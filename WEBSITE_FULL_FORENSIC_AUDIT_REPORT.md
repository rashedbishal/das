# DAS — Website Full Forensic Audit Report

**Audit date:** 2026-11-15
**Commit audited:** `ef0be96` (`main`, in sync with `origin/main`)
**Audit mode:** Read-only. No application source, schema, data, packages or configuration were modified. The only file written is this report.
**Business model evaluated:** Non-transactional product catalogue + CMS. Missing cart/checkout/orders/payments are **not** treated as defects.

---

## 0. Method & Evidence Base

| Activity         | What was actually done                                                                                                                                                                                                                                                                                                   |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Code inspection  | All routes (`web.php`, `admin.php`, `api/v1.php`), middleware, Fortify/auth wiring, policies, all product/media/category/banner/settings services & requests, API controllers & resources, storefront data layer (`resources/js/data/catalogue.ts`, `lib/api.ts`), key storefront pages/components, Blade root template. |
| Database         | Live schema of local MySQL read via Laravel Boost (`database-schema`), plus read-only `SELECT` queries.                                                                                                                                                                                                                  |
| Automated checks | `php artisan test`, `phpstan`, `pint --test`, `npm run check` (vp check), `composer audit`, `npm audit --omit=dev`. Tests run on in-memory SQLite (verified in `phpunit.xml`), never touching MySQL.                                                                                                                     |
| Runtime probing  | A temporary `php artisan serve` on port 8123 against the local DB; **GET requests only**; stopped afterwards.                                                                                                                                                                                                            |
| Not performed    | No Lighthouse / Core Web Vitals measurement (no browser tooling run), no load tests, no write requests against the running app, no destructive DB operations. Anything in those areas is labelled **Potential** or given as a test design.                                                                               |

### Baseline results (actual)

| Check                      | Result                                                                                                                                                                                                    |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Pest                       | **222 passed, 1 skipped**, 667 assertions, ~6.5 s                                                                                                                                                         |
| PHPStan (level 7)          | **0 errors**                                                                                                                                                                                              |
| Pint                       | **Pass**                                                                                                                                                                                                  |
| `npm run check` (vp check) | 0 errors, **1 warning** → **exit code 1** (so `composer run ci:check` fails as-is) — `resources/js/lib/api.ts:44` `no-misused-spread`                                                                     |
| `composer audit`           | **12 advisories / 7 packages** — `laravel/framework` 13.36.0 (low, fixed ≥13.40.0); `symfony/http-foundation`, `http-kernel`, `routing`, `mime` (medium), `serializer`, `yaml` (fixed in 7.4.23 / 8.1.11) |
| `npm audit --omit=dev`     | 2 low (`deepmerge` via `@sveltejs/vite-plugin-svelte`, build-time only, no fix available)                                                                                                                 |

---

## 1. Executive Summary

DAS is a well-structured, unusually disciplined Laravel 13 + Inertia v3 + Svelte 5 codebase: thin controllers, a real service layer, policies on every admin action, a carefully designed anti-escalation role model, passing static analysis, and 222 green tests. The admin CMS is functionally broad (products, media, categories/subcategories, brands, tags, banners, enquiries, settings, tracking scripts, admin users/roles).

However, it is **not production-ready**. The most important problems are not code-quality problems; they are **gaps between what the tests exercise and how the real stack behaves**:

1. **The catalogue is largely invisible to search engines** (P0 for a business whose goal is discovery): product detail pages require login and render "Sign in to view this product" to every crawler; listing pages contain no product data in the server-rendered HTML; per-product SEO fields and global SEO defaults stored in the CMS are never used; no sitemap, no structured data, and unknown slugs return HTTP 200.
2. **Validation allows values MySQL will reject.** Several fields validate up to 500–2048 characters but the MySQL columns are `VARCHAR(255)` in strict mode. A normal admin action (e.g. a 300-character short description) causes an HTTP 500. The test suite runs on SQLite, which ignores lengths, so this is invisible to CI.
3. **Authorization gap:** an admin with `products.update` (but not `products.publish`) can publish or archive products through the normal edit form; the `publish` permission is only enforced in bulk actions.
4. **Privilege-escalation path through tracking scripts:** any admin holding `settings.manage` can inject arbitrary JavaScript that executes inside the Mother Admin's authenticated admin pages.
5. **Storefront reliability:** API failures leave infinite loading states; search fires an unthrottled request per keystroke with race conditions; category/brand pages issue one API call per category/brand.
6. **Unconfirmed draft leak & data hygiene:** related products are returned without a `published` filter; soft-deleted products keep their media files forever; images uploaded for categories store an absolute URL containing the current `APP_URL` (`http://192.168.0.111:8000`).

**Verdict: NOT READY for production.** Roughly 2–3 focused engineering weeks (P0+P1 below) would make it launch-ready.

---

## 2. Project Architecture Overview

```
Browser
 ├─ Storefront (pages/storefront/*)  ── Inertia shell (SSR-capable) ──► Laravel web routes (web.php)
 │      └─ data fetched CLIENT-SIDE via fetch() ──► /api/v1/* (public JSON, Sanctum stateful)
 ├─ Customer auth  /login, /register (Fortify)          ─┐
 ├─ Admin auth     /admin/login (Fortify pipeline)       ├─ one `users` table, `web` session guard
 └─ Admin CMS      /admin/* + /dashboard (Inertia props)─┘   user_type = admin|customer
                         │
                Controllers (thin) → Services (ProductService, ProductMediaService, CategoryService,
                BannerImageService, HomepageSectionService, SettingsService, RoleService, AdminUserService)
                         │
                Policies (permission slugs via native roles/permissions tables)
                         │
                MySQL 8.0.46 · database cache · database sessions · database queue · local `public` disk
```

- **Catalogue tree:** `Category → Subcategory (nullable on product) → Product` (+ brands, tags, specifications, variants, related products, media).
- **CMS settings:** `settings(group,key,value JSON)` behind `SettingsService` (cached forever, invalidated on write).
- **Product detail access rule (business decision):** guests can list products but cannot view detail; detail requires any logged-in account.
- **Parallel `/api/v1/admin/*` JSON API** mirrors most admin CRUD.

## 3. Technology Stack & Versions (verified)

