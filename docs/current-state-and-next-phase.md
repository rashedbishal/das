# DAS: Current State and Next-Phase Brief

**Purpose:** concise handoff for a technical planning discussion. This describes
the current repository implementation; production hosting and live configuration
should be verified separately.

## 1. Product and scope

DAS is a CMS-managed dental product catalog and discovery site. It is not an
e-commerce checkout: there is currently no cart, payment, or order management.
Customers are expected to contact the business directly.

## 2. Technology and architecture

- **Backend:** PHP 8.3, Laravel 13, Eloquent, migrations, services, policies,
  form requests, Pest 4, Pint, and Larastan/PHPStan.
- **Frontend:** Inertia v3, Svelte 5, TypeScript, Tailwind CSS v4, bits-ui, and
  Laravel Wayfinder-generated route/action helpers.
- **Authentication:** Fortify sessions for the web app; Sanctum stateful
  same-origin session authentication for `/api/v1`. There is one users table
  with `user_type` distinguishing admins and customers.
- **Data access:** server-rendered Inertia pages for CMS and catalog pages;
  JSON API resources under `/api/v1` for the primary storefront and future
  clients. Public resources intentionally return less product detail than
  authenticated detail endpoints.
- **Database and infrastructure:** configurable through Laravel `.env`.
  `.env.example` currently uses SQLite and database-backed session, cache, and
  queue drivers. MySQL is supported. Product uploads use Laravel's public
  filesystem disk and require `php artisan storage:link`.

## 3. Data model

| Area | Main records and relationships |
|---|---|
| Identity and access | `users` (admin/customer, active flag, optional phone and role), `roles`, `permissions`, and role-permission assignments |
| Catalog taxonomy | `categories` → optional `subcategories`; products belong to one category and can optionally belong to a subcategory |
| Products | `products` with SKU, slug, brand, descriptions, highlights, draft/published/archived status, featured flag, nullable regular/sale prices, currency, stock quantity/status, and SEO fields |
| Product content | `product_variants` (options such as shade or size), `product_specifications` (name/value rows), `product_media` (uploaded or URL media), tags, and related-product links |
| Site content/config | `homepage_sections` (including scheduled banners), grouped JSON `settings`, and `admin_activity_logs` table |

Product price and stock visibility are independent product-level booleans.
Hidden values remain stored but are omitted from public product data and
excluded from public filters; the CMS can toggle either field individually or
in bulk. Stock quantity is shown in the storefront only when stock visibility
is enabled. Product options have their own prices and stock statuses, but
quantity visibility and storefront display are currently product-level.

## 4. Current catalog and customer experience

- The reviewed client catalog import contains **15 products, 15 product images,
  and 81 options**, with two brands and the approved category tree. Prices not
  provided by the source are unset; options are marked “On order.” See
  [product-import-review.md](./product-import-review.md).
- `php artisan catalog:import-client-data` replaces products, categories,
  subcategories, brands, and tags after confirmation. It preserves users,
  homepage sections, and other settings; `--force` skips confirmation.
- Public catalog browsing shows product summaries. Full product details require
  customer sign-in; guests see an inline login prompt on product pages.
- The main storefront is under `/`, `/catalogue`, `/categories`, `/products`,
  and `/brands`. A separate, more server-driven catalog also exists under
  `/catalog`. These are parallel implementations and should be considered for
  consolidation or clearly assigned responsibilities.
- Contact widgets, phone links, and quote/enquiry UI are present. **The quote
  enquiry is explicitly a disconnected demo and does not send or persist a
  lead.** No checkout or order workflow is implemented.

## 5. CMS and security capabilities

- Product, category/subcategory, brand, tag, homepage section, settings, and
  user/role administration.
- Product list thumbnails, media management, product status and featured
  controls, optional category subcategory assignment, price/stock visibility,
  and selection-based bulk actions. Bulk operations are permission-checked;
  destructive actions respect protected records and relationships.
- Role permissions are slug-based and policy-checked. The Mother Admin role is
  system-protected; transfer requires the current Mother's password.
  Admins cannot grant permissions or roles they do not themselves hold.
- Customer login can be configured for email, phone, or either. Bangladeshi
  phone numbers are normalized. Public registration creates customers only.
- Product image uploads are validated and stored with generated filenames.
  Homepage banner images are transformed into responsive renditions.

## 6. Configuration and developer operations

Important settings are managed in **Admin → Settings**: site identity/contact,
currency/timezone, login mode and registration, WhatsApp/Messenger, catalog
page size/featured limit/delivery note, SEO defaults, and tracking snippets.
Runtime secrets and infrastructure settings belong in `.env`, not source
control. Production Sanctum stateful domains must include the deployed
same-origin domain.

Useful commands:

```bash
composer setup
php artisan migrate
php artisan db:seed
php artisan das:bootstrap-mother-admin
php artisan catalog:import-client-data
npm run build
composer test
npm run types:check
```

**Production caution:** `DatabaseSeeder` currently also creates a default
factory user. Review that behavior before using `php artisan db:seed` on a
production database. Bootstrap the Mother Admin with secure deployment-time
credentials; never store those credentials in the repository. Production
password rules, destructive DB command protections, and `/up` health routing
are configured in the app.

## 7. Gaps and decisions for the next phase

### Confirm before implementation

1. **Sales workflow:** remain a catalog with phone/WhatsApp contact, or capture
   enquiries as leads? If leads are needed, decide delivery (email/CRM), storage,
   notifications, assignment, consent, spam protection, and retention.
2. **Inventory semantics:** is stock count authoritative and current? Should
   stock be tracked per variant, or only per product? Should public users see
   exact counts, availability labels, or both?
3. **Pricing:** who maintains prices, which currencies/discount rules apply,
   and what should “Contact for price” mean operationally?
4. **Catalog ownership:** confirm the imported products, labels, images,
   category placement, specifications, and “On order” status with the client;
   decide who maintains future updates and imports.
5. **Customer access:** should all full product details require sign-in, or
   should public visitors see descriptions/specifications/options?

### Candidate implementation gaps

- Product CMS forms do not currently provide dedicated editing for variants,
  specifications, or related products; the imported records and public views
  support these data, but ongoing maintenance needs a defined workflow.
- The two public catalog implementations can diverge. Decide which is canonical
  before adding substantial features.
- Quote/enquiry is a non-functional demo. There is no lead inbox, CRM handoff,
  or persisted enquiry record.
- Confirm production infrastructure and operations: hosting, database choice,
  image storage/backups, mail delivery, queue worker, cache, monitoring,
  deployment/migration rollback, and database/media restore tests.
- Confirm SEO expectations (redirects when slugs change, metadata, sitemap,
  structured data), analytics/tracking governance, and supported languages.
- Review whether activity logging is consistently written and available to
  admins; a log table exists, but an operational audit workflow is not
  established by this brief.

## 8. Suggested planning sequence

1. Agree on sales/contact flow, access to product details, price policy, and
   inventory truth source.
2. Confirm client catalog accuracy and CMS ownership; decide whether variant
   and specification editing is needed in the first phase.
3. Choose the canonical storefront/catalog and define a prioritized customer
   journey.
4. Specify lead capture or explicitly keep all contact outside the application.
5. Set production requirements and acceptance criteria, then plan delivery in
   small releases with regression tests for role permissions, public data
   redaction, product visibility, and catalog import safety.
