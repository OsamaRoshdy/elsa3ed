<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SyncRun;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Talks to the Daftra API v2 (https://docs.daftara.dev) and mirrors the data into local tables.
 *
 * Endpoints used (all GET, `.json` suffix, paginated with `page` + `limit` and a `pagination.page_count` summary):
 *  - /products.json, /products/{id}.json     → data[].Product (+ ProductImage, ProductCategory), stock in Product.stock_balance
 *  - /clients.json,  /clients/{id}.json      → data[].Client
 *  - /invoices.json, /invoices/{id}.json     → data[].Invoice (summary_total / summary_paid / summary_unpaid)
 *  - /client_payments.json                   → data[].ClientPayment (status 1 = completed)
 */
class DaftraService
{
    private const PAGE_LIMIT = 200;

    private const PRODUCT_TYPE_SERVICE = 2;

    private const PRODUCT_STATUS_ACTIVE = 0;

    private const INVOICE_TYPE_ADVANCE_PAYMENT = 19;

    private const PAYMENT_STATUS_COMPLETED = 1;

    public function isConfigured(): bool
    {
        return filled(config('services.daftra.base_url')) && filled(config('services.daftra.token'));
    }

    /**
     * Cheap call used by the admin "Test connection" button.
     */
    public function testConnection(): bool
    {
        $this->get('products.json', ['limit' => 1]);

        return true;
    }

    // ---------------------------------------------------------------------
    // Raw API calls
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getProducts(int $page = 1, int $limit = self::PAGE_LIMIT): array
    {
        return $this->get('products.json', ['page' => $page, 'limit' => $limit]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProduct(int $daftraId): ?array
    {
        return $this->find("products/{$daftraId}.json");
    }

    /**
     * @return array<string, mixed>
     */
    public function getClients(int $page = 1, int $limit = self::PAGE_LIMIT): array
    {
        return $this->get('clients.json', ['page' => $page, 'limit' => $limit]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getClient(int $daftraId): ?array
    {
        return $this->find("clients/{$daftraId}.json");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getInvoices(int $page = 1, array $filters = []): array
    {
        return $this->get('invoices.json', ['page' => $page, 'limit' => self::PAGE_LIMIT] + $filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getInvoice(int $daftraId): ?array
    {
        return $this->find("invoices/{$daftraId}.json");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPayments(int $page = 1, array $filters = []): array
    {
        return $this->get('client_payments.json', ['page' => $page, 'limit' => self::PAGE_LIMIT] + $filters);
    }

    // ---------------------------------------------------------------------
    // Full synchronization
    // ---------------------------------------------------------------------

    /**
     * Runs a full sync. Safe to run repeatedly: records are matched on their Daftra ID,
     * and anything that no longer exists in Daftra is removed locally.
     */
    public function syncAll(string $triggeredBy = 'manual'): SyncRun
    {
        $lock = Cache::lock('daftra-sync', 1800);

        if (! $lock->get()) {
            throw new DaftraException(__('A synchronization is already running. Please wait.'));
        }

        @set_time_limit(0);

        $run = SyncRun::create([
            'status' => SyncRun::STATUS_RUNNING,
            'triggered_by' => $triggeredBy,
            'started_at' => now(),
        ]);

        try {
            if (! $this->isConfigured()) {
                throw DaftraException::notConfigured();
            }

            $run->update(['products_count' => $this->syncProducts()]);
            $run->update(['clients_count' => $this->syncClients()]);
            $run->update(['invoices_count' => $this->syncInvoices()]);
            $run->update(['payments_count' => $this->syncPayments()]);
            $this->refreshAllBalances();

            $run->update(['status' => SyncRun::STATUS_SUCCESS, 'finished_at' => now()]);
        } catch (Throwable $exception) {
            Log::error('Daftra sync failed', ['sync_run' => $run->id, 'error' => $exception->getMessage()]);

            $run->update([
                'status' => SyncRun::STATUS_FAILED,
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
        } finally {
            $lock->release();
        }

        return $run;
    }

    public function syncProducts(): int
    {
        $startedAt = now();
        $count = $this->eachItem('products.json', [], fn (array $item) => $this->upsertProduct($item) !== null);

        Product::where('last_synced_at', '<', $startedAt)->orWhereNull('last_synced_at')->delete();

        return $count;
    }

    public function syncClients(): int
    {
        $startedAt = now();
        $count = $this->eachItem('clients.json', [], fn (array $item) => $this->upsertClient($item) !== null);

        Client::where('last_synced_at', '<', $startedAt)->orWhereNull('last_synced_at')->delete();

        return $count;
    }

    public function syncInvoices(): int
    {
        $startedAt = now();
        $clientIds = Client::pluck('id', 'daftra_id')->all();
        $count = $this->eachItem('invoices.json', [], fn (array $item) => $this->upsertInvoice($item, $clientIds) !== null);

        Invoice::where('last_synced_at', '<', $startedAt)->orWhereNull('last_synced_at')->delete();

        return $count;
    }

    public function syncPayments(): int
    {
        $startedAt = now();
        $clientIds = Client::pluck('id', 'daftra_id')->all();
        $invoiceIds = Invoice::pluck('id', 'daftra_id')->all();
        $count = $this->eachItem('client_payments.json', [], fn (array $item) => $this->upsertPayment($item, $clientIds, $invoiceIds) !== null);

        Payment::where('last_synced_at', '<', $startedAt)->orWhereNull('last_synced_at')->delete();

        return $count;
    }

    public function refreshAllBalances(): void
    {
        $balances = Invoice::query()
            ->whereNotNull('client_id')
            ->groupBy('client_id')
            ->selectRaw('client_id, SUM(remaining) as balance')
            ->pluck('balance', 'client_id');

        Client::query()->update(['balance' => 0]);

        foreach ($balances as $clientId => $balance) {
            Client::whereKey($clientId)->update(['balance' => $balance]);
        }
    }

    // ---------------------------------------------------------------------
    // Single-record refresh (used by webhooks)
    // ---------------------------------------------------------------------

    /**
     * Re-fetches a product from Daftra. If Daftra no longer has it, it is removed locally.
     */
    public function refreshProduct(int $daftraId): void
    {
        $item = $this->getProduct($daftraId);

        if ($item === null || $this->upsertProduct($item) === null) {
            Product::where('daftra_id', $daftraId)->delete();
        }
    }

    public function refreshClient(int $daftraId): void
    {
        $item = $this->getClient($daftraId);

        if ($item === null) {
            Client::where('daftra_id', $daftraId)->delete();

            return;
        }

        $this->upsertClient($item);
    }

    /**
     * Re-fetches an invoice and the client's payments, then recalculates the client's balance.
     */
    public function refreshInvoice(int $daftraId): void
    {
        $item = $this->getInvoice($daftraId);
        $existing = Invoice::where('daftra_id', $daftraId)->first();

        if ($item === null) {
            $existing?->delete();
            $existing?->client?->refreshBalance();

            return;
        }

        $invoice = $this->upsertInvoice($item, Client::pluck('id', 'daftra_id')->all());

        if ($invoice === null) {
            $existing?->delete();
        }

        $client = $invoice?->client ?? $existing?->client;

        if ($client) {
            $this->refreshClientPayments($client);
            $client->refreshBalance();
        }
    }

    public function refreshClientPayments(Client $client): void
    {
        $startedAt = now();
        $clientIds = [$client->daftra_id => $client->id];
        $invoiceIds = Invoice::where('client_id', $client->id)->pluck('id', 'daftra_id')->all();

        $this->eachItem('client_payments.json', ['client_id' => $client->daftra_id], fn (array $item) => $this->upsertPayment($item, $clientIds, $invoiceIds) !== null);

        $client->payments()->where('last_synced_at', '<', $startedAt)->delete();
    }

    // ---------------------------------------------------------------------
    // Invoice PDF
    // ---------------------------------------------------------------------

    /**
     * Daftra's documented `invoice_pdf_url` (a hashed link) opens the invoice in Daftra's client area
     * rather than returning a PDF. Visiting it starts a client-area session; with that session,
     * `/client/invoices/view/{id}.pdf` returns the actual PDF. That second path is NOT in Daftra's
     * API docs, so callers must fall back to redirecting to `pdf_url` when this returns null.
     */
    public function downloadInvoicePdf(Invoice $invoice): ?string
    {
        $pdfUrl = $this->invoicePdfUrl($invoice);

        if ($pdfUrl === null) {
            return null;
        }

        try {
            $cookies = new CookieJar;
            $browser = Http::connectTimeout(10)->timeout(60)->withOptions(['cookies' => $cookies]);

            $browser->get($pdfUrl)->throw();

            $origin = parse_url($pdfUrl, PHP_URL_SCHEME).'://'.parse_url($pdfUrl, PHP_URL_HOST);
            $response = $browser->get("{$origin}/client/invoices/view/{$invoice->daftra_id}.pdf");

            if ($response->successful() && str_starts_with($response->body(), '%PDF')) {
                return $response->body();
            }

            Log::warning('Daftra invoice PDF download did not return a PDF', ['invoice' => $invoice->daftra_id, 'status' => $response->status()]);
        } catch (Throwable $exception) {
            Log::warning('Daftra invoice PDF download failed', ['invoice' => $invoice->daftra_id, 'error' => $exception->getMessage()]);
        }

        return null;
    }

    /**
     * Returns the stored hashed link, fetching it from the API for invoices synced before it was stored.
     */
    public function invoicePdfUrl(Invoice $invoice): ?string
    {
        if ($invoice->pdf_url) {
            return $invoice->pdf_url;
        }

        try {
            $url = $this->getInvoice($invoice->daftra_id)['Invoice']['invoice_pdf_url'] ?? null;
        } catch (DaftraException) {
            return null;
        }

        if ($url) {
            $invoice->forceFill(['pdf_url' => $url])->save();
        }

        return $url ?: null;
    }

    // ---------------------------------------------------------------------
    // Mapping Daftra records to local rows
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $item  A `data[]` entry: ['Product' => [...], 'ProductImage' => [...], ...]
     */
    public function upsertProduct(array $item): ?Product
    {
        $data = $item['Product'] ?? null;

        if (! is_array($data) || empty($data['id'])) {
            return null;
        }

        if ((int) ($data['type'] ?? 1) === self::PRODUCT_TYPE_SERVICE) {
            return null;
        }

        $tracksStock = (int) ($data['track_stock'] ?? 1) === 1;

        return Product::updateOrCreate(['daftra_id' => (int) $data['id']], [
            'sku' => ($data['product_code'] ?? null) ?: (($data['barcode'] ?? null) ?: null),
            'name' => $data['name'] ?? ('#'.$data['id']),
            'description' => $data['description'] ?? null,
            'image' => $this->extractImage($item['ProductImage'] ?? []),
            'category' => $this->extractCategory($data, $item['ProductCategory'] ?? []),
            'price' => (float) ($data['unit_price'] ?? 0),
            'stock_quantity' => $tracksStock ? (float) ($data['stock_balance'] ?? 0) : null,
            // Daftra spells this field "low_stock_thershold".
            'low_stock_threshold' => ($data['low_stock_thershold'] ?? null) ?: null,
            'is_active' => (int) ($data['status'] ?? self::PRODUCT_STATUS_ACTIVE) === self::PRODUCT_STATUS_ACTIVE,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertClient(array $item): ?Client
    {
        $data = $item['Client'] ?? null;

        if (! is_array($data) || empty($data['id'])) {
            return null;
        }

        $name = trim((string) ($data['business_name'] ?? ''))
            ?: trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''))
            ?: '#'.($data['client_number'] ?? $data['id']);

        return Client::updateOrCreate(['daftra_id' => (int) $data['id']], [
            'name' => $name,
            'email' => ($data['email'] ?? null) ?: null,
            'phone' => ($data['phone1'] ?? null) ?: (($data['phone2'] ?? null) ?: null),
            'credit_limit' => ($data['credit_limit'] ?? null) !== null ? (float) $data['credit_limit'] : null,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, int>  $clientIds  Daftra client id => local client id
     */
    public function upsertInvoice(array $item, array $clientIds): ?Invoice
    {
        $data = $item['Invoice'] ?? null;

        if (! is_array($data) || empty($data['id'])) {
            return null;
        }

        // Drafts are not final and advance payments are not amounts owed, so neither is shown to clients.
        if (! empty($data['draft']) || (int) ($data['type'] ?? 0) === self::INVOICE_TYPE_ADVANCE_PAYMENT) {
            return null;
        }

        $total = (float) ($data['summary_total'] ?? 0);
        $paid = (float) ($data['summary_paid'] ?? 0);
        $remaining = (float) ($data['summary_unpaid'] ?? max($total - $paid, 0));

        return Invoice::updateOrCreate(['daftra_id' => (int) $data['id']], [
            'client_id' => $clientIds[(int) ($data['client_id'] ?? 0)] ?? null,
            'invoice_number' => $data['no'] ?? null,
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'status' => Invoice::statusFromAmounts($total, $paid, $remaining),
            'currency' => $data['currency_code'] ?? null,
            'invoice_date' => $this->parseDate($data['date'] ?? null),
            'due_date' => $this->parseDate($data['due_date'] ?? null),
            'pdf_url' => ($data['invoice_pdf_url'] ?? null) ?: null,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, int>  $clientIds
     * @param  array<int, int>  $invoiceIds
     */
    public function upsertPayment(array $item, array $clientIds, array $invoiceIds): ?Payment
    {
        $data = $item['ClientPayment'] ?? $item['InvoicePayment'] ?? null;

        if (! is_array($data) || empty($data['id'])) {
            return null;
        }

        if ((int) ($data['status'] ?? self::PAYMENT_STATUS_COMPLETED) !== self::PAYMENT_STATUS_COMPLETED) {
            return null;
        }

        return Payment::updateOrCreate(['daftra_id' => (int) $data['id']], [
            'client_id' => $clientIds[(int) ($data['client_id'] ?? 0)] ?? null,
            'invoice_id' => $invoiceIds[(int) ($data['invoice_id'] ?? 0)] ?? null,
            'amount' => (float) ($data['amount'] ?? 0),
            'payment_date' => $this->parseDate($data['date'] ?? null),
            'payment_method' => ($data['payment_method'] ?? null) ?: null,
            'last_synced_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------------
    // HTTP helpers
    // ---------------------------------------------------------------------

    /**
     * Walks every page of a list endpoint and calls $callback for each item.
     * Returns how many items the callback accepted (returned true for).
     *
     * @param  array<string, mixed>  $filters
     * @param  callable(array<string, mixed>): bool  $callback
     */
    private function eachItem(string $path, array $filters, callable $callback): int
    {
        $page = 1;
        $count = 0;

        do {
            $response = $this->get($path, ['page' => $page, 'limit' => self::PAGE_LIMIT] + $filters);

            foreach ($response['data'] ?? [] as $item) {
                if (is_array($item) && $callback($item)) {
                    $count++;
                }
            }

            $pageCount = (int) Arr::get($response, 'pagination.page_count', 1);
            $page++;
        } while ($page <= $pageCount);

        return $count;
    }

    /**
     * Fetches a single record. Returns the `data` wrapper (e.g. ['Product' => [...]]) or null when not found.
     *
     * @return array<string, mixed>|null
     */
    private function find(string $path): ?array
    {
        try {
            $response = $this->get($path);
        } catch (DaftraException $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }

        $data = $response['data'] ?? null;

        return is_array($data) && $data !== [] ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            throw DaftraException::notConfigured();
        }

        try {
            $response = $this->client()->get($path, $query);
        } catch (ConnectionException $exception) {
            Log::error('Daftra API connection failed', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new DaftraException('Could not connect to Daftra (timeout or network error).', 0, $exception);
        }

        return $this->handleResponse($response, $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function handleResponse(Response $response, string $path): array
    {
        if ($response->successful()) {
            $json = $response->json();

            if (! is_array($json)) {
                Log::error('Daftra API returned an invalid response', ['path' => $path, 'body' => mb_substr($response->body(), 0, 500)]);

                throw new DaftraException('Daftra returned an invalid (non-JSON) response.', $response->status());
            }

            return $json;
        }

        $message = match (true) {
            $response->status() === 401 => 'Daftra rejected the API credentials (401). Check DAFTRA_API_TOKEN.',
            $response->status() === 403 => 'The Daftra API key does not have permission for this resource (403).',
            $response->status() === 404 => 'Record not found in Daftra (404).',
            $response->status() === 429 => 'Daftra rate limit reached (429). Please try again in a few minutes.',
            $response->serverError() => "Daftra server error ({$response->status()}).",
            default => "Daftra API error ({$response->status()}).",
        };

        if ($response->status() !== 404) {
            Log::error('Daftra API error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
        }

        throw new DaftraException($message, $response->status());
    }

    private function client(): PendingRequest
    {
        $config = config('services.daftra');

        $request = Http::baseUrl($config['base_url'])
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout($config['timeout'])
            ->retry(3, function (int $attempt, Throwable $exception) {
                if ($exception instanceof RequestException && $exception->response->status() === 429) {
                    return max((int) $exception->response->header('Retry-After'), 2 * $attempt) * 1000;
                }

                return 500 * $attempt;
            }, function (Throwable $exception) {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException
                        && ($exception->response->serverError() || $exception->response->status() === 429));
            }, throw: false);

        return $config['auth_type'] === 'bearer'
            ? $request->withToken($config['token'])
            : $request->withHeaders(['apikey' => $config['token']]);
    }

    /**
     * @param  array<int, mixed>  $images
     */
    private function extractImage(array $images): ?string
    {
        foreach ($images as $image) {
            $image = $image['ProductImage'] ?? $image;

            foreach (['file_full_path', 'full_path', 'url', 'file'] as $key) {
                $value = $image[$key] ?? null;

                if (is_string($value) && str_starts_with($value, 'http')) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<int, mixed>  $categories
     */
    private function extractCategory(array $product, array $categories): ?string
    {
        if (filled($product['category'] ?? null)) {
            return trim($product['category']);
        }

        foreach ($categories as $category) {
            $name = $category['Category']['name'] ?? $category['name'] ?? null;

            if (filled($name)) {
                return trim($name);
            }
        }

        return null;
    }

    private function parseDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || str_starts_with($value, '0000')) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
