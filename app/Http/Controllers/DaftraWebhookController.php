<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use App\Services\DaftraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives Daftra webhooks (App Hub → Webhooks).
 *
 * Daftra documents these events: Invoice created/deleted, Client created/updated/deleted,
 * Product created/updated/deleted. It does not document a payload schema or a signature,
 * so the payload is only used to find *which* record changed. The record is then re-fetched
 * from the Daftra API, meaning a forged request can never write data that Daftra doesn't hold.
 *
 * Since Daftra signs nothing, register the URL with a secret token:
 *   https://your-portal.com/webhooks/daftra?token=DAFTRA_WEBHOOK_SECRET
 */
class DaftraWebhookController extends Controller
{
    public function __invoke(Request $request, DaftraService $daftra): JsonResponse
    {
        $secret = (string) config('services.daftra.webhook_secret');
        $token = (string) ($request->query('token') ?? $request->header('X-Webhook-Secret', ''));

        if ($secret !== '' && ! hash_equals($secret, $token)) {
            Log::warning('Daftra webhook rejected: invalid token', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid token'], 403);
        }

        $payload = $request->json()->all() ?: $request->except('token');
        $event = $this->detectEvent($request, $payload);
        $resource = $this->detectResource($event, $payload);
        $daftraId = $this->detectId($payload, $resource);

        $log = WebhookLog::create([
            'event' => $event,
            'daftra_id' => $daftraId,
            'payload' => $payload,
        ]);

        try {
            match (true) {
                $daftraId === null || $resource === null => throw new \RuntimeException('Unrecognized webhook: could not determine the record type or ID.'),
                $resource === 'product' => $daftra->refreshProduct($daftraId),
                $resource === 'client' => $daftra->refreshClient($daftraId),
                $resource === 'invoice' => $daftra->refreshInvoice($daftraId),
            };

            $log->update(['processed' => true, 'processed_at' => now()]);
        } catch (Throwable $exception) {
            Log::error('Daftra webhook processing failed', ['webhook_log' => $log->id, 'error' => $exception->getMessage()]);

            $log->update(['error' => $exception->getMessage(), 'processed_at' => now()]);
        }

        // Always acknowledge so Daftra doesn't hammer us; failures are visible in the admin webhook log.
        return response()->json(['received' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detectEvent(Request $request, array $payload): ?string
    {
        foreach (['event', 'event_name', 'event_type', 'action', 'type', 'topic'] as $key) {
            if (is_string($payload[$key] ?? null) && $payload[$key] !== '') {
                return mb_substr($payload[$key], 0, 255);
            }
        }

        return $request->header('X-Daftra-Event') ?: $request->query('event');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detectResource(?string $event, array $payload): ?string
    {
        $haystack = strtolower((string) $event);

        foreach (['invoice', 'client', 'product'] as $resource) {
            if (str_contains($haystack, $resource)) {
                return $resource;
            }
        }

        // Fall back to the shape of the payload, e.g. {"Product": {...}} or {"data": {"Client": {...}}}.
        foreach (['Invoice' => 'invoice', 'Client' => 'client', 'Product' => 'product'] as $key => $resource) {
            if (isset($payload[$key]) || isset($payload['data'][$key])) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detectId(array $payload, ?string $resource): ?int
    {
        $model = $resource ? ucfirst($resource) : null;

        $candidates = array_filter([
            $model ? "{$model}.id" : null,
            $model ? "data.{$model}.id" : null,
            $resource ? "{$resource}_id" : null,
            $resource ? "data.{$resource}_id" : null,
            'data.id',
            'id',
            'entity_id',
            'record_id',
        ]);

        foreach ($candidates as $path) {
            $value = Arr::get($payload, $path);

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }
}
