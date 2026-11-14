# DAS Analysis: Product Documents, Categories, Images

Analysis only. Nothing in the codebase was changed.

## 1. Product documents vs. the data structure

### Structure extracted from the documents

The same layout appears in every file:

| Doc field                           | Example                                                   | Maps to                                  |
| ----------------------------------- | --------------------------------------------------------- | ---------------------------------------- |
| Title line                          | "SHOFU VINTAGE PRO BODY 50G"                              | `name`                                   |
| Sub-title / description line        | "Dental Circular Ceramic Honeycomb Firing Tray…"          | `short_description` / `full_description` |
| Material                            | Porcelain, Ceramic                                        | spec row                                 |
| Key Features (numbered, Shofu only) | "Excellent Firing Stability"                              | `highlights`                             |
| Warranty                            | "Not applicable"                                          | spec row                                 |
| After-sale Service                  | "Online Technical Support"                                | spec row                                 |
| Place of Origin                     | China / Japan                                             | spec row                                 |
| Delivery (some files)               | "Through courier all over Bangladesh"                     | spec row, or one global setting          |
| Model Name                          | shade list (A1…D4), sizes (45/65/85/100mm), Silver/Golden | **variants, with no home in the schema** |
| Size / weight in the text           | "3 g", "128mm", "25 mm"                                   | spec row                                 |
| Stock                               | always blank                                              | `stock_quantity` / `stock_status`        |
| One embedded photo                  |                                                           | `product_media`                          |

### Where they don't match the schema

- **Variants are the biggest gap.** "Model Name" holds a list of options. The products table has a single `model_number` and no variants. The Shofu body powder alone has 16 shades. Either make each shade or size its own product, or add a `product_variants` table (recommended).
- **Required fields the documents never supply:** `regular_price`, `sku`, `category_id`, `subcategory_id`. Prices and SKUs must come from the client. Stock is blank everywhere.
- **Brand is only in the title** ("SHOFU"), not in its own field.
- **Material, Warranty and the other label lines** fit the existing `product_specifications` table (name/value). Key Features fit `highlights`.

### Data problems in the documents

- `Pro Porcelain.doc` contains the Empty Cartridges text word for word. It is a copy-paste error with no real content.
- Firing Tray lists the same model twice ("Firing Tray with SS Pins / Firing Tray with SS Pins"). It also carries a company boilerplate paragraph the others lack.
- Base Opaque 6g says "Opaque Paste" in Material but "Fine Powder" in the features, and its model is "Base Paste 6g".
- Empty Cartridges is titled "25 mm", but its models are 45 to 100 mm.
- The Saw handle files put the material into "Model Name".
- Both hanging motor files name their model "Hand Piece Silver / Golden", although one is a motor.
- Polishing Brush Wheel is titled "Brush & Wheel", but its only model is "Polishing Brush".
- "Delivery" appears in some files and not others. "Warranty" is worded inconsistently ("Service warranty" for the motor).
- The Shofu Art shade list mixes names and codes ("Gr (GR)", "DR-Br (DRBR)").
- Each document has one photo, and they vary a lot:

| Photo            | Size                   |
| ---------------- | ---------------------- |
| Firing Tray      | 4636×4415, 1.2 MB      |
| hanging motor    | 1920×1920, 263 KB      |
| Crucible Large   | 479×455                |
| Shofu Opaque 50G | 500×500                |
| Most others      | 650 to 1280 px, square |

They are JPG or PNG, not WebP. All but Crucible Large are square.

`DAS (1).ai` is an Illustrator file, probably the logo or branding. It was not opened.

## 2. Categories and sub-categories

Sub-categories already exist in the code. There is a `subcategories` table linked to categories, a Subcategory model and service, admin CRUD on `admin/categories`, and the header mega menu already reads `c.subcategories`. A product must have both a `category_id` and a `subcategory_id`.

What needs to change:

- **Seed data.** Replace the demo categories (Dental Chairs, Endodontics, Imaging, …) with **Instrument** and **Material**. Material gets **Porcelain**, **Denture** and **CAD/CAM**.
- **Instrument has no sub-categories.** `subcategory_id` is required, so Instrument products can't be saved. Either make it nullable (recommended), or add a hidden "General" sub-category.
- **Frontend and API.** Not verified: how the catalogue filters, breadcrumbs and category pages handle a category with no sub-categories.
- **Suggested mapping (client to confirm):**
    - Shofu Vintage Pro products: Material → Porcelain.
    - Empty Cartridges (Flexible Denture): Material → Denture.
    - Crucibles, Firing Tray, Saw handles, hanging motor, hand piece, Polishing Brush: Instrument or accessory items. Crucibles and the Firing Tray could arguably be Material → Porcelain.

