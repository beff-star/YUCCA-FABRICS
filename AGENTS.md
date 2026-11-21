# AGENTS.md — Yucca Fabrics

Instructions for AI coding agents working in this repository.

## Project

Yucca Fabrics is an e-commerce site for upholstery fabrics, upholstery services and interior-design
consultations (Kenya, prices in KES). Customers browse and buy fabrics, request services and book
consultations; staff manage everything from `/admin`.

## Stack

- PHP 8.2 (no framework, no Composer) with PDO, running on XAMPP (Apache 2.4 + MariaDB 10.4).
- Tailwind CSS from the CDN. The theme lives in one file, `assets/js/tailwind-config.js`, loaded by both
  `includes/header.php` and `includes/admin-header.php`. Colours come from the logo:
  `forest` #3F5320 (primary), `olive` #5B7029, `sage` #647536 (small labels), `clay` #8C5530 (wood-brown accent),
  `wheat` #D8B07E (accent on dark), `sand` #F7F4EE (page), `cream` #EFEADF (alternate sections),
  `ink` #2A2B25 (text), `night` #252E16 (top bar and footer). Use these tokens, not raw hex values, and keep
  text at WCAG AA contrast (all text tokens above pass on white and sand; use `wheat` for text on `night`).
  Fonts: Inter (body) and Poppins (`font-serif`, headings).
- Small hand-written CSS in `assets/css/style.css` and vanilla JS in `assets/js/main.js`.
- M-Pesa (Safaricom Daraja) payments in `payment/`, sandbox by default.

## Layout

| Path | Purpose |
| --- | --- |
| `*.php` (root) | Public pages: home, shop, product, cart, checkout, services, consultations, gallery, blog |
| `includes/` | `functions.php` (helpers), `header.php`/`footer.php`, `product-card.php`, `fabric-collection.php` |
| `account/` | Signed-in customer area |
| `admin/` | Staff back office, one folder per section |
| `api/` | JSON endpoints called by `assets/js/main.js` |
| `auth/` | Login, register, password reset |
| `config/` | `config.php` (reads `.env`) and `database.php` (`db()` PDO factory); web access denied |
| `database/` | `yucca_fabrics.sql` schema and `create_admin.php` (CLI only); web access denied |
| `assets/images/` | Site images tracked in git: logo and `fabrics/` lookbook photos |
| `uploads/` | Admin/customer uploads only; gitignored, PHP execution denied |

## Local setup

1. Copy `.env.example` to `.env` and fill in the database credentials. Never commit `.env`.
2. Import `database/yucca_fabrics.sql` into MariaDB as UTF-8:
   `Get-Content database\yucca_fabrics.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4`.
   The file also starts with `SET NAMES utf8mb4;`; without it Windows imports turn `é` and `–` into `├®` / `ÔÇô`.
3. Add the fabric catalogue (18 products with photos, placeholder prices):
   `C:\xampp\php\php.exe database\seed_fabric_products.php`. It copies the photos into `uploads/products/`,
   is safe to re-run and never overwrites products edited in admin. For a new fabric, add the photo and an
   entry in `includes/fabric-collection.php`, then its colour and description to the script's `$details`.
4. Publish the starter journal (6 articles with cover photos): `C:\xampp\php\php.exe database\seed_journal_posts.php`.
   Same rules as the fabric seed: re-runnable, copies images into `uploads/blog/`, never overwrites admin edits.
   Article text supports a tiny safe Markdown subset via `journal_body_html()`: blank line = paragraph,
   `## ` = heading, `- ` = bullet. Everything is escaped first, so never output post bodies any other way.
5. Create an admin: `C:\xampp\php\php.exe database\create_admin.php "Name" email username`.
6. Serve through XAMPP so `.htaccess` rules apply (sitemap/robots rewrites, denied files).

`php` and `mysql` are not on PATH; use `C:\xampp\php\php.exe` and `C:\xampp\mysql\bin\mysql.exe`.

## Conventions

- Every page starts with `require_once __DIR__ . '/includes/functions.php';`, sets `$pageTitle`
  (optionally `$description`, `$structuredData`), then includes the header and footer.
- Build links and asset paths with `url()` (or `absolute_url()` for canonical/schema URLs); never hard-code
  `/YUCCA-FABRICS`.