| Layer      | Version                                                                                                                |
| ---------- | ---------------------------------------------------------------------------------------------------------------------- |
| PHP        | 8.3.36 (CLI), extensions: GD, intl, PDO (mysql/pgsql/sqlite), OPcache present; **no Redis/Imagick**                    |
| Laravel    | 13.36.0                                                                                                                |
| Inertia    | `@inertiajs/svelte` ^3, inertia-laravel v3; SSR **enabled** in `config/inertia.php` (`127.0.0.1:13714`)                |
| Svelte     | ^5.16 (runes used throughout)                                                                                          |
| Build      | Vite 8 via `vite-plus` 0.3.0, Tailwind 4.1, bits-ui 2.15                                                               |
| Auth       | Fortify (registration, password reset, email verification; **no 2FA**), Sanctum (stateful SPA)                         |
| DB         | **MySQL 8.0.46** (local), `sql_mode` includes `STRICT_TRANS_TABLES`; Laravel `strict => true`                          |
| Tests      | Pest 4 on SQLite `:memory:`                                                                                            |
| Node       | 22.22.3                                                                                                                |
| Local data | 16 products (1 soft-deleted), 19 media rows, 2 categories, 3 subcategories, 2 brands, 5 banners, 45 users, 4 enquiries |

## 4. Overall Application Health

| Area                    | Health    | One-line reason                                                               |
| ----------------------- | --------- | ----------------------------------------------------------------------------- |
| Code structure          | Good      | Service layer, policies, typed props, PHPStan L7 clean                        |
| Correctness vs. real DB | **Poor**  | Validation/column length mismatches → 500s on MySQL only                      |
| Authorization           | Fair      | Strong role model, but publish bypass + script-injection escalation           |
| SEO / discoverability   | **Poor**  | Products unindexable; CMS SEO fields unused                                   |
| Storefront resilience   | Fair/Poor | No error handling on most fetches; request races                              |
| Ops readiness           | Poor      | No deployment config, security headers, SSR process docs, backups, monitoring |

---

## 5–13. Findings

Severity → Priority mapping: Critical=P0, High=P1, Medium=P2, Low/Info=P3 (with noted exceptions).
**Status** values: **Confirmed** = verified at runtime, from DB schema, or by an unambiguous code path; **Potential** = plausible but not reproduced.

### 5. Backend Findings

#### BE-01 — Validation limits exceed MySQL column lengths → HTTP 500 on legitimate admin input

- **Severity:** High (P1) · **Category:** Bug / Data integrity · **Status:** Confirmed (schema vs. rules; MySQL strict mode verified)
- **Affected:**

| Field                                         | Validation                                                        | MySQL column   |
| --------------------------------------------- | ----------------------------------------------------------------- | -------------- |
| `products.short_description`                  | `max:500` (`StoreProductRequest.php`, `UpdateProductRequest.php`) | `varchar(255)` |
| `products.seo_description`                    | `max:500`                                                         | `varchar(255)` |
| `products.canonical_url`                      | `max:2048`                                                        | `varchar(255)` |
| `product_media.url`                           | `max:2048` (`StoreProductMediaRequest.php`)                       | `varchar(255)` |
| `homepage_sections.link_url`                  | `max:2048` (`Store/UpdateHomepageSectionRequest.php:30/29`)       | `varchar(255)` |
| `brands.logo`, `brands.website_url`           | `max:2048` (`StoreBrandRequest.php:24,26`)                        | `varchar(255)` |
| `categories.image` (and likely subcategories) | `max:2048` (`StoreCategoryRequest.php:25`)                        | `varchar(255)` |

- **Explanation:** With `STRICT_TRANS_TABLES` + Laravel `strict => true`, inserting 256+ chars raises `SQLSTATE[22001] Data too long` → unhandled → 500. SEO descriptions of 256–500 chars and CDN image URLs over 255 chars are normal.
- **Repro:** Admin → Products → edit → paste a 300-char short description → Save → 500 error page / Inertia error modal.
- **Why tests miss it:** SQLite ignores `VARCHAR(n)`.
- **Fix:** Either align rules to 255, or (better for SEO/URLs) migrate those columns to `TEXT`/`VARCHAR(2048)`/`VARCHAR(500)`. Add a MySQL CI job.
- **Tests:** Feature tests submitting boundary-length values; run suite against MySQL in CI.

#### BE-02 — `products.publish` permission bypassable via single-product create/update

- **Severity:** High (P1) · **Category:** Broken access control · **Status:** Confirmed (code path)
- **Affected:** `app/Http/Requests/Admin/Product/UpdateProductRequest.php:14-17,33` (`status` accepts `published|archived`, authorize only checks `update`); `StoreProductRequest.php` (same with `create`); `app/Http/Controllers/Admin/ProductController.php::update()`; `Api/V1/Admin/ProductController.php::store/update`. Contrast `ProductController::bulk()` which maps publish/draft → `publish` ability.
- **Repro:** Create role with `products.view`+`products.update` only → edit a draft product → set Status = Published → Save → product is live.
- **Impact:** Editorial control (publish/unpublish) can't be delegated safely.
- **Fix:** In both requests (or `ProductService`), if `status` differs from current (or is non-draft on create), require `can('publish', $product)`; otherwise reject or strip the field.
- **Tests:** "updater without publish permission cannot change status" for Inertia + API.

#### BE-03 — Related products are exposed without a `published` filter

- **Severity:** Medium (P2) · **Category:** Information disclosure · **Status:** Potential (code confirmed; local DB currently has 0 unpublished related rows)
- **Affected:** `app/Http/Controllers/Api/V1/ProductController.php:34` loads `relatedProducts.*` unfiltered; `ProductResource::related_products`; `Product::relatedProducts()`.
- **Impact:** Draft/archived products linked as "related" are shown to customers (name, slug, image, price), i.e. pre-launch leak.
- **Fix:** Constrain eager load: `'relatedProducts' => fn ($q) => $q->published()`.
- **Tests:** Related draft product absent from `/api/v1/products/{slug}`.

#### BE-04 — Category/subcategory uploaded images stored as absolute URLs tied to `APP_URL`

