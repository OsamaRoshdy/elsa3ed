# Daftra Client Portal — Project Documentation

A simple Laravel portal that mirrors data from **Daftra** (the source of truth) and shows it to:

- **Admins** — products, stock, clients, balances, invoices, payments, sync status, webhook activity, portal users.
- **Clients** — their own balance, what they paid, what they owe, their invoices and payments, and the product catalogue with live stock.
- **Clients and guests** — a storefront in the style of speero.net: home page with a "Select your car" finder, parts list with filters, product pages with a "fits your car?" check (see §14).

On top of the Daftra data, the admin manages **brands, car models, categories and tags** locally and links them to products (§13). These are never written to Daftra and survive every sync.

The browser never talks to Daftra. Data flows like this:

```
Browser ──► Laravel ──► MySQL                 (every page reads the local database)
Laravel ──► Daftra API                         (sync: manual button, scheduled, or webhook-triggered)
Daftra  ──► Webhook ──► Laravel ──► MySQL      (near real-time updates where Daftra supports it)
```

---

## 1. Technology

| Area        | Choice                                                                 |
|-------------|------------------------------------------------------------------------|
| Backend     | Laravel 13, PHP 8.4                                                    |
| Database    | MySQL (database `elsa3d`)                                              |
| Views       | Blade + Tailwind CSS v4                                                |
| JS          | Alpine.js (mobile menu, sync button state, small form helpers); jQuery 3 + Select2 4.1 for searchable dropdowns |
| HTTP        | Laravel HTTP client (timeouts, retries, rate-limit handling)           |
| Auth        | Laravel session auth (custom simple login controller, no registration) |
| Fonts       | Instrument Sans (English), **Cairo** (Arabic) — bundled via Vite       |
| Tests       | Pest 5 feature tests (`tests/Feature`, SQLite in memory) — run with `php artisan test --compact` |
| Theme       | Main colour **#ef4444** (red), defined once as the `brand` palette in `resources/css/app.css` |

Not used, on purpose: queues, Redis, React/Vue, repositories, service interfaces.

---

## 2. Installation & setup

```bash
composer install
npm install
npm run build

# .env — database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elsa3d
DB_USERNAME=root
DB_PASSWORD=your-password

php artisan migrate
php artisan storage:link             # brand logos and category images are served from storage/app/public
php artisan portal:create-admin      # prompts for name, email, password
php artisan db:seed --class=CatalogSeeder   # brands + logos, models, categories, tags; links products (§13)
```

`APP_URL` must be the address the site is opened at (locally `http://elsa3d.test`, on the server the real `https://` domain). Logo and category image links are built from it — a wrong `APP_URL` shows broken images.

### Customer login code (`.env`)

```dotenv
LOGIN_OTP_ENABLED=true          # false = customers log in with email + password only
LOGIN_OTP_FIXED_CODE=123456     # every code is 123456 until SMS/email sending is set up; remove for random codes
```

See §15.

### Daftra configuration (`.env`)

```dotenv
DAFTRA_API_BASE_URL=https://YOUR-SUBDOMAIN.daftra.com/api2
DAFTRA_API_TOKEN=your-api-key          # Daftra → Settings → API → API Key
DAFTRA_AUTH_TYPE=apikey                # apikey (default) or bearer (OAuth access token)
DAFTRA_WEBHOOK_SECRET=long-random-text # appended to the webhook URL as ?token=
DAFTRA_CURRENCY=EGP                    # display currency
DAFTRA_LOW_STOCK_THRESHOLD=10          # used when a product has no threshold in Daftra
DAFTRA_TIMEOUT=30                      # optional, seconds per API request
```

These are read in `config/services.php` under the `daftra` key. Credentials never reach Blade or the browser.

### First data import

- From the terminal: `php artisan daftra:sync`
- Or log in as admin → **Sync** → **Test connection** → **Sync with Daftra**

### Scheduler (production)

Stock movements and payments have no Daftra webhook, so a sync runs every 15 minutes. Add the standard Laravel cron entry on the server:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 3. Daftra API — what was verified in the official docs

Docs: <https://docs.daftara.dev> (Daftra API v2).

| Topic           | Finding                                                                                         |
|-----------------|-------------------------------------------------------------------------------------------------|
| Base URL        | `https://{subdomain}.daftra.com/api2`                                                           |
| Authentication  | Header `apikey: <key>` (Settings → API), or `Authorization: Bearer <token>` (OAuth `/v2/oauth/token`) |
| Format          | Paths end in `.json`, e.g. `/products.json`                                                     |
| Pagination      | `?page=N&limit=N` (max 1000). Response has `pagination.page_count`, `total_results`, `next`, `prev` |
| Response shape  | `{ "result": "successful", "data": [ { "Product": {...} } ], "pagination": {...} }`              |

### Endpoints used (all `GET`)

