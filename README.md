# Pincode Directory

This project provides a PHP-based directory for browsing India Post PIN codes with an admin panel, search, and static informational pages.

## Project structure

```
app/
├── bootstrap.php            # Application bootstrap (sessions, config, database, helpers)
├── controllers/             # Route handlers for the public site
├── helpers/                 # Shared helper functions (database, view rendering)
├── routing/                 # Optional routing/sitemap utilities
config/
└── config.php               # Application configuration constants
public/
├── index.php                # Front controller / router
├── admin/                   # Admin dashboard entry point
├── *.php                    # Lightweight redirectors for legacy page URLs
resources/
├── views/layout/            # Shared layout templates
├── views/pages/             # Static page content
└── views/pincode/           # Pincode-related view templates
scripts/                     # CLI utilities (CSV importer, post generator, etc.)
tools/                       # Operational diagnostics (debug, server scan)
database/schema.sql          # Database schema reference
docs/                        # Additional documentation
```

## Getting started

1. Visit `/install.php` in your browser and follow the guided installer to create the configuration file, seed the database schema, and provision the first admin user.
2. (Alternative) Manually update `config/config.php` with database credentials and site constants if you prefer not to use the installer.
3. Ensure your web server points to the `public/` directory as the document root.
4. Configure URL rewriting so requests are routed through `public/index.php` (an Apache-ready `.htaccess` file is included for shared hosting).

## Feature highlights

- CSV imports with automatic column mapping populate the `pincode_master` table and log outcomes in `import_history`.
- Admin search, filtering, and CSV export tools let you audit and reuse the dataset without leaving the panel.
- A templated Post Generator writes preview content to `generated_posts` so you can review or export articles before publishing.
- Built-in SEO, analytics, AdSense, and Maps settings inject your saved snippets into the public layout automatically.
- Nearby PIN suggestions and optional latitude/longitude fields make it easy to add interactive maps, Google Search Console verification, and location-based widgets.

For a complete walkthrough of each admin workflow, generated content management, and map integration tips, see
[`docs/FEATURES_AND_USAGE.md`](docs/FEATURES_AND_USAGE.md).

## Development notes

- Controllers use helper functions in `app/helpers` to render view templates located under `resources/views`.
- Diagnostic utilities have moved to `tools/`; copy individual scripts into your public directory only when needed.
- CLI scripts reside in `scripts/` and can be executed with `php scripts/<script>.php` after configuring the environment.

## Admin panel

The admin dashboard lives at `/admin` and relies on the same configuration/bootstrap used by the public site. Ensure admin users exist in the database before logging in.

## Support

For debugging production deployments, start with `tools/debug.php` to validate configuration, database connectivity, and required PHP extensions.