- **Severity:** Medium (P2) · **Category:** Bug / Portability · **Status:** Confirmed (code); local DB has no uploaded category image yet
- **Affected:** `app/Services/CategoryService.php::storeImage()` returns `Storage::disk('public')->url($path)`; `deleteStoredImage()` relies on the same prefix. (Same pattern presumably in `SubcategoryService`.)
- **Impact:** Current `APP_URL=http://192.168.0.111:8000`. Images uploaded now (or on staging) break when moving to the production domain, and `deleteStoredImage()` can no longer recognise them → orphaned files.
- **Fix:** Store relative path; resolve URL at read time (like `ProductMedia::resolvedUrl()`).

#### BE-05 — Product image processing stretches non-square photos to 1200×1200

- **Severity:** Medium (P2) · **Category:** Bug / Media quality · **Status:** Confirmed (code — documented as intentional)
- **Affected:** `app/Services/ProductMediaService.php::storeProcessedImage()` (`imagecopyresampled` to a square with the full source rect).
- **Impact:** Any landscape/portrait product photo is visibly distorted on the storefront — highly damaging for a product catalogue.
- **Fix:** Fit (contain) onto a square canvas with padding/background, or centre-crop like `BannerImageService`.

#### BE-06 — Soft-deleted products keep media files forever; no restore/purge path

