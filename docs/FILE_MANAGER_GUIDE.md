# 📁 Deployment Guide

Follow these steps when uploading the project through cPanel/File Manager.

---

## 📂 Folder layout (upload into `public_html/`)

```
public_html/
├── public/                    # Set this as your document root
│   ├── index.php              # Front controller & router
│   ├── admin/                 # Admin dashboard entry point
│   ├── about.php              # Legacy redirects → /?route=about
│   └── ...
├── app/                       # Controllers, helpers, bootstrap
├── config/config.php          # Database & site configuration
├── resources/                 # View templates (layout + pages)
├── scripts/                   # CLI utilities (importers, generators)
├── tools/                     # Diagnostic utilities (debug, scan)
├── database/schema.sql        # SQL schema for reference
└── docs/                      # Additional documentation
```

> ✅ **Tip:** Point your hosting control panel to `public_html/public` so only public files are web accessible.

---

## 🚀 Installation checklist

1. **Upload files** keeping the folder structure intact.
2. **Edit `config/config.php`** with your MySQL credentials and site URL.
3. **Import `database/schema.sql`** into your database.
4. **Set the document root** (via cPanel or `.htaccess`) to `public_html/public`.
5. **Ensure URL rewriting** sends all requests to `public/index.php` (use `.htaccess` if on Apache).
6. **Verify permissions**: files `644`, directories `755`.
7. **Visit `/admin`** to access the dashboard (create admin users in the DB first).

---

## 🛠 Useful scripts

| Path                | Purpose                                   |
|---------------------|-------------------------------------------|
| `tools/debug.php`   | Quick environment & DB diagnostics        |
| `tools/scan_server.php` | Inspect file permissions and logs    |
| `scripts/csv_importer.php` | Bulk import PIN data from CSV    |
| `scripts/post_generator.php` | Generate content stubs         |

Run CLI scripts via `php scripts/<script>.php` from the project root.

---

## 🧹 Maintenance

- Keep `tools/` files outside the public web root unless actively troubleshooting.
- Update templates under `resources/views/` to change page content or layout.
- When adding new routes, create a controller in `app/controllers/` and a matching view in `resources/views/`.
