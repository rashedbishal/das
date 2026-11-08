# Product Import Review

**Status:** Cleaned data draft for sign-off before product import.  
**Scope:** Product schema and extracted document data only. No product records, images, categories, or product seed data are imported in this phase.

The source `.doc` files are not present in the workspace. This review captures the extracted details and decisions supplied for this cleanup; source-only wording (including complete SHOFU highlights and shade labels) must be carried over from the original documents during the later import.

## Approved import rules

- Products with options use `product_variants`. Variant price is optional; a missing price means **Price on request**.
- Variant SKUs are generated as `DAS-0001`, `DAS-0002`, and so on. Imported stock status is **On order**.
- SHOFU products use the **SHOFU** brand. All other products use the house brand **DAS**.
- `Pro Porcelain.doc` is skipped because it duplicates Empty Cartridges.
- Delivery is the site-wide setting `catalog.delivery_note`: **Through courier all over Bangladesh**.
- Warranty and After-sale Service are specification rows. Use **Not applicable** unless the source specifies **Service warranty**; normalize After-sale Service to **Online technical support**.
- Place of Origin is a specification row, preserving the source value (**China** or **Japan**).
- Material and any stated size, weight, or length are specification rows.
- Non-SHOFU products have no Key Features. SHOFU Key Features are retained unchanged from their source documents.

## Cleaned products

| Product name                                          | Brand | Material                 | Variant names                                                    | Cleanups and retained specifications                                                                                                                                             |
| ----------------------------------------------------- | ----- | ------------------------ | ---------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Durable Manual Large Crucibles for Dental Lab         | DAS   | Ceramic                  | —                                                                | Drop model “Manual Large Crucibles” because it repeats the product name.                                                                                                         |
| Durable Manual Standard Size Crucibles for Dental Lab | DAS   | Ceramic                  | —                                                                | Drop the repeated model name.                                                                                                                                                    |
| Empty Cartridges for Flexible Denture                 | DAS   | Aluminum                 | 45mm, 65mm, 85mm, 100mm                                          | Keep **Diameter: 25 mm** as a specification. Remove “25 mm” from the description; fix mixed separators.                                                                          |
| Firing Tray (Honeycomb, with Pins)                    | DAS   | Ceramic                  | SS Pins; Ceramic Pins                                            | Distinguish the duplicate source models through “SS and Ceramic Pins”; remove the company boilerplate.                                                                           |
| Dental Lab Hanging Motor Foredom SR                   | DAS   | Metal body               | Silver; Golden                                                   | Retain these as variants per approval. Replace the incorrect “Hand Piece” model text; Warranty: **Service warranty**.                                                            |
| Hanging Motor Handpiece with Wrench                   | DAS   | Metal                    | Silver; Golden                                                   | Normalize “HandPiece” to “Handpiece”.                                                                                                                                            |
| Dental Lab Polishing Brush & Wheel                    | DAS   | Plastic bristles         | Brush; Wheel                                                     | Represent as two variants.                                                                                                                                                       |
| Dental Plaster Saw 128mm, Rubber Handle               | DAS   | Stainless steel & rubber | —                                                                | Remove material from Model Name; Length: **128 mm**; Pack: **1 unit** (normalized from “1unit/s”).                                                                               |
| Dental Plaster Saw 128mm, Steel Handle                | DAS   | Stainless steel          | —                                                                | Remove material from Model Name; Length: **128 mm**; Pack: **1 unit** (normalized from “1unit/s”).                                                                               |
| SHOFU Vintage Pro Art Cream 3g                        | SHOFU | Vintage Art (stain)      | 21 shades from source                                            | Normalize shade labels as “Name (CODE)” where both are present; preserve bare codes as written. Weight: **3 g**. Full shade list and SHOFU features remain verbatim from source. |
| SHOFU Vintage Pro Base Opaque Powder 50g              | SHOFU | Base opaque powder       | —                                                                | Drop Model Name because it repeats the product name.                                                                                                                             |
| SHOFU Vintage Pro Base Opaque Paste 6g                | SHOFU | Opaque paste             | —                                                                | Remove the contradictory “Fine Powder” feature.                                                                                                                                  |
| SHOFU Vintage Pro Body 200g                           | SHOFU | Porcelain                | A1, A2, A3, A3.5, A4, B1, B2, B3, B4, C1, C2, C3, C4, D2, D3, D4 | Keep 16 shades, including D3 and D4 per approval. Variant names are shades only; weight: **200 g**.                                                                              |
| SHOFU Vintage Pro Body 50g                            | SHOFU | Porcelain                | A1–D4 (16 shades from source)                                    | Variant names are shades only; weight: **50 g**.                                                                                                                                 |
| SHOFU Vintage Pro Opaque 50g                          | SHOFU | Opaque                   | A1–D4 (16 shades from source)                                    | Fix the double space in the title.                                                                                                                                               |

## Final checks before import

- Confirm all source-specific Place of Origin and Warranty values against each original document.
- Copy all 21 Art shades and all SHOFU Key Features verbatim from the source files; only apply the approved shade-label normalization.
- Generate distinct variant SKUs in import order and set each imported variant to **On order**.
- Keep prices unset until supplied; do not infer prices from the documents.