## 3. Image slots and recommended specs

**Proposed CMS rule for all images:** WebP only, under 100 KB for large images and under 50 KB for everything else.

| Slot                                             | Where it shows                                                                                                                                        | Today's rule                                             | Recommended                                                                                                                                         |
| ------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Home banner, desktop**                         | Hero (`<picture>`, above 1100px)                                                                                                                      | Auto-cropped to **1920×640** (3:1), WebP quality 82      | Keep. Upload a landscape file of at least 1920×640. Output is ~75–90 KB, so this is the one slot needing the 100 KB cap.                            |
| **Home banner, tablet**                          | 768–1100px                                                                                                                                            | Auto-cropped to **1536×768** (2:1)                       | Keep. Output is ~13 KB.                                                                                                                             |
| **Home banner, mobile**                          | Under 767px                                                                                                                                           | Auto-cropped to **1080×1350** (4:5)                      | Keep. Output is ~16 KB.                                                                                                                             |
| **Product images** (card, detail, gallery, zoom) | Card has a 5:4 box with object-contain. Detail is a square. Thumbnails are small squares.                                                             | WebP/JPG/PNG up to 10 MB (≤8000 px a side), re-encoded server-side to **1200×1200** WebP ≤50 KB, max 10 images | Square **1000×1000**, ≤100 KB. Use a white or transparent background, because the storefront blends images into the page with `mix-blend-multiply`. |
| **Category image**                               | 4:3 category card, 256 px category page header, 56 px mega-menu tile                                                                                  | JPG/PNG/WebP, ≤5 MB, no size check, not converted        | **800×600 (4:3)**, ≤50 KB, white or transparent background                                                                                          |
| **Sub-category image**                           | 56 px square in the mega menu (admin shows 64 px)                                                                                                     | Same loose rule as categories                            | **600×600**, ≤40 KB                                                                                                                                 |
| **Brand logo**                                   | 56 px (Brands page) and 48 px (header) squares                                                                                                        | Plain text field, no upload                              | **400×400**, transparent WebP, ≤30 KB                                                                                                               |
| **Site logo** (settings)                         | Setting `logo`                                                                                                                                        | Plain text field, no upload                              | Needs a decision. The header seems to show text and an icon, so confirm where this logo actually appears.                                           |
| **Favicon and touch icon**                       | `favicon.ico`, `favicon.svg`, `apple-touch-icon.png`                                                                                                  | The touch icon is 166×166, but Apple expects 180×180     | `apple-touch-icon.png` at 180×180. These stay PNG/SVG/ICO.                                                                                          |
| **Social share image**                           | `twitter:card = summary_large_image`                                                                                                                  | No image set                                             | Add one at 1200×630, if the client wants it.                                                                                                        |
| **Static page images**                           | `dental-suite.jpg` (1920×1024) is the fallback hero, the About hero and the editorial banner. 27 other demo JPGs in `resources/js/assets/storefront/` | Hard-coded JPGs in the code                              | Remove, or make them editable slots. Not editable from the CMS today.                                                                               |
| **Existing uploads**                             | `public/storage/categories/22` is 348 KB                                                                                                              |                                                          | Re-upload under the new rules.                                                                                                                      |

### Gaps in the current CMS rules

- Only product images enforce a format, dimensions and size, and even those are normalised server-side (10 MB upload cap, stored as 1200 px WebP ≤50 KB).
- Category, sub-category, brand and logo images have no WebP, dimension or small size limit.
- Brand and logo don't have an upload at all.
- Server-side conversion to WebP at fixed sizes, as banners already have, would be the most reliable way to apply the rules.

## Decisions needed

1. **Variants:** a `product_variants` table (recommended), or one product per shade and size?
2. **Instrument sub-categories:** make `subcategory_id` nullable (recommended), or add a hidden "General" sub-category?
3. **Product image size:** keep 1200×1200 or move to 1000×1000? 1000 fits the 100 KB budget more reliably.
4. **Image enforcement:** reject uploads that break the rules, or auto-convert and resize them like the banners?