- Escape all output with `e()`. Use prepared statements for every query with user input.
- State-changing forms include `csrf_token()` in a hidden `csrf_token` field.
- File uploads go through `save_uploaded_image()`, which validates type and size and stores a random
  filename under `uploads/<folder>/`.
- Keep markup in the existing style: Tailwind utility classes, compact single-line sections.
- `includes/header.php` opens `<main id="main">` and `includes/footer.php` closes it. Put each page block in
  a top-level `<section>` inside it: `main.js` fades sections in on scroll (skipped for reduced motion).
- Header navigation is defined once in the `$navLinks` and `$serviceLinks` arrays in `includes/header.php`
  and rendered for both desktop and mobile; add pages there, not in two places.
- Component classes (`nav-link`, `icon-button`, `mobile-link`, `footer-list`, `back-to-top`, `form-control`,
  `eyebrow`) are defined in `assets/css/style.css`.
- Shared page shells: account pages set `$accountActive`, `$accountTitle` (optional `$accountIntro`,
  `$accountAction`) and wrap content in `includes/account-start.php` / `account-end.php`; sign-in pages use
  `includes/auth-start.php` / `auth-end.php`; legal pages use `includes/legal-page.php`.
- UI helpers in `includes/functions.php`: `status_badge()` for any order/payment/booking status,
  `payment_method_label()`, `form_alert($message, 'error'|'success'|'info')`, `password_input_html()` (with
  show/hide toggle), `checkout_steps()` for the bag → checkout → confirmation bar, and `site_numbers()` for
  honest statistics (live counts plus only admin-verified milestones; never hard-code figures).
- `flash('error', …)` messages are shown automatically at the top of the next page by `includes/header.php`.
- Errors: `config/config.php` logs uncaught exceptions and shows visitors a generic page (or JSON for API calls).
  Never echo `$exception->getMessage()` from a `PDOException` to users; catch it before `RuntimeException`
  (it is a subclass) and show a friendly message. Set `APP_DEBUG=true` in `.env` only on your own machine.
- Social links come from `social_links()` in `includes/functions.php` (Instagram, Facebook, TikTok, WhatsApp).
  Blank settings fall back to each platform's home page as a placeholder; use `whatsapp_url()` for any
  WhatsApp link so the admin URL, or the business phone in international format, is used everywhere.
- Footer grids need an explicit `grid-cols-1` on mobile, or a form input's natural width overflows 360 px.

## Images

- Images of the business's own fabrics live in `assets/images/fabrics/`, named
  `<fabric-name>-<fabric-type>.jpeg` in lowercase kebab-case (for example `santa-boucle.jpeg`).
- The lookbook list is `includes/fabric-collection.php`. When adding a fabric photo, add an entry there with
  its name and type exactly as labelled on the card; the homepage preview and gallery lookbook update
  automatically. Use `fabric_image('file.jpeg')` to build its URL.
- Most lookbook cards are portrait collages with a label in the middle. When cropping one to a wide frame,
  show the top band (`object-top` on `<img>`, or `background-position:center top` for backgrounds).
- Service photos live in `assets/images/services/`; `includes/service-details.php` gives each service (by slug)
  its colour theme, icon, photo, tagline and longer copy, rendered by `includes/service-card.php`. Services in
  the `fabric-sourcing` / `interior-styling` categories are booked through the consultation form.
- External images must be free to use commercially (e.g. the Unsplash License, never Unsplash+). Download them
  at web size into `assets/images/` and record the photographer and source in that folder's `CREDITS.md`.
- `PRODUCT_PLACEHOLDER_IMAGE` in `includes/functions.php` is the fallback for products without a photo.
- Do not put site imagery in `uploads/`; that folder is gitignored and reserved for runtime uploads.
- Every `<img>` needs meaningful `alt` text; add `loading="lazy"` below the fold.

## Verify before reporting done

- Lint every changed PHP file: `C:\xampp\php\php.exe -l path\to\file.php`.
- Load the changed pages in a browser at about 360, 768 and 1280 px wide: no broken images,
  no horizontal scroll, no console errors.
- Do not edit the database schema in place; add numbered `.sql` migration files and review them first.

## Safety

- Ask before destructive database commands, deleting user uploads, or changing `.htaccess` deny rules.
- M-Pesa keys and DB passwords stay in `.env`; never print them or commit them.
