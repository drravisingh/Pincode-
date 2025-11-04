# Features & Usage Guide

This guide explains every major capability shipped with the PIN code directory,
how to operate the admin dashboard, and where to hook in extra services such as
maps or analytics.

## Public site experience

- **Front controller routing** – all public traffic flows through
  `public/index.php`, which bootstraps the application and dispatches the
  correct controller for homepage, search, or individual PIN views.
- **Detail pages with nearby offices** – `app/controllers/PincodeController.php`
  fetches the requested PIN record plus six nearby offices from the same state
  so each page can promote related locations. The view lives at
  `resources/views/pincode/detail.php`.
- **Reusable layouts and SEO metadata** – the header/footer templates render the
  meta tags, Google verification snippets, analytics, and AdSense placements
  stored in admin settings so every page automatically inherits your marketing
  configuration.

## Admin dashboard overview

Navigate to `/admin` after completing installation. The panel loads shared
bootstrap code, creates any missing tables, and seeds a default admin account on
first run.

The navigation tiles map to the following workflows:

1. **Import PIN codes (CSV)**
   - Upload a `.csv` (max 12 MB). Columns are auto-mapped using header aliases
     defined inside `public/admin/index.php`.
   - Valid rows insert/update `pincode_master`; invalid entries surface in the
     error log and are skipped rather than aborting the entire import.
   - Latitude/longitude columns are optional but recommended for map embeds and
     proximity tools.
2. **Search / View Data**
   - Filter the master table by keyword, state, district, or delivery status.
   - Export the current filter result to CSV for reporting or offline edits.
3. **Post Generator**
   - Select a saved template and generate up to 200 posts. Generated content is
     written to the `generated_posts` table instead of public pages so you can
     review it before publishing.
   - CSV imports do **not** auto-create posts; run the generator after each
     import if you want templated articles.
4. **Pincode Template Editor**
   - Maintain the `pincode_page` template used by the generator. Tokens such as
     `{{pincode}}`, `{{officename}}`, `{{district}}`, and `{{statename}}` will be
     swapped at generation time.
   - Updating the template does not rewrite old generated rows; re-run the Post
     Generator to refresh content.
5. **Sitemap / Router Tool**
   - Create a `public/sitemap.xml` covering the most popular active PIN codes.
     Adjust the per-run limit to control output size.
6. **SEO & Monetisation Suite**
   - Manage on-page SEO (titles, descriptions, keywords, structured data) and
     off-page planning notes in a single place.
   - Store Google Search Console verification tokens, analytics IDs, and ad
     slot markup. Saved values appear automatically in the public layout.

### Admin data tables created automatically

- `admin_users` – credential storage for panel logins.
- `content_templates` – reusable templates for the Post Generator.
- `generated_posts` – output from the generator for later review/export.
- `settings` – key/value store for SEO, analytics, ad, and misc configuration.
- `import_history` – audit log of CSV uploads, counts, and failure reasons.

## Working with generated posts

- Generated posts stay in the database until you export them into your CMS or
  build a front-end that reads `generated_posts`.
- To edit specific posts, update the row directly in the database or rerun the
  generator after tweaking the source template.
- You can create additional templates (new `slug` values) for alternate post
  formats and run the generator separately for each template.

## Map and nearby services integration

Latitude and longitude columns from your CSV imports unlock automated map
embeds and quick links to nearby facilities.

1. **Store coordinates** – ensure the CSV includes `latitude` and `longitude`
   headers (or compatible aliases). The importer persists them to
   `pincode_master`.
2. **Configure the Maps panel** – in the admin “SEO & Monetisation Suite” you
   will find a “Maps & Nearby Places” card. Paste your Google Maps Embed API key
   (optional) and list the categories you want surfaced (one per line, e.g.
   `Hospital`, `ATM`, `Bank`, `Police Station`).
3. **Automatic embeds** – `resources/views/pincode/detail.php` now renders a
   responsive Google Map iframe whenever coordinates exist. If an API key is
   supplied it uses the Embed API, otherwise it falls back to a public maps URL.
4. **Nearby service shortcuts** – the same view turns your configured
   categories into quick Google Maps searches centred on the PIN code’s
   coordinates so visitors can discover government hospitals, post offices, ATM
   branches, banks, and more without custom code.
5. **Advanced caching (optional)** – if you later extend the project with live
   Places lookups, cache results in a dedicated table keyed by pincode and
   refresh them on a schedule to stay within quota limits.

## Tips for Google services & AdSense

- Add your Search Console HTML tag or TXT verification string inside the SEO
  suite under “Additional head HTML”.
- Paste your Google Analytics Measurement ID in the Analytics section to enable
  gtag.js automatically.
- Define AdSense slots (header, in-content, sidebar, footer, etc.) in the
  Monetisation panel. The public layout looks up these placements and injects
  them without further code changes.

## Troubleshooting

- Run `tools/debug.php` from the command line or browser to confirm configuration
  and database connectivity.
- Review `import_history` when imports fail; the admin card shows the first
  50 issues so you can fix and re-upload the CSV.
- If settings do not appear on the public site, ensure the `settings` table has
  the expected values and that the bootstrap cache (`app/helpers/settings.php`)
  is initialized by hitting any page or clearing opcode caches.
