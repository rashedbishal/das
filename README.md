# DAS — Dental Product Catalog Platform

A CMS-driven product catalog for dental equipment, instruments, and supplies. This is strictly a **catalog and discovery platform** — there is no cart, checkout, payment, or order management. Customers contact the business directly to purchase.

Built on Laravel 13 + Inertia v3 + Svelte 5 (the `laravel/svelte-starter-kit`), with a versioned JSON API (`/api/v1`) alongside the Inertia-rendered pages for headless/mobile consumption.

## Stack

- PHP 8.3, Laravel 13.36
- Inertia Laravel v3 + Inertia Svelte v3, Svelte 5, Tailwind v4, bits-ui components
- Laravel Fortify (session-based auth), Laravel Sanctum (stateful SPA auth for `/api/v1`)
- Laravel Wayfinder (typed TS route/action helpers)
- MySQL (configurable), Pest 4, Pint, Larastan

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env` (defaults to MySQL; set `DB_CONNECTION=sqlite` for a quick local SQLite setup instead) and run:

```bash
php artisan migrate
php artisan db:seed            # seeds the permission catalog, system roles, and default settings
php artisan storage:link       # required for product media URLs to resolve
php artisan das:bootstrap-mother-admin
npm run build                  # or `npm run dev` / `composer run dev` during development
```

### Importing the Client Catalog

After migrations, run `php artisan catalog:import-client-data` to replace the current products, categories, subcategories, brands, and tags with the reviewed client catalog. The command asks for confirmation because it deletes existing catalog records; `--force` skips that prompt for an intentional, unattended import. It preserves user accounts, homepage sections, and other settings, while setting the documented catalog delivery note.

The import contains 15 products, 81 product options, and bundled photos under `public/images/client-catalog/`. Products are assigned to **Instrument** directly or to **Material** → **Porceline**, **Denture**, or **cad-cam-material**, as appropriate. Prices are left unset where the source documents do not provide them, so the storefront displays **Contact for price**. Documented sizes and shades are imported as product options with sequential `DAS-0001` variant SKUs and **On order** stock status. No products are marked as featured because the source documents do not identify a featured selection; admins can curate this in the CMS. The duplicate `Pro Porcelain.doc` document is not imported as a separate product.

### Bootstrapping the Mother Admin

`php artisan das:bootstrap-mother-admin` creates the single highest-privilege account. It is idempotent — it refuses to run if a Mother Admin already exists. Credentials can be supplied via environment variables (`MOTHER_ADMIN_NAME`, `MOTHER_ADMIN_EMAIL`, `MOTHER_ADMIN_PASSWORD`) or entered interactively if left unset. The command also seeds the permission/role catalog automatically if it hasn't been seeded yet.

## Authentication Architecture

- **One `users` table, one session (`web`) guard** for both admins and customers, distinguished by `user_type` (`admin`/`customer`). Public registration always creates a customer — admin accounts can only be created by another admin holding the `admins.manage` permission.
- **Customer login identifier** (email, phone, or both) is configurable from **Settings → Customer authentication** without a deploy. The mode is enforced on both registration and login; admins can always sign in by email regardless of the customer-facing mode.
- **Bangladeshi phone numbers** are normalized to local `01[3-9]XXXXXXXX` format (`App\Support\PhoneNumber`) before validation, storage, and lookup, so `+8801...`, `8801...`, and `01...` all collide as the same identifier and can't be used to create duplicate accounts.
- **`/api/v1`** uses Laravel Sanctum in _stateful SPA_ mode — the same session cookie from the Inertia login authenticates JSON requests from the same origin; no bearer tokens are issued or required for this frontend.

## Role & Permission Model

Native `roles` / `permissions` tables (no third-party package), chosen so the Mother Admin safeguards and anti-escalation rules below could be enforced precisely:

- **Mother Admin** is a single, seeded, system-protected role (`roles.is_system = true`). It can never be edited, deleted, demoted, or deactivated through ordinary admin operations — those actions are blocked at the policy _and_ service layer (`App\Services\RoleService`, `App\Services\AdminUserService`), not just the UI.
- **Ownership transfer**: the only way the Mother Admin role moves to another admin is `AdminUserService::transferMotherAdmin()`, which requires the current Mother Admin's password and demotes them to a fallback role (`read-only-admin`) atomically.
- **Anti-escalation**: an admin can only grant a role, or assign permissions, that they already hold themselves (checked permission-by-permission). The Mother Admin bypasses this check; nobody else can grant the Mother Admin role directly — only via the transfer flow above.
- Seeded example roles: `product-manager`, `catalog-editor`, `content-manager`, `customer-manager`, `seo-manager`, `read-only-admin` — all fully custom/editable (unlike Mother Admin). Permission catalog (group → slugs) is seeded by `Database\Seeders\RolePermissionSeeder` and can be inspected via `GET /api/v1/admin/roles/permissions`.

## Product Detail Access Rule

Enforced on the backend (not just the frontend), on **both** the Inertia routes and the `/api/v1` routes:

- Guests may browse product/category/brand listings (summary fields only — never full description, specifications, or SEO fields; see `App\Http\Resources\ProductCardResource`).
- Opening a product detail page as a guest never navigates away or reveals whether the slug exists (Inertia route): the page renders in place with `authRequired: true` and `product: null`, and the Svelte page shows an inline login modal over it. The API instead returns `401`. Either way, logging in returns the user to the product they requested (`session(['url.intended' => ...])` set by the controller, consumed by Fortify's `redirect()->intended()`).
- Authenticated customers see full detail only for **published** products; admins see all statuses. Non-public statuses return `404` rather than `403` for authenticated non-admins, to avoid confirming a draft/archived product's existence.

## Media & Storage

Product images/videos live in one `product_media` table (`type`: image/video, `source`: upload/url). Uploads are validated by MIME/extension allow-list and size (`App\Http\Requests\Admin\Product\StoreProductMediaRequest`), stored on the `public` disk under `products/{id}/` with Laravel-generated random filenames (never the original filename), and served via the storage symlink — internal paths are never exposed. Setting a new main image automatically unsets the previous one.

## CMS Settings

All settings live in one `settings` table (`group`, `key`, `value` as JSON) behind a cached `App\Services\SettingsService`, editable from **Admin → Settings**:

| Group       | Keys                                                                                                             |
| ----------- | ---------------------------------------------------------------------------------------------------------------- |
| `general`   | site_name, logo, favicon, contact_email, contact_phone, address, currency, timezone, social_links                |
| `auth`      | login_mode (`email`\|`phone`\|`both`), registration_enabled                                                      |
| `whatsapp`  | enabled, phone, message                                                                                          |
| `messenger` | enabled, page_url                                                                                                |
| `catalog`   | page_size, featured_limit, delivery_note (shown on all product detail pages)                                     |
| `seo`       | default_title, default_description                                                                               |
| `tracking`  | head_scripts, body_scripts (raw JS/HTML, e.g. Google Tag Manager — injected into every page's `<head>`/`<body>`) |

WhatsApp/Messenger config is shared to every Inertia page as `contactWidgets` and exposed publicly at `GET /api/v1/settings/contact-widgets` (and the Inertia-side `GET /contact-widgets`) for the floating widget component — the frontend never hard-codes a phone number or page URL.

## Admin Bulk Actions

The CMS list screens support selecting visible rows (up to 100 per request). Products can be published, moved to draft, have customer-facing price or stock visibility toggled, or be deleted. Hidden price and stock fields are omitted from public product responses and filters while their values remain stored for later display. Categories and subcategories, brands, and homepage sections can be activated, deactivated, or deleted; tags can be deleted. In **Users & Roles**, admins can be deactivated, reactivated, or deleted, customers can be deactivated or reactivated, and non-system roles can be deleted. Existing permission, protected-account, and relationship constraints also apply to bulk actions.

## API Reference (`/api/v1`)

All admin endpoints require the `auth:sanctum` + `admin` middleware and the policy-gated permission named in parentheses.

**Public**: `GET products`, `GET products/{slug}` (auth required, 401 otherwise), `GET categories`, `GET categories/{slug}`, `GET brands`, `GET brands/{slug}`, `GET tags`, `GET homepage`, `GET settings/contact-widgets`.

**Auth**: `POST auth/register`, `POST auth/login` (body: `identifier`, `password`), `POST auth/logout`, `GET auth/user`.

**Admin**: `apiResource admin/products` (+ `POST`/`DELETE admin/products/{product}/media/{media}`), `admin/categories`, `admin/brands`, `admin/tags` (`*.manage`/`*.view`), `admin/roles` (+`GET admin/roles/permissions`, `POST admin/roles/bulk`), `admin/admins` (+ `POST .../deactivate|reactivate`, `POST admin/admins/bulk`), `admin/customers` (+ `POST admin/customers/bulk`), `admin/settings` (`settings.manage`/`settings.view`, or `seo.manage` for the catalog/SEO group).

## Known Gaps / Left for the Frontend Developer

The backend and API for all of the following are complete and tested; only the Svelte UI is intentionally minimal or deferred, per the "minimal frontend" brief:

- Product specifications and related-product editing have no dedicated UI yet (API/Inertia endpoints accept `specifications[]` and `related_product_ids[]` arrays already).
- Public brand browsing pages (`/api/v1/brands`, Inertia `Catalog\BrandController`) exist but have no Svelte pages; only category pages were built per the brief's explicit page list.
- No image upload UI for brand images — these accept a plain URL string for now. (Category/subcategory images now support file upload, stored on the `public` disk via `CategoryService`/`SubcategoryService`, alongside the existing URL-string option.)

## Running Tests

```bash
php artisan test --compact          # full suite
php artisan test --filter=Admin     # scoped
vendor/bin/pint --dirty             # formatting
vendor/bin/phpstan analyse          # static analysis (level 7)
npm run check                       # svelte-check + eslint, if configured
npm run build                       # production asset build
```

The suite covers: Mother Admin bootstrap/idempotency, role/permission anti-escalation, all three customer login modes with Bangladeshi phone validation and duplicate-normalization, the product-detail access rule (guest redirect, customer/admin visibility by status) on both Inertia and API routes, full catalog CRUD + media upload + search/filter/sort/pagination, settings/homepage-section management, contact-widget exposure, and targeted mass-assignment/IDOR checks.

## Deployment Notes

- Set `APP_ENV=production`, `APP_DEBUG=false`, and a strong `APP_KEY`.
- Run `php artisan storage:link` on first deploy (product media depends on it).
- Run `php artisan das:bootstrap-mother-admin` once, with `MOTHER_ADMIN_*` env vars set non-interactively in your deploy pipeline — never commit real credentials.
- `SANCTUM_STATEFUL_DOMAINS` must include your production domain for `/api/v1` session auth to work from the deployed frontend.
- No CORS policy is published (`config/cors.php` doesn't exist) because the frontend and API are same-origin by design; only publish and restrict it if you later add a genuinely cross-origin consumer.