| Data       | Endpoint                                   | Key fields used                                                                 |
|------------|--------------------------------------------|---------------------------------------------------------------------------------|
| Products   | `/products.json`, `/products/{id}.json`    | `id, name, product_code, barcode, unit_price, stock_balance, track_stock, low_stock_thershold`*, `status, type, category`, `ProductImage[]`, `ProductCategory[]` |
| Clients    | `/clients.json`, `/clients/{id}.json`      | `id, business_name, first_name, last_name, email, phone1, phone2, credit_limit` |
| Invoices   | `/invoices.json`, `/invoices/{id}.json`    | `id, no, client_id, date, draft, type, currency_code, summary_total, summary_paid, summary_unpaid, invoice_pdf_url` |
| Payments   | `/client_payments.json`                    | `id, client_id, invoice_id, amount, date, payment_method, status`               |

\* Daftra really spells it `low_stock_thershold`.

Stock comes from `Product.stock_balance` — there is no separate stock endpoint needed.

`/client_payments` and `/invoice_payments` read the **same** payment records (one filters "linked to a client", the other "linked to an invoice"). The portal syncs `/client_payments` only, to avoid duplicates.

### Webhooks available in Daftra

Configured in Daftra **App Hub → Webhooks** (URL must be `https://`):

| Resource | Events                               |
|----------|--------------------------------------|
| Product  | created, updated, deleted            |
| Client   | created, updated, deleted            |
| Invoice  | created, deleted                     |

### Limitations found (and how they are handled)