- **Severity:** Low (P3) · **Category:** Storage hygiene · **Status:** Confirmed (1 trashed product locally)
- **Affected:** `Product` uses `SoftDeletes`; `ProductService::delete()`; no restore/forceDelete UI or scheduled prune.
- **Impact:** Unbounded growth of `storage/app/public/products/{id}`; SKU/slug of deleted products permanently reserved (SKU unique index includes trashed rows → "SKU already taken" for a product admins can't see).
- **Fix:** Add trash view (restore / permanently delete with file cleanup) or `Prunable` with file deletion; make SKU uniqueness error message mention the trashed product.

#### BE-07 — Hardcoded real contact details as fallbacks

- **Severity:** Low (P3) · **Category:** Config hygiene · **Status:** Confirmed
- **Affected:** `app/Http/Middleware/HandleInertiaRequests.php` (`contact_phone` default `01761158577`, `contact_email` default a personal Gmail, `delivery_note` default).
- **Impact:** Violates the project rule "never hardcode values that belong in settings"; a fresh environment silently publishes personal contact details.
- **Fix:** Seed via `SettingsSeeder` only; default to `null` and hide when unset.

#### BE-08 — Raw model serialization in public category/brand API

- **Severity:** Low (P3) · **Category:** API consistency · **Status:** Confirmed (runtime: `/api/v1/categories` returns `created_at`, `updated_at`, `deleted_at`, `is_active`, `sort_order`)
- **Fix:** Use API Resources consistently (products already do).

#### BE-09 — Deactivated customers keep existing sessions

- **Severity:** Low (P3) · **Category:** Session management · **Status:** Confirmed (code: `is_active` only checked at login and in `EnsureUserIsAdmin`)
- **Fix:** Middleware on `web`/`auth:sanctum` logging out inactive users, or delete their DB sessions on deactivation.

#### BE-10 — Over-eager loading on public product listing

- **Severity:** Low (P3) · **Category:** Performance · **Status:** Confirmed
- **Affected:** `ProductService::paginate()` eager-loads `category, subcategory, tags, media` (all media incl. videos) though `ProductCardResource` uses only `brand` + main image.
- **Fix:** Separate public query: `with(['brand', 'media' => fn ($q) => $q->where('type','image')])`.

### 6. Frontend Findings (Svelte 5 storefront)

#### FE-01 — Unhandled fetch failures → infinite loading / silent blank sections

- **Severity:** High (P1) · **Category:** Reliability · **Status:** Confirmed (code)
- **Affected:** `components/storefront/CatalogueView.svelte:61-67` and `loadMore()` (no `.catch`, `loading` never reset); `pages/storefront/Home.svelte:38`; `Brands.svelte:26-29`; `Header.svelte:148`; `data/catalogue.ts::loadCategories/loadBrands` (memoised promise caches the **rejection** forever → categories never load until full reload).
- **Repro:** DevTools → block `/api/v1/products*` → open `/catalogue` → permanent loading state; console shows unhandled promise rejection.
- **Fix:** `.catch` → error state + Retry; reset memoised promise on failure.

#### FE-02 — Race conditions on filter changes and search

- **Severity:** Medium (P2) · **Category:** Bug · **Status:** Potential (code; not reproduced in browser)
- **Affected:** `CatalogueView.svelte` `$effect` fetch; `Header.svelte:139-151` search `$effect`.
- **Explanation:** No `AbortController`/request sequencing: a slower earlier response can overwrite a newer one (wrong products for the visible filters). Search fires **one request per keystroke** with no debounce, each executing a `LIKE '%term%'` scan.
- **Fix:** Debounce (250–300 ms), abort previous request, or compare a request token before assigning.

#### FE-03 — N+1 HTTP requests for counts/previews

- **Severity:** Medium (P2) · **Category:** Performance · **Status:** Confirmed (code)
- **Affected:** `CatalogueView.svelte:70-78` — one `/api/v1/products?per_page=1` call **per category** to get a count; `Brands.svelte:26-29` — **two** calls per brand.
- **Impact:** With 30 categories → 30 extra paginated queries per catalogue visit; scales linearly with taxonomy size.
- **Fix:** Return `products_count` via `withCount` (published only) in `/api/v1/categories` and `/api/v1/brands`.

#### FE-04 — Product page masks errors as "not found"

- **Severity:** Low (P3) · **Category:** UX · **Status:** Confirmed (code: `Product.svelte` `.catch(() => product = null)` → `MissingPage`)
- **Fix:** Distinguish 404 from network/5xx; show retry for the latter.

#### FE-05 — Hardcoded search suggestions don't match the catalogue

- **Severity:** Low (P3) · **Category:** Dead zone · **Status:** Confirmed (DB: "Dental chair" → 0, "Autoclave" → 0 published products)
- **Affected:** `Header.svelte` quick terms `['Dental chair', 'Autoclave', 'Handpiece']`.
- **Fix:** Drive from CMS (tags/featured categories) or remove.

#### FE-06 — `api.ts` header spread loses `Headers` instances

- **Severity:** Low (P3) · **Category:** Bug-in-waiting / CI · **Status:** Confirmed (lint warning; current callers pass plain objects)
- **Affected:** `resources/js/lib/api.ts:44`. This warning also makes `npm run check` exit 1.
- **Fix:** Merge with `new Headers(options.headers)`.

#### FE-07 — Decorative/empty alt text on product thumbnails in search & gallery

- **Severity:** Low (P3) · **Category:** Accessibility · **Status:** Confirmed (`Header.svelte` search results `alt=""` beside visible name is acceptable; gallery thumbnails in `Product.svelte` also `alt=""` — acceptable only if buttons are labelled; verify).

### 7. CMS Findings

#### CMS-01 — Tracking-script field enables admin-to-Mother-Admin privilege escalation

- **Severity:** High (P1) · **Category:** Stored XSS by design / Privilege escalation · **Status:** Confirmed (code)
- **Affected:** `resources/views/app.blade.php` (`{!! $headScripts !!}`, `{!! $bodyScripts !!}`), `ShareTrackingScripts` middleware (applied to **all** web routes incl. `/admin/*`), `SettingPolicy::manageTracking` (`settings.manage`).
- **Explanation:** The template comment assumes "only trusted admins". But `settings.manage` is a delegable permission that's weaker than Mother Admin. Script injected there runs in the Mother Admin's session on admin pages and can call `/api/v1/admin/*` (create admins, assign roles, etc.) using the session cookie + XSRF token.
- **Fix:** (a) Render tracking scripts only on storefront pages, never under `/admin` or `/dashboard`; (b) restrict editing to Mother Admin or a dedicated `tracking.manage` permission; (c) prefer a GTM container-ID field over raw HTML; (d) add a CSP for admin routes.
- **Tests:** Admin pages do not include `head_scripts`; non-mother admin cannot update tracking.

#### CMS-02 — Per-product SEO fields and global SEO defaults are dead data

- **Severity:** High (P1, SEO) · **Category:** Functional gap · **Status:** Confirmed (grep: no use of `seo`/`canonical`/`default_title` in storefront)
- **Affected:** Admin edits `seo_title`, `seo_description`, `seo_keywords`, `canonical_url`, `seo.default_title`, `seo.default_description`; storefront `PageMeta.svelte` uses hardcoded titles/descriptions or `product.shortDescription`.
- **Fix:** Feed these into `PageMeta` (and server-side, see SEO-01).

#### CMS-03 — Banner upload has no maximum dimension and decodes the file 3×

- **Severity:** Medium (P2) · **Category:** Crash point · **Status:** Potential (not executed — would risk OOM)
- **Affected:** `Store/UpdateHomepageSectionRequest.php` (`max:20480`, only `min_width/min_height`); `BannerImageService::render()` calls `imagecreatefromstring` once per rendition (desktop, tablet, mobile) without raising/lowering memory.
- **Explanation:** A 20 MB highly-compressed PNG can be e.g. 15000×15000 px ≈ 900 MB decoded → PHP fatal "Allowed memory exhausted" → 500, plus earlier renditions orphaned on disk.
- **Fix:** Add `max_width/max_height`, decode once and reuse, wrap in try/cleanup.

#### CMS-04 — Partial-failure orphans in banner and category saves

- **Severity:** Low (P3) · **Category:** Data consistency · **Status:** Potential
- **Affected:** `HomepageSectionService::save()` (files written before `save()`; no cleanup if a later rendition/DB write throws); `CategoryService::create()` (row created before image stored, no transaction).
- **Fix:** Transaction + compensating file delete, as already done in `Admin\ProductController::store()`.

#### CMS-05 — Media count limit race

- **Severity:** Low (P3) · **Status:** Potential · `ProductMediaController::store()` checks count then inserts without a lock; two concurrent uploads can exceed `MAX_IMAGES`. Low impact.

#### CMS-06 — Inertia-only vs API feature drift

- **Severity:** Informational · **Status:** Confirmed · `settings/tracking`, homepage sections, enquiries, product media "set main" exist only on the Inertia side; the README claims parity. Decide whether `/api/v1/admin` is a supported surface; if not, consider removing it to reduce attack surface.

#### CMS-07 — Enquiries: no admin notification, no spam protection beyond throttle

- **Severity:** Medium (P2) · **Category:** Missing safeguard · **Status:** Confirmed
- **Affected:** `ContactEnquirySubmissionController` (stores only; `throttle:5,1` per IP). `phone` is free text (not normalised with `PhoneNumber`).
- **Impact:** For a contact-the-business model, enquiries are the main conversion; nobody is alerted, and bots can fill the table.
- **Fix:** Queue a mail/WhatsApp notification; add honeypot/time-trap; normalise BD phone.

What works well (verified by tests/code): slug generation with uniqueness incl. trashed rows, category/subcategory delete protection, product image re-encoding to WebP ≤50 KB, main-image rules, bulk actions with per-item authorization, role anti-escalation, Mother Admin transfer with password confirmation, activity logging.

### 8. Database Findings

#### DB-01 — See BE-01 (column length mismatches) — **High**.

#### DB-02 — No index supporting the main public listing query

- **Severity:** Low now / Medium at scale (P2) · **Status:** Confirmed (schema)
- **Explanation:** Public listing = `WHERE status='published' AND deleted_at IS NULL ORDER BY created_at DESC, id`. Only single-column `status` exists → filesort on every page. Category/subcategory filters use `whereHas` on slug (correlated `EXISTS`).
- **Fix:** Composite `(status, deleted_at, created_at)`; filter by joined id rather than `whereHas` slug; consider `(category_id, status)`.

#### DB-03 — Search uses leading-wildcard `LIKE` across 3 columns

- **Severity:** Low (P3) · **Status:** Confirmed (`ProductService::paginate`) · Full scan per keystroke (see FE-02). Fine for hundreds of products; for thousands add a MySQL `FULLTEXT(name, sku, model_number)` index or Scout (database driver). Also `%`/`_` in user input aren't escaped (functional quirk, not injection — bindings are used).

#### DB-04 — Low-stock dashboard widget assumes inventory semantics

- **Severity:** Informational · `DashboardController` "lowStock" (`stock_quantity <= 5`) — fine if the client tracks stock; otherwise noise given the catalogue model.

#### DB-05 — Integrity is generally good

- FKs with sensible `restrict`/`cascade`/`set null`; unique slugs/SKUs; pivot uniques. `homepage_sections` has no index (5 rows — irrelevant). `product_variants.sku` globally unique — confirm intended.

### 9. Security Findings (summary; details above)

| ID     | Title                                                                                                                                         | Sev    | Status                                                                                                         |
| ------ | --------------------------------------------------------------------------------------------------------------------------------------------- | ------ | -------------------------------------------------------------------------------------------------------------- |
| CMS-01 | Tracking scripts → admin privilege escalation                                                                                                 | High   | Confirmed                                                                                                      |
| BE-02  | Publish permission bypass                                                                                                                     | High   | Confirmed                                                                                                      |
| SEC-01 | Vulnerable dependencies (Symfony HTTP stack, Laravel 13.36)                                                                                   | Medium | Confirmed (`composer audit`) — run `composer update` for `laravel/framework` ≥13.40 and Symfony ≥7.4.23/8.1.11 |
| SEC-02 | No security headers (CSP, X-Frame-Options/frame-ancestors, HSTS, Referrer-Policy, nosniff); `X-Powered-By: PHP/8.3.36` exposed                | Medium | Confirmed (runtime headers)                                                                                    |
| SEC-03 | No 2FA for admin accounts (Fortify `twoFactorAuthentication` disabled)                                                                        | Medium | Confirmed (`config/fortify.php`)                                                                               |
| BE-03  | Related product draft leak                                                                                                                    | Medium | Potential                                                                                                      |
| SEC-04 | Session cookie `secure` relies on `SESSION_SECURE_COOKIE` env (unset locally); `SESSION_ENCRYPT=false`                                        | Low    | Confirmed (config) — set in prod                                                                               |
| SEC-05 | API login throttle keyed on `email` input but API sends `identifier` → key degenerates to IP-only (shared 5/min for all accounts from one IP) | Low    | Confirmed (code: `FortifyServiceProvider::configureRateLimiting` vs `AuthController::login`)                   |
| BE-09  | Inactive customers keep sessions                                                                                                              | Low    | Confirmed                                                                                                      |
| SEC-06 | Banner/category/brand URL fields accept any `url` — only `http(s)` should be allowed for links rendered as `href`                             | Low    | Potential                                                                                                      |

Checked and **not** found: SQL injection (all queries bound), mass assignment (explicit `#[Fillable]`, validated arrays), IDOR on media (`abort_if($media->product_id !== $product->id)`), CSRF (session + XSRF), open redirect (post-login redirect restricted to own-site paths via session-stored intended URL), user enumeration on login (admin/customer split fails identically), upload type spoofing for images (re-encoded through GD). Video uploads are stored as-is (mp4/mov MIME validated) — acceptable.

### 10. Performance Findings

- **Measured (local dev, PHP built-in server, warm):** `/` 89 ms / 48.8 KB HTML; `/catalogue` 32 ms; `/api/v1/products?per_page=1` 19 ms; `/api/v1/categories` 12 ms. These are **not** production numbers (single-user, dev mode, Vite dev server).
- **Not measured:** LCP/FCP/INP/CLS/TBT — no Lighthouse run was performed. The stale `public/build` (dated Oct 15) shows `app-*.js` 226 KB, `app-*.css` 112 KB uncompressed, plus a 176 KB JPG bundled into assets; re-measure after `npm run build`.
- **Practical opportunities:** FE-03 (remove N+1 counts), FE-02 (debounce search), BE-10 (lighter eager loads), move listing data into SSR props (also fixes SEO-02 and removes a client waterfall on first paint), cache `/api/v1/categories`/`brands` responses (they change rarely), `loading="lazy"` already used for below-the-fold images; hero uses `fetchpriority="high"` (good). Product images are already ≤50 KB WebP (good).
- **Cache/session/queue all on the database** — fine for low traffic; every request does a settings cache read + session read/write on MySQL. Redis is not installed.

### 11. SEO Findings

#### SEO-01 — Product detail pages are not indexable

- **Severity:** Critical (P0 for a discovery catalogue) · **Status:** Confirmed (runtime: guest GET `/products/{slug}` → body "Sign in to view this product")
- **Explanation:** `ProductPolicy::view` denies guests; crawlers are guests. Every product URL looks to Google like a login wall with identical content (duplicate thin pages).
- **Fix (business decision required):** Make a public, indexable product view (name, images, description, specs, category, brand) and gate only sensitive fields (price/stock/downloads) behind login — the `show_price`/`show_stock` toggles already exist for this. This must be agreed with the client because "detail requires login" is a stated rule in README.

#### SEO-02 — Listing pages ship no product content in server HTML

- **Severity:** High (P1) · **Status:** Confirmed (runtime: SSR HTML of `/catalogue` contains 0 product names; `apiFetch` returns `null` during SSR by design)
- **Fix:** Pass first-page data as Inertia props (as already done for banners in `StorefrontHomeController`).

#### SEO-03 — Soft 404s

- **Severity:** Medium (P2) · **Status:** Confirmed (runtime: `/products/does-not-exist` → 200, `/categories/nope` → 200)
- **Fix:** Resolve slug server-side and return 404 when unknown/unpublished.

#### SEO-04 — No sitemap, no structured data, no canonical tags, CMS SEO fields unused

- **Severity:** High (P1) · **Status:** Confirmed (`/sitemap.xml` 404; no `ld+json`; `PageMeta` lacks canonical/og:image; see CMS-02). `robots.txt` allows everything but references no sitemap.
- **Fix:** Generated sitemap (products/categories/brands), `Organization` + `BreadcrumbList` JSON-LD site-wide, `Product` schema **without** `offers` (no purchasing) or with `offers` only when price is shown, `og:image` from main image, canonical from `canonical_url` or the slug URL.

#### SEO-05 — SSR depends on an undocumented runtime process

- **Severity:** Medium (P2) · **Status:** Confirmed (no SSR mention in README/docs; no supervisor/systemd config)
- **Explanation:** Titles/meta appear in HTML only when the Inertia SSR server (`php artisan inertia:start-ssr` after `npm run build:ssr`) is running. If it isn't, Inertia silently falls back to client rendering → `<title>DAS</title>` for every page.
- **Fix:** Document and supervise the SSR process; add a health check.

#### SEO-06 — Query-string catalogue filters (`/catalogue?category=x`) instead of category URLs; breadcrumb "Equipment" label doesn't match category

- **Severity:** Low (P3) · Use `/categories/{slug}` as the canonical category landing page in internal links (Footer uses `catalogue?category=`).

### 12. Infrastructure & Deployment Findings

| ID     | Finding                                                                                                                                                                                                                                                     | Sev    | Status                      |
| ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ | --------------------------- |
| INF-01 | No deployment artefacts (no Dockerfile, web-server config, process manager config for queue worker + SSR server, no deploy script). Only `.github/workflows/tests.yml`.                                                                                     | High   | Confirmed                   |
| INF-02 | Local `.env`: `APP_DEBUG=true`, `LOG_LEVEL=debug`, `APP_URL` is a LAN IP; no `.env.production` template documenting required prod values (`APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS`, `MAIL_*`).                           | Medium | Confirmed                   |
| INF-03 | `MAIL_MAILER=log` — password reset and email verification emails don't send anywhere until configured; email verification is toggleable but defaults to required.                                                                                           | Medium | Confirmed                   |
| INF-04 | No backup strategy for MySQL **and** `storage/app/public` (uploaded media are not in Git).                                                                                                                                                                  | High   | Confirmed (nothing in repo) |
| INF-05 | No error monitoring / uptime monitoring (`/up` exists — good).                                                                                                                                                                                              | Medium | Confirmed                   |
| INF-06 | CI tests only on SQLite (hides BE-01); `npm run check` exits 1 due to a warning.                                                                                                                                                                            | Medium | Confirmed                   |
| INF-07 | `vite.config.ts` hardcodes a LAN IP as `server.host` (committed value `192.168.0.113`, locally changed to `.111`), so it churns per developer machine. `public/hot` is correctly git-ignored but must never be copied to a server. Move host to an env var. | Low    | Confirmed                   |
| INF-08 | Queue = database; nothing currently queued (mail is sync). Worker must still be supervised once notifications (CMS-07) are added.                                                                                                                           | Info   | —                           |

### 13. Bugs & Dead Zones (consolidated)

Confirmed bugs: BE-01, BE-02, BE-04, BE-05, FE-01, FE-04, FE-05, FE-06, SEO-03, SEC-05.
Dead zones: per-product and global SEO fields (CMS-02); hardcoded search suggestions (FE-05); dashboard "low stock" for a non-inventory catalogue (DB-04); `/api/v1/admin` surface with no first-party client (CMS-06).

---

## 14. Crash Points & Failure Scenarios

| Scenario                                                     | Current behaviour                                                                                                                           | Evidence           |
| ------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------- | ------------------ |
| Admin enters >255 chars in short/SEO description or long URL | 500                                                                                                                                         | BE-01              |
| Huge-dimension banner upload                                 | PHP memory fatal, orphan files                                                                                                              | CMS-03 (potential) |
| API returns 5xx / network drop                               | Catalogue spinner forever; categories never load again until reload                                                                         | FE-01              |
| Rapid typing in search                                       | N requests, out-of-order results                                                                                                            | FE-02              |
| SSR server down                                              | Silent fallback; no SEO meta                                                                                                                | SEO-05             |
| DB unavailable                                               | Cache, sessions **and** queue all on DB → total outage incl. `/up`? (`/up` doesn't hit DB by default; every page does via session/settings) | Config             |
| Settings cache stale/corrupted                               | Mitigated: arrays cached, invalidated on write                                                                                              | Code               |
| Missing media file on disk                                   | Broken `<img>`; no placeholder fallback in card                                                                                             | Potential          |
| Domain change                                                | Category images uploaded earlier break                                                                                                      | BE-04              |

### Safe stress-test designs (staging only — not executed)

1. **Read load:** k6/`ab` against `/`, `/catalogue`, `/api/v1/products?page=N`, `/api/v1/categories` at 50→200 VUs for 5 min; record p95, error rate, MySQL connections; with 5,000 seeded products (factory) and 100 categories.
2. **Search storm:** 100 VUs issuing incremental search terms (`a`, `ab`, `abc`…) — expect DB CPU spike (DB-03).
3. **Concurrent admin uploads:** 5 admins each uploading 10 × 8 MB JPEGs to one product — verify memory (512 MB lift), `MAX_IMAGES` race (CMS-05), timing.
4. **Failure injection:** stop MySQL mid-test; kill SSR server; return 500 from `/api/v1/products` via proxy — verify UI states.
5. **Large catalogue UX:** 5,000 products / 100 categories — measure catalogue page request count (FE-03) and time-to-content.

---

## 15. Missing Test Coverage

Existing coverage is strong for auth modes, roles/anti-escalation, CRUD, media upload, bulk actions, enquiries. Gaps:

| Area              | Missing test                                                                                                                                                                |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| DB realism        | Entire suite on MySQL in CI; boundary-length fields (BE-01)                                                                                                                 |
| Authorization     | Publish without `products.publish` via update/store (BE-02); tracking scripts absent from admin pages / restricted (CMS-01)                                                 |
| API data exposure | Unpublished related products excluded (BE-03)                                                                                                                               |
| Media             | Aspect-ratio preservation (BE-05); banner oversize dimension rejection (CMS-03); file cleanup on soft/force delete (BE-06)                                                  |
| SEO               | 404 for unknown product/category slug; sitemap contents; meta tags rendered server-side                                                                                     |
| Frontend          | No JS/component tests and no browser tests at all (Pest 4 browser plugin available): catalogue filters, search, login modal on product page, error/empty states, mobile nav |
| Accessibility     | No automated axe checks                                                                                                                                                     |
| Sessions          | Deactivated customer is logged out (BE-09)                                                                                                                                  |

### Testing matrix (target)

| Domain                                                      | Unit                     | Feature (HTTP)               | Browser                        |
| ----------------------------------------------------------- | ------------------------ | ---------------------------- | ------------------------------ |
| Auth (customer/admin split, modes, throttle, reset, verify) | ✓ PhoneNumber            | ✓ exists                     | add login modal flow           |
| Admin permissions per action (incl. publish)                | –                        | extend                       | –                              |
| Product CRUD + SEO fields + boundary lengths                | SlugGenerator            | extend (MySQL)               | admin form happy path          |
| Category/Subcategory/Brand CRUD + images                    | –                        | ✓ exists, add image cleanup  | –                              |
| Media upload/processing                                     | image processor (aspect) | ✓ exists, add oversize       | –                              |
| Banners                                                     | BannerImageService       | ✓ exists, add oversize       | hero carousel                  |
| Storefront navigation/search/filters                        | catalogue.ts mappers     | API filters ✓                | **add**                        |
| SEO                                                         | –                        | **add** (404, sitemap, meta) | –                              |
| Error handling                                              | –                        | API 4xx shapes               | **add** offline/5xx states     |
| Responsive / a11y                                           | –                        | –                            | **add** (viewport sizes + axe) |

---

## 16. Scalability Risks

1. Linear growth of API calls with taxonomy size (FE-03).
2. Unindexed listing sort + `whereHas` slug filters + `LIKE` search (DB-02/03) — fine at 16 products, noticeable at several thousand.
3. Everything on MySQL (cache, sessions, queue) — acceptable to ~low hundreds of concurrent users; move cache/sessions to Redis beyond that.
4. Media on the local `public` disk — blocks horizontal scaling and needs separate backup; consider S3-compatible storage + CDN.
5. Admin product index sends full product models (incl. `full_description`, all media) to the browser per page.

## 17. Recommended Improvements (catalogue-relevant, non-transactional)

- Public SEO-friendly product pages with login-gated price/stock (requires client sign-off).
- Server-side props for first page of listings/category pages; real 404s.
- Sitemap + JSON-LD + canonical + OG image; wire CMS SEO fields.
- Enquiry notifications (email/WhatsApp), honeypot, "enquire about this product" pre-filled from product page.
- Trash/restore for products; media library cleanup command.
- Admin 2FA; restrict tracking-script editing; CSP + security headers middleware.
- MySQL CI job; Pest browser smoke tests for storefront.
- Deployment runbook: build + build:ssr, SSR & queue supervisors, `storage:link`, backups, monitoring.

---

## 18. Production Readiness Assessment

### Scorecard (0–10, evidence-based)

| Dimension            | Score               | Evidence                                                                                                                       |
| -------------------- | ------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| Backend Health       | **7**               | Clean architecture, PHPStan L7 clean, policies everywhere; docked for BE-01/02/04/05                                           |
| Frontend Health      | **5**               | Modern Svelte 5 runes, consistent data layer; no error handling on most fetches, races, N+1 requests, zero frontend tests      |
| CMS Reliability      | **6**               | Broad, mostly correct, well tested; image distortion, 500s on long input, script-injection trust boundary, orphan files        |
| Database Health      | **6**               | Good FKs/uniques; validation/length mismatch; missing composite index; soft-delete hygiene                                     |
| Application Security | **6**               | Strong role model, no injection/IDOR found; publish bypass, script-based escalation, vulnerable deps, no headers, no admin 2FA |
| Performance          | **5** (provisional) | Small payloads and WebP images; N+1 network calls, unthrottled search; no real CWV measurement taken                           |
| SEO                  | **2**               | Product pages unindexable, no listing content in HTML, soft 404s, no sitemap/schema, CMS SEO fields unused                     |
| Accessibility        | **5** (provisional) | Labels/aria on icon buttons observed; no automated audit performed                                                             |
| Testing Coverage     | **6**               | 222 backend tests, good domain coverage; SQLite-only, no frontend/browser tests                                                |
| Infrastructure       | **3**               | No deploy config, backups, monitoring, prod env template, SSR process docs                                                     |
| Scalability          | **5**               | Fine for current size; known linear hotspots                                                                                   |

**Overall: ~5.1 / 10 — NOT READY.**

### Verdict

The application is functionally close to a credible launch but should **not go to production** until the P0/P1 items below are done. None of them requires architectural change.

---

## 19. Prioritized Remediation Roadmap

### P0 — before launch (business-critical)

1. **SEO-01** Agree with the client on public product pages (gate price/stock only), then implement. _(~2–3 days)_
2. **BE-01** Align column sizes ↔ validation (migration to widen columns) and add a MySQL CI job. _(~0.5–1 day)_

### P1 — before launch

3. **BE-02** Enforce `publish` on single create/update (Inertia + API) + tests. _(0.5 day)_
4. **CMS-01** Exclude tracking scripts from admin/dashboard routes; restrict editing; add CSP on admin. _(0.5–1 day)_
5. **SEO-02/03/04, CMS-02** Server-side listing props, real 404s, sitemap, JSON-LD, canonical/OG, wire SEO fields. _(2–3 days)_
6. **FE-01** Error/retry states and promise-cache reset across storefront fetches. _(1 day)_
7. **SEC-01** `composer update` for Laravel ≥13.40 and Symfony patches; re-run suite. _(0.5 day)_
8. **INF-01/02/04/05** Deployment runbook + supervisors (queue, SSR), prod `.env` template, DB + media backups, error monitoring. _(1–2 days)_
9. **BE-05** Preserve aspect ratio in product image processing. _(0.5 day)_

### P2 — first iteration after launch

10. FE-02 debounce/abort; FE-03 counts in category/brand APIs; BE-10 lighter eager loads.
11. BE-03 published-only related products; BE-04 relative image paths (+ data fix).
12. CMS-03/04 banner dimension cap, single decode, transactional saves.
13. CMS-07 enquiry notifications + honeypot + phone normalisation.
14. SEC-02 security headers; SEC-03 admin 2FA; DB-02 composite index; SEO-05 SSR health check.
15. Pest browser smoke tests for storefront and admin product flow.

### P3 — maintenance

16. BE-06 trash/restore + media pruning; BE-07 remove hardcoded contacts; BE-08 API resources for categories/brands; BE-09 inactive-session logout; SEC-05 throttle key; SEC-06 restrict URL schemes; FE-04/05/06; SEO-06; CMS-05/06; DB-03 full-text search when catalogue grows.

---

### Appendix A — Critical issues requiring immediate attention

SEO-01, BE-01, BE-02, CMS-01, SEO-02/04 + CMS-02, FE-01, SEC-01, INF-04.

### Appendix B — Missing safeguards relevant to a non-transactional catalogue

Enquiry notification & spam protection; public indexable product content; sitemap/structured data; product trash/restore; media backups; admin 2FA; CSP; SSR supervision; error monitoring.

### Appendix C — Progress tracker (this audit)

| Phase                                                                    | Status                                                     |
| ------------------------------------------------------------------------ | ---------------------------------------------------------- |
| Stack/version discovery                                                  | Done                                                       |
| Baseline tests & static analysis                                         | Done                                                       |
| Routes / middleware / auth / policies                                    | Done                                                       |
| CMS services & requests (products, media, categories, banners, settings) | Done                                                       |
| Database schema & sampling                                               | Done                                                       |
| Storefront data layer & key pages                                        | Done (not every admin Svelte page reviewed line-by-line)   |
| Runtime GET probing                                                      | Done                                                       |
| Core Web Vitals / Lighthouse                                             | **Not done** — recommended next step on a production build |
| Accessibility automated audit                                            | **Not done**                                               |
| Load / failure testing                                                   | Designed only (§14)                                        |

---

## Appendix D — Remediation Status (2026-11-15, uncommitted on `main`)

Verification after fixes: Pest **261 passed, 1 skipped** (39 new tests); PHPStan 0 errors; Pint pass; `npm run check` **pass** (was failing); `npm run types:check` 0 errors; `composer audit` **no advisories**; `npm run build:ssr` builds; live SSR check confirmed catalogue/category HTML contains product names, `<title>`, canonical and JSON-LD.

| ID              | Status                       | What changed                                                                                                                                   |
| --------------- | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| BE-01 / DB-01   | Fixed                        | Migration widens 9 columns to their validation limits; MySQL CI job added                                                                      |
| BE-02           | Fixed                        | `ChecksPublishPermission` on product store/update (Inertia + API)                                                                              |
| BE-03           | Fixed                        | Related products constrained to `published()`                                                                                                  |
| BE-04           | Fixed                        | `PublicDiskImage` cast stores relative paths; data migration converts legacy absolute URLs                                                     |
| BE-05           | Fixed                        | Images fit into the square with transparent padding instead of stretching                                                                      |
| BE-06           | Fixed                        | Product trash view, restore, permanent delete (removes files), clearer SKU message                                                             |
| BE-07           | Partial                      | Backend defaults removed; storefront fallback remains in `resources/js/lib/contact.ts`                                                         |
| BE-08           | Fixed                        | `CategoryResource`, `SubcategoryResource`, `BrandResource`                                                                                     |
| BE-09           | Fixed                        | `LogoutInactiveUsers` middleware on web + API                                                                                                  |
| BE-10           | Fixed                        | Public listing eager-loads only brand + images                                                                                                 |
| FE-01           | Fixed                        | Error/retry states; failed category/brand loads are retried                                                                                    |
| FE-02           | Fixed                        | 250 ms delay + latest-request-wins on catalogue and header search                                                                              |
| FE-03           | Fixed                        | `products_count` in categories/brands API; per-item count requests removed (4 components)                                                      |
| FE-04           | Fixed                        | Product page distinguishes load failure (retry) from not found                                                                                 |
| FE-05           | Fixed                        | Search suggestions are the first CMS categories                                                                                                |
| FE-06           | Fixed                        | Header merge via `Headers`                                                                                                                     |
| CMS-01          | Fixed                        | Tracking scripts: Mother Admin only, never rendered on admin pages; admin CSP                                                                  |
| CMS-02          | Fixed                        | Product SEO fields and global SEO defaults feed `PageMeta`                                                                                     |
| CMS-03 / CMS-04 | Fixed                        | Banner max 8000px, single decode, cleanup on failure; transactional category/subcategory saves                                                 |
| CMS-05          | Fixed                        | Media limit enforced in `ProductMediaService::add()` under a row lock — also closes a newly found gap: the admin API had no media limit at all |
| CMS-06          | Resolved (option 1)          | Admin API documented as partial; admin-panel-only features listed in README "Admin API coverage"                                               |
| CMS-07          | Fixed (partial)              | Queued mail notification + honeypot; phone normalisation skipped (existing contract stores phone as typed)                                     |
| SEC-01          | Fixed                        | Laravel 13.42, Symfony 7.4.23                                                                                                                  |
| SEC-02          | Fixed                        | `AddSecurityHeaders` (nosniff, frame, referrer, permissions, HSTS, admin CSP, X-Powered-By removed)                                            |
| SEC-03          | Skipped (owner decision)     | Admin 2FA deferred                                                                                                                             |
| SEC-05          | Fixed                        | Login limiter keys on `email` or `identifier`; non-stateful API login now 400 instead of a 500 (newly found)                                   |
| SEC-06          | Fixed                        | URL fields accept `http`/`https` only                                                                                                          |
| SEO-01          | Not changed (owner decision) | Login wall kept; the wall page is `noindex` and products are left out of the sitemap                                                           |
| SEO-02          | Fixed                        | Catalogue/category first page rendered server-side as props                                                                                    |
| SEO-03          | Fixed                        | Unknown/inactive category → real 404; 404 page `noindex`                                                                                       |
| SEO-04          | Fixed                        | `/sitemap.xml`, dynamic `/robots.txt`, canonical, OG, Organization + BreadcrumbList JSON-LD                                                    |
| SEO-05          | Fixed                        | Scheduled `inertia:check-ssr` logs critical on failure; runbook supervises SSR                                                                 |
| SEO-06          | Fixed                        | Footer and category tiles link to `/categories/{slug}`                                                                                         |
| DB-02           | Fixed                        | `products_listing_index (status, deleted_at, created_at)`                                                                                      |
| DB-03           | Partial                      | `%`/`_` escaped in search; full-text search deferred until the catalogue grows                                                                 |
| INF-01/02/04/05 | Fixed                        | `docs/deployment.md`, `.env.production.example`, `spatie/laravel-backup` (DB + media, nightly, failure mail), Slack error alerts               |
| INF-06          | Fixed                        | MySQL CI job; `npm run check` passes                                                                                                           |
| INF-07          | Open                         | `vite.config.ts` LAN IP left as-is (locally modified by the developer)                                                                         |
| Browser tests   | Open                         | Needs `pestphp/pest-plugin-browser` (not approved)                                                                                             |