| Limitation in Daftra's documented API                     | Solution in the portal                                                                 |
|-----------------------------------------------------------|----------------------------------------------------------------------------------------|
| No webhook for stock movements or payments                | Scheduled sync every 15 minutes + manual "Sync with Daftra" button                     |
| Webhooks are not signed; payload schema not documented    | Secret `?token=` on the URL; payload only used to find the record ID, which is then **re-fetched from the API** (a forged request can't write fake data) |
| Client API has no "balance" field                          | Balance = sum of `summary_unpaid` of the client's invoices (opening balance not included) |
| Invoices have no due date field                            | No "overdue" status; statuses are paid / partial / unpaid / overpaid                    |
| `ProductImage` item shape not documented                   | Tries `file_full_path`, `full_path`, `url`, `file` (first absolute URL wins)            |

---

## 4. Database

| Table          | Purpose                                                                                       |
|----------------|-----------------------------------------------------------------------------------------------|
| `users`        | Portal logins. Added `role` (`admin`/`client`) and `daftra_client_id` (links a client user to a Daftra client) |
| `products`     | `daftra_id` (unique), `sku, name, sort_name` (hidden sort key), `description, image, category, price, stock_quantity, low_stock_threshold, is_active, last_synced_at` |
| `clients`      | `daftra_id` (unique), `name, email, phone, balance, credit_limit, last_synced_at`               |
| `invoices`     | `daftra_id` (unique), `client_id → clients`, `invoice_number, total, paid, remaining, status, currency, invoice_date, due_date, pdf_url` (hidden), `last_synced_at` |
| `payments`     | `daftra_id` (unique), `client_id → clients`, `invoice_id → invoices` (nullable), `amount, payment_date, payment_method, last_synced_at` |
| `webhook_logs` | `event, daftra_id, payload (JSON), processed, processed_at, error`                             |
| `settings`     | Key/value admin settings (`catalogue_sort`, `catalogue_client_sort`), cached; model `App\Models\Setting::get()/set()` |
| `sync_runs`    | Sync history: `status, triggered_by, products_count, clients_count, invoices_count, payments_count, error, started_at, finished_at` |
| `brands`       | `name` (unique), `logo_path` (file on the `public` disk) — e.g. Toyota, Kia                    |
| `car_models`   | `brand_id → brands`, `name` (unique per brand) — e.g. Kia → Cerato. Model class `CarModel` (avoids clashing with Eloquent's `Model`) |
| `fitments`     | `product_id → products`, `car_model_id → car_models`, `year_from`, `year_to` (NULL = open-ended) — "fits Kia Cerato 2015–2017" |
| `categories`   | `name` (unique), `image_path` (file on the `public` disk)                                      |
| `tags`         | `name` (unique), `color` (one of `Tag::COLORS`: slate, emerald, sky, amber, rose, violet) — e.g. أصلي, تجاري كوري |
| `brand_product`, `category_product`, `product_tag` | Many-to-many links (cascade on delete)              |

Notes:
- `stock_quantity` is `NULL` when Daftra doesn't track stock for that product → shown as available.
- Daftra IDs are the external identifiers; every sync uses `updateOrCreate` on `daftra_id`, so no duplicates.
- `products.category` is Daftra's own category. It is kept and updated by the sync but **no longer shown to clients** — the storefront uses the local `categories` instead.
- Brands, models, categories, tags and fitments are local only: a sync never changes them. Deleting a product (gone from Daftra) removes its links.

---

## 5. Synchronization

Code: `app/Services/DaftraService.php`

`syncAll()` runs, in order:

1. Products → 2. Clients → 3. Invoices → 4. Payments → 5. Recalculate client balances

Rules:
- **Idempotent** — safe to run any number of times.
- **Deletions** — anything not seen in a complete sync (older `last_synced_at`) is removed locally.
- **Skipped** — services (product `type = 2`), draft invoices, advance-payment documents (`type = 19`), payments whose `status` isn't `1` (completed).
- **Locking** — a cache lock prevents two syncs running at the same time.
- **Failure-safe** — if a sync fails, the error is logged and saved in `sync_runs`; previously synced data stays visible.

Error handling for every API call:

| Situation          | Behaviour                                                              |
|--------------------|------------------------------------------------------------------------|
| Timeout / network  | Retried 3×, then "Could not connect to Daftra"                         |
| 429 rate limit     | Retried, honouring `Retry-After`                                       |
| 5xx server error   | Retried 3×                                                             |
| 401 / 403          | Clear message about the API key / permissions                          |
| 404                | Treated as "record deleted in Daftra" (webhook refresh)                |
| Non-JSON response  | "Invalid response" error                                               |

All errors go to `storage/logs/laravel.log`.

---

## 6. Webhook endpoint

```
POST /webhooks/daftra?token=DAFTRA_WEBHOOK_SECRET
```

Code: `app/Http/Controllers/DaftraWebhookController.php`

1. Rejects requests with a wrong token (403) when `DAFTRA_WEBHOOK_SECRET` is set.
2. Saves the raw payload to `webhook_logs`.
3. Detects the event (`event`, `event_name`, `action`, `type`… or `X-Daftra-Event` header) and the resource (product / client / invoice).
4. Detects the record ID from common payload shapes (`Product.id`, `data.Product.id`, `id`, `data.id`, …).
5. Re-fetches that record from the Daftra API and updates/deletes it locally.
   - Invoice events also refresh that client's payments and balance.
6. Marks the log as processed (or stores the error). Always answers `200` so Daftra doesn't retry endlessly.

CSRF is disabled for this one route; it is rate-limited to 120 requests/minute.

The admin **Webhooks** page shows the exact URL to paste into Daftra (with the token) and every received webhook with its payload and status.

---

## 7. Users, roles & security

| Role    | Access                                                                                    |
|---------|-------------------------------------------------------------------------------------------|
| Admin   | Everything under `/admin/*`                                                               |
| Client  | `/`, `/dashboard`, `/invoices`, `/invoices/{id}`, `/payments`, `/account`, `/cart`, `/orders`, `/products` |
| Guest   | `/`, `/login`, `/products`, `/products/{id}` (store is public, per the spec)              |

- No registration. Admins create users in **Users**; a client user must be linked to a synced Daftra client (dropdown auto-fills name/email).
- A client's data is always taken from `auth()->user()->client` — never from a request parameter. Opening another client's invoice returns **404**.
- Role middleware `role:admin` / `role:client` (`app/Http/Middleware/EnsureUserHasRole.php`).
- Login is throttled (5 failed attempts per email+IP).
- **Customers enter a 6-digit login code** after their password (§15). Admins log in with the password only.
- After login, customers land on the store home page (or the page they were trying to open); admins on `/admin/dashboard`.
- **SKUs are masked** for everyone except admins (§16).
- Admins can't delete themselves or remove their own admin role.
- CSRF on all forms, validation on all input, Blade escaping everywhere.

To make the product catalogue login-only, move the two `products.*` routes inside the `auth` group in `routes/web.php`.

---

## 8. Routes

| Method | URI                              | Who    | Purpose                              |
|--------|----------------------------------|--------|--------------------------------------|
| GET    | `/`                              | all    | Store home page (admins → `/admin/dashboard`) |
| GET    | `/login`, POST `/login`          | guest  | Login (step 1: email + password)     |
| GET/POST | `/login/code`                  | guest  | Login step 2: customer's 6-digit code |
| POST   | `/login/code/resend`             | guest  | New code (3 per minute)              |
| POST/DELETE | `/vehicle`                  | all    | Remember / forget the selected car ("Select your car") |
| POST   | `/logout`                        | auth   | Logout                               |
| GET    | `/locale/{ar|en}`                | all    | Switch language                      |
| GET    | `/products`, `/products/{id}`    | all    | Catalogue, search, filters, details  |
| GET    | `/dashboard`                     | client | Client dashboard (admins redirected) |
| GET    | `/invoices`, `/invoices/{id}`    | client | Own invoices                         |
| GET    | `/payments`                      | client | Own payments                         |
| GET    | `/account`                       | client | Account summary                      |
| GET    | `/admin/dashboard`               | admin  | Stats, last sync, low stock, balances, webhooks |
| GET    | `/admin/products`                | admin  | Products table (search, category, brand, tag, low stock) |
| GET/PUT | `/admin/products/{id}/edit`, `/admin/products/{id}` | admin | Assign categories, brands, tags and fitted vehicles |
| *      | `/admin/categories…`, `/admin/brands…`, `/admin/car-models…`, `/admin/tags…` (resources) | admin | Manage categories (with image), brands (with logo), models, tags (with colour) |
| GET    | `/admin/clients`, `/admin/clients/{id}` | admin | Clients + client account page  |
| GET    | `/admin/invoices`, `/admin/invoices/{id}` | admin | Invoices                      |
| GET    | `/admin/payments`                | admin  | Payments                             |
| GET    | `/admin/webhooks`                | admin  | Webhook URL + log                    |
| GET    | `/admin/sync`, POST `/admin/sync`, POST `/admin/sync/test` | admin | Sync page, run sync, test connection |
| *      | `/admin/users…` (resource)       | admin  | Create / edit / delete portal users  |
| POST   | `/webhooks/daftra`               | Daftra | Webhook receiver                     |

---

## 9. UI, language & design

- **Admins:** modern SaaS style — cards, rounded corners, soft shadows, sidebar navigation, responsive tables, empty states, badges. Mobile: sidebar collapses into a menu button. "View store" link in the header.
- **Clients and guests:** storefront layout (header, category/brand menus, footer) — see §14.
- **Theme colour #ef4444:** the `brand-*` palette in `app.css` (`brand-600` = #ef4444 for buttons/highlights, `brand-700` = #dc2626 for hover). Native checkboxes/radios use it too (`accent-color`).
- **Stock badges:** 🟢 Available (above threshold) · 🟡 Low Stock (1 … threshold) · 🔴 Out of Stock (0). Threshold = product's Daftra threshold, else `DAFTRA_LOW_STOCK_THRESHOLD` (10).
- **Invoice badges:** Paid · Partially paid · Unpaid · Overpaid.
- **Brand:** ال سعد — Auto Parts logo in sidebar, header, login page and browser tab. The logo square is a red gradient (#ef4444 → #991b1b) with a white gear and amber hub.
- Custom pagination (`components/pagination/links.blade.php`) used by every list.
- "Last synchronized: X minutes ago" on the catalogue, product page, account page.
- Sync button shows "Sync started…" with a spinner while running, then a success/error message.
- **Arabic is the default** (`APP_LOCALE=ar`), full RTL layout (`dir="rtl"`, logical Tailwind classes `ms/me/ps/pe/start/end`).
- Language switcher: **العربية | English** (stored in session, kept after logout).
- Translations: `lang/ar.json` (all UI strings), `lang/ar/validation.php`, `lang/ar/pagination.php`. English uses the keys as-is.
- **Fonts:** Cairo for Arabic, Instrument Sans for English (Cairo is also the fallback for Arabic text on English pages). Both are bundled at build time via `bunny()` in `vite.config.js`; Cairo includes the `arabic` subset.

---

## 10. Artisan commands

| Command                        | What it does                                              |
|--------------------------------|-----------------------------------------------------------|
| `php artisan daftra:sync`      | Full sync; prints counts or the error                     |
| `php artisan portal:create-admin` | Creates an admin user (interactive)                    |
| `php artisan schedule:run`     | Runs `daftra:sync` every 15 minutes (via cron)            |
| `php artisan db:seed --class=CatalogSeeder` | Brands + logos, models, categories, tags; links every product from its name (safe to re-run) |
| `php artisan db:seed --class=CarBrandsSeeder` | Only the 17 car brands with their logos (safe to re-run) |

---

## 11. File map

```
app/
  Services/DaftraService.php          API client + sync + single-record refresh
  Services/DaftraException.php        Friendly API errors
  Services/LoginOtp.php               Customer login code (issue, verify, send)
  Support/Garage.php                  Selected car ("Select your car") kept in the session
  Support/SkuMask.php                 SKU masking for non-admins
  Models/                             Product, Client, Invoice, Payment, WebhookLog, SyncRun, User,
                                      Brand, CarModel, Fitment, Category, Tag, Order, OrderItem, Setting
  Http/Middleware/EnsureUserHasRole.php
  Http/Middleware/SetLocale.php
  Http/Controllers/
    Auth/LoginController.php          step 1 (password); homeFor() = where users land after login
    Auth/LoginOtpController.php       step 2 (code), resend
    LocaleController.php
    StorefrontController.php          home page
    VehicleController.php             select / clear the car
    ProductController.php             public catalogue (parts list, product page)
    DaftraWebhookController.php
    Portal/                           Dashboard, Invoice, Payment, Account (client side)
    Admin/                            Dashboard, Product, Client, Invoice, Payment, Webhook, Sync, User,
                                      Category, Brand, CarModel, Tag
bootstrap/app.php                     middleware aliases, CSRF exception, redirects
config/services.php                   'daftra' settings
database/migrations/                  products, clients, invoices, payments, webhook_logs, sync_runs, users role,
                                      brands, car_models, fitments, categories, tags + link tables
database/seeders/CatalogSeeder.php    brands/models/categories/tags + links from product names
database/seeders/CarBrandsSeeder.php  17 Japanese and Korean brands with logos
database/seeders/logos/*.png          brand logo files used by CarBrandsSeeder
public/images/hero-aisle-light.jpg    home page hero background
routes/web.php                        all routes
routes/console.php                    daftra:sync, portal:create-admin, schedule
resources/css/app.css                 Tailwind theme, brand colours, fonts, .card/.btn/.input/.table
resources/js/app.js                   Alpine.js
resources/views/
  components/layouts/app.blade.php    main layout (admin sidebar; clients/guests include the storefront layout)
  components/layouts/partials/store.blade.php  storefront header, menus, account tabs, footer
  components/layouts/auth.blade.php   login + login code pages
  storefront/home.blade.php           home page
  components/                         vehicle-finder, product-card, brand-logo, brand-badges, category-image, tag-badges…
  components/                         icon, money, stock-badge, invoice-status, stat-card, tables, sync button…
  auth/login.blade.php, auth/otp.blade.php
  products/                           catalogue + product details
  portal/                             client dashboard, invoices, payments, account
  admin/                              dashboard, products, clients, invoices, payments, webhooks, sync, users
lang/ar.json, lang/ar/*.php           Arabic translations
tests/Feature/                        Pest tests (catalogue, brands, categories, fitments, tags, storefront, seeders, login code, SKU mask)
```

---

## 12. Client orders (order requests)

The portal never writes to Daftra. Clients send **order requests**; the admin creates the real invoice in Daftra by hand, and the synced invoice is then linked back to the order.

```
Client: Products → "Add to order" → My Order (cart) → Send order
Admin:  Orders → open order → "Mark as processing" → create invoice in Daftra
        → Sync → select the synced invoice → "Link invoice & complete"
Client: sees status + link to the real Daftra invoice
```

### Statuses

| Status       | Meaning                                                        | Who sets it            |
|--------------|----------------------------------------------------------------|------------------------|
| `draft`      | The client's cart (one per client user, not visible to admin) | automatic              |
| `submitted`  | Sent by the client, waiting for the admin ("New")             | client (Send order)    |
| `processing` | Admin is creating it in Daftra                                 | admin                  |
| `completed`  | Daftra invoice linked                                          | admin (link invoice)   |
| `cancelled`  | Cancelled                                                      | admin, or client while still `submitted` |

Admins can also **reopen** a completed/cancelled order and leave a **message to the client** shown on the order page.

### Rules
- Out-of-stock products can't be added; quantities are capped at Daftra's stock (untracked products are unlimited).
- Prices are copied onto each item and refreshed from the latest synced prices when the order is sent. The total is an **estimate**; the Daftra invoice is final.
- Item name/SKU are stored on the item, so orders stay readable even if a product is later removed from Daftra.
- Only invoices of the **same client** (and not already linked to another order) can be linked.
- Clients only see their own orders (others return 404); admins can't use the cart.
- Sidebar badges: items in the client's cart, and new (`submitted`) orders for the admin. The admin dashboard shows a "new orders waiting" banner.

### Database

| Table         | Columns                                                                                                   |
|---------------|-----------------------------------------------------------------------------------------------------------|
| `orders`      | `client_id`, `user_id`, `status`, `notes` (client), `admin_notes` (message to client), `invoice_id` (linked Daftra invoice), `estimated_total`, `submitted_at`, `completed_at` |
| `order_items` | `order_id`, `product_id`, `daftra_product_id`, `product_name`, `sku`, `price`, `quantity`                  |

### Routes

| Method | URI                              | Who    | Purpose                         |
|--------|----------------------------------|--------|---------------------------------|
| GET    | `/cart`                          | client | My Order (draft)                |
| GET    | `/cart/summary`                  | client | JSON for the header mini-cart   |
| POST   | `/cart/items`                    | client | Add product                     |
| PATCH/DELETE | `/cart/items/{item}`       | client | Change quantity / remove        |
| POST   | `/cart/submit`                   | client | Send order                      |
| GET    | `/orders`, `/orders/{order}`     | client | Sent orders + status            |
| POST   | `/orders/{order}/cancel`         | client | Cancel while still submitted    |
| GET    | `/admin/orders`, `/admin/orders/{order}` | admin | Orders list / details     |
| PATCH  | `/admin/orders/{order}`          | admin  | processing / complete / cancel / reopen / note |
| GET    | `/admin/orders/{order}/invoices?q=` | admin | JSON for the Select2 invoice search (client's unlinked invoices) |

### Files
`app/Models/Order.php`, `app/Models/OrderItem.php`, `app/Http/Controllers/Portal/CartController.php`, `app/Http/Controllers/Portal/OrderController.php`, `app/Http/Controllers/Admin/OrderController.php`, views in `resources/views/portal/orders/` and `resources/views/admin/orders/`, components `add-to-order`, `order-status`, `order-timeline`, `order-items`.

---

## 13. Catalogue: brands, models, categories, tags

Managed by the admin under **Categories**, **Brands**, **Models** and **Tags**; assigned per product on **Products → ✏️ (edit)**. A product can have several of each.

| Item      | Example                       | Notes |
|-----------|-------------------------------|-------|
| Brand     | Toyota, Kia                   | Optional logo (PNG/JPG/WEBP ≤ 2 MB; SVG refused — it can carry scripts). Deleting a brand deletes its models and fitments, not its products. |
| Model     | Kia → Cerato                  | Belongs to one brand; name unique per brand. |
| Fitment   | Kia Cerato 2015–2017          | Product edit page → "Fits vehicles": model + from/to year (1980 … next year, empty = no limit). Saving a fitment also adds the model's brand to the product. |
| Category  | الفرامل, المرايات              | Optional image for the home page grid. Replaces Daftra's category in the store. |
| Tag       | أصلي, تجاري كوري              | Coloured badge on product cards; "Type" filter in the store. |

### Catalog seeder (`database/seeders/CatalogSeeder.php`)

Reads every product **name** and links it — no random data:

- **Category:** the category whose part word appears **first** in the name (e.g. "كف مرايا …" → المرايات, "تيل خلفي …" → الفرامل). Generic words (خرطوم, غطاء, …) are used only when nothing specific matches. Products with no known part word go to **أخرى** (Other); they leave it automatically when a later run finds a real category.
- **Models and years:** Arabic/English spellings per model (النترا / ELANTRA, سبورتاج, كورولا, صني, …). A year after the model gives that year ("النترا 2015" → 2015–2015), a range gives the range ("اكسنت 2012-2015"), "2018-" means 2018 onwards, no year = all years. Several cars in one name → one fitment each.
- **Generation codes** → year ranges: `HD` Elantra 2007–2010, `MD` 2011–2015, `AD` 2016–2020, `CN7` 2021+, `RB` Accent 2011–2018, `HCI` 2018+, `NX4` Tucson 2021+, `NQ5` Sportage 2022+, `K3` Cerato 2013–2018 (please verify for your market).
- **Brand:** from the matched models, and from brand names written in the name (كيا, تويوتا, …).
- **Tag:** أصلي when Daftra's category is `oem` or the name says اصلي / موبيس / MOBIS; تجاري كوري / صيني / تايواني from words in the name.
- **Safe to re-run:** only adds what's missing; never removes links made by the admin.
- **Edit the lists** (models, spellings, codes, part words) at the top of the file and run it again.

Brands: **17** — Korean (Hyundai, Kia, SsangYong, Daewoo) and Japanese (Toyota, Nissan, Honda, Mazda, Mitsubishi, Subaru, Suzuki, Lexus, Infiniti, Acura, Daihatsu, Isuzu, Datsun), with 116 models. Logos come from `CarBrandsSeeder` (files in `database/seeders/logos/`, from github.com/filippofilip95/car-logos-dataset); a logo uploaded in the admin is never replaced.

Result on the current data (5,529 products): every product has a category (51 in أخرى), 5,424 have a brand, 5,416 a model + years.

---

## 14. Storefront (clients and guests)

Layout modelled on speero.net (structure only — own logo, colours, texts and images).

- **Header:** logo, part search, **Select your car** (brand → model → year; remembered in the session by `App\Support\Garage`, shown as e.g. "Kia Cerato 2016"), account menu (clients) or Login (guests), cart (clients). Second row: All parts, Categories and Brands menus, language switcher. Mobile: menu drawer.
- **Home (`/`):** hero with background photo (`public/images/hero-aisle-light.jpg`) and the car finder (or search by part name/number); stats strip; **brand slider** (all brands, most parts first, arrows, auto-scroll that pauses on hover, swipe, RTL-aware); category grid (with images); latest parts; "Don't know the part name?" banner.
- **Parts list (`/products`):** car selector bar; compact filter box (available only, category, brand with logos, type/tags; long lists behind "Show more"); removable filter chips; sort; product cards (photo, tags, name, masked SKU, price, add to order). Changing the brand in the filter clears the model/year; a model that doesn't belong to the chosen brand is ignored.
- **Product page:** photo, details (categories, brands), **part compatibility** table (brand, model, years); buy box with "Fits your … / Does not fit your …" check for the selected car, tags, price, stock, add to order; related parts (same categories).
- **Client account pages** (dashboard, order, orders, invoices, payments, account) use the same layout with account tabs under the header.

---

## 15. Customer login code (OTP)

1. Customer enters email + password → password is checked but they are **not logged in yet**.
2. "Enter your code" page (`/login/code`) → 6-digit code; typing the 6th digit submits.
3. Correct code → logged in → store home page (or the page they were trying to open).

- Code expires after **5 minutes**; after **5 wrong codes** the customer must sign in again; "Resend code" is limited to 3 per minute.
- The pending login lives in the session (code stored hashed); nothing is logged in until the code is right.
- **For now every code is `123456`** (`LOGIN_OTP_FIXED_CODE`); the code is not shown on the page.
- Admins don't get a code. `LOGIN_OTP_ENABLED=false` turns the step off.
- Code: `app/Services/LoginOtp.php` (settings in `config/auth.php` → `login_otp`), `app/Http/Controllers/Auth/LoginOtpController.php`, `resources/views/auth/otp.blade.php`.

**Before going live:** add the real sending (SMS to the client's phone or email) in `LoginOtp::send()` and remove `LOGIN_OTP_FIXED_CODE` — codes then become random per login.

---

## 16. SKU masking

Everyone except admins sees part numbers masked: `58101-2HA10` → `58••••••A10` (first 2 and last 3 characters; shorter SKUs show less). Applies to product cards, product page, cart and order pages. Search still matches the full SKU; orders still store the full SKU for the admin and Daftra. Code: `app/Support/SkuMask.php`.

---

## 17. What was done — change log

1. Read the official Daftra API v2 docs; confirmed endpoints, auth, pagination, product/stock, client, invoice, payment and webhook formats; listed the limitations above.
2. Configured `.env` for MySQL (`elsa3d`), Arabic default locale, and Daftra settings.
3. Created migrations and models for products, clients, invoices, payments, webhook logs, sync runs, and user roles.
4. Built `DaftraService` (HTTP client with retries/timeouts/error handling, full sync, single-record refresh, balance calculation).
5. Built the webhook endpoint with token protection, logging and API re-fetch.
6. Built login/logout, role middleware, language switching.
7. Built the client portal (dashboard, invoices, payments, account) and the public product catalogue with search, category and stock filters.
8. Built the admin area (dashboard, products, clients, invoices, payments, webhooks, sync, users).
9. Added Arabic translations and full RTL support.
10. Added `daftra:sync` and `portal:create-admin` commands and the 15-minute schedule.
11. Verified end-to-end with simulated Daftra responses: sync, re-sync without duplicates, webhooks, every page per role, access control, Arabic/English.
12. Ran migrations on the MySQL `elsa3d` database.
13. Switched the Arabic font to **Cairo**, bundled with both the `arabic` and `latin` subsets (`subsets: ['arabic', 'latin']` in `vite.config.js` — without the Arabic subset, Arabic text silently falls back to the system font).
14. Replaced Laravel's default pagination with a custom view matching the design (`resources/views/components/pagination/links.blade.php`, set as default in `AppServiceProvider`): rounded buttons, brand-coloured current page, RTL-aware arrows, translated "Showing X–Y of Z", compact "Page X of Y" on mobile.
15. Created the **ال سعد** (Al Saad — Auto Parts) brand: gear mark with an amber hub (`components/logo-mark.blade.php`), full lockup with Arabic wordmark and tagline "قطع غيار السيارات" (`components/logo.blade.php`), browser icon `public/favicon.svg`, standalone logo `public/images/logo.svg`. `APP_NAME="Al Saad"`, shown as "ال سعد" in Arabic.
16. Added **client order requests**: cart (draft order), send order, order status page; admin orders list, processing, link synced Daftra invoice, cancel/reopen, message to client; sidebar badges and dashboard banner. Also fixed Arabic plural forms (Arabic has 6).
17. Added **Select2** searchable dropdowns (`resources/js/select2.js`). Any `<select data-searchable>` becomes a Select2; add `data-ajax-url` to load options from a JSON endpoint (`{ results: [{id, text}], pagination: { more } }`) and `data-placeholder` for the placeholder. Styled in `app.css` to match the inputs, RTL-aware, messages translated. Used for linking a Daftra invoice on the admin order page (AJAX search over all of the client's invoices, 20 per page) and for choosing the Daftra client in the admin user form (local search by name/email).
    - jQuery is pinned to v3 (Select2 4.1 uses helpers removed in jQuery 4).
    - Don't use `data-select2` as the marker attribute — Select2 reads it as its own internal data.
    - Select2 only fires jQuery events, so it re-dispatches a native `change` event — this keeps Alpine `x-model` / `@change` working on the same `<select>`.
    - Select2 4.1 crashes on AJAX results when bundled (strict mode); `processResults` sets `_resultId` on each item to avoid it.
18. Added **invoice PDFs** (`GET /invoices/{invoice}/pdf`, `?download=1` to download; admins any invoice, clients only their own). Buttons on invoice pages and a "PDF" link in every invoice table.
    - Daftra documents `invoice_pdf_url` (hashed link, stored as `invoices.pdf_url`, hidden from JSON). Opening it redirects to Daftra's client-area invoice page — not a PDF.
    - With the session that link creates, `/client/invoices/view/{id}.pdf` returns the real PDF. This path is **not in Daftra's API docs**, so `DaftraService::downloadInvoicePdf()` checks the response really is a PDF; if not, the portal redirects to the documented `invoice_pdf_url` instead.
    - PDFs are fetched on demand (Daftra takes ~7–10 s to generate one) and not stored. Rate-limited to 30/minute per user.
    - Invoices synced before this change get their link fetched from the API on first use; the next full sync stores all links.
19. **Add to order without reloading** + **header cart** (clients):
    - "Add to order" submits with `fetch` (`resources/js/cart.js`, Alpine component `addToOrder`); `POST /cart/items` returns JSON when the request wants JSON (count, total, items, message) and still redirects for non-JS form posts.
    - Header cart button (`components/header-cart.blade.php`), always on the right side of the header (both languages), with a live count badge and a dropdown mini-cart (items, estimated total, "Review & send order"), loaded from `GET /cart/summary` when opened. The sidebar "My Order" badge uses the same live count (Alpine store `cart`).
    - Pop-up messages (`components/toasts.blade.php`, Alpine store `toast`): green for added, amber when the quantity was capped at the available stock, red for errors.
20. **Catalogue sort order** — Admin → **Settings** (`/admin/settings`):
    - Default order for clients: Name A→Z / Z→A, Price low→high / high→low, Availability: in stock first (available → low → out, then name), Availability: most stock first (untracked stock counts as unlimited).
    - "Let clients change the sort order": when on, the products page shows a "Sort by" menu starting at the admin's default (`?sort=`); when off, the menu is hidden and `?sort=` is ignored.
    - Sorting lives in `Product::scopeSorted()`; options in `Product::SORTS`.
21. **Correct Arabic alphabetical order**: products have a hidden `sort_name` (set automatically on every save/sync by `Product::sortKey()`):
    - Arabic names first, then Latin, then names starting with numbers/symbols.
    - Leading punctuation ignored — "(يمين) غماز" sorts under ي.
    - Spelling variants unified for sorting: أ إ آ ٱ → ا, ى → ي, ة → ه; tatweel and diacritics removed.
    - Z→A reverses the letters but still keeps Arabic first and numbers/symbols last.
    - The displayed name is unchanged.
22. **Brands** (many per product) with logos; admin pages to manage them and assign them to products.
23. **Categories** (local, many per product) with images; they replace Daftra's category in the store (Daftra's value is kept). New category `أخرى` for unmatched products.
24. **Car models and fitments**: models per brand; products linked to model + year range; "Select your car" finder (brand → model → year) in the store; compatibility table and "fits your car?" check on the product page.
25. **Tags** with colours (أصلي, تجاري كوري, …) shown as badges and used as the "Type" filter.
26. **Storefront redesign** for clients and guests (header, menus, home page, parts list with filter sidebar, product page, footer); admins keep the sidebar layout. Client account pages moved into the store layout with account tabs.
27. **Catalog seeder** linking all products to categories, brands, models/years and tags from their names; **17 Japanese and Korean brands with logos** and 116 models.
28. Home page: brand **slider** above the categories (all brands, most parts first); hero **background photo**; stats.
29. Parts list: **brand filter** with logos and counts; compact filter box that fits on screen; checkboxes/radios in the theme colour.
30. **Theme colour #ef4444** everywhere, including the logo (red gradient).
31. **Customer login code** (OTP) after the password; fixed code `123456` for now; customers land on the home page after login.
32. **SKU masking** for everyone except admins.
33. `APP_URL` set to `http://elsa3d.test` locally (logos were broken with `http://localhost:8000`); `storage:link` created.
34. **Automated tests** (Pest) for all of the above — 81 tests.

### Still to do

- Put the real `DAFTRA_API_BASE_URL` and `DAFTRA_API_TOKEN` in `.env` and run the first sync.
- Create the admin account (`php artisan portal:create-admin`).
- After the first real sync, check that product images appear (image field format isn't documented by Daftra).
- When deployed on HTTPS: register the webhooks in Daftra App Hub and add the scheduler cron entry.
- On the server: set `APP_URL` to the real domain, run `php artisan storage:link` and `php artisan db:seed --class=CatalogSeeder`.
- **Login code:** choose SMS or email, add it in `LoginOtp::send()`, remove `LOGIN_OTP_FIXED_CODE`.
- **Category images:** upload one per category in Admin → Categories (home page shows placeholders until then).
- Check the generation-code year ranges in `CatalogSeeder` for your market; widen single-year fitments where a part fits more years.
- Decide on Chevrolet (Optra, Aveo — 11 products without a brand) and universal parts ("الكل").
- After Japanese parts are synced from Daftra, run the catalog seeder again to link them.
