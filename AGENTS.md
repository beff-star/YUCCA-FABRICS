# AGENTS.md — Yucca Homeware

Instructions for AI coding agents working in this repository.

## Project

Yucca Homeware is an e-commerce site for upholstery fabrics, upholstery services, interior-design
consultations and paid virtual classes (Kenya, prices in KES, times in Africa/Nairobi). Customers browse and
buy fabrics, request services, book consultations and classes; staff manage everything from `/admin`.

The site was ported from PHP to TypeScript on Cloudflare Workers. The old PHP app is kept in `legacy-php/` as a
read-only reference: never edit it or import from it.

## Stack

- Astro 7 with `output: 'server'` on Cloudflare Workers (`@astrojs/cloudflare`), TypeScript (strict), Node 24,
  pnpm 10. Server code reads bindings and vars with `import { env } from 'cloudflare:workers'`.
- Cloudflare D1 (SQLite) through Drizzle ORM (`src/db/schema.ts`, `db` from `~/lib/db`); R2 for uploads
  (`UPLOADS` binding); Cloudflare Email Service for transactional email (`EMAIL` binding, `~/lib/email`).
- Better Auth (email/password) in `src/lib/auth.ts`, called only through `auth.api` from our own pages.
- Astro Actions (`src/actions/`) with Zod for public forms; shared field schemas in `src/actions/fields.ts`.
- Tailwind CSS 4 via `@tailwindcss/vite`. The theme is the `@theme` block in `src/styles/global.css`.
  Colours come from the logo: `forest` #3F5320 (primary), `olive` #5B7029, `sage` #576829 (small labels),
  `clay` #8C5530 (wood-brown accent), `wheat` #E4C59A (accent on dark), `sand` #F7F4EE (page),
  `cream` #EFEADF (alternate sections), `ink` #2A2B25 (text), `night` #252E16 (top bar and footer), plus card
  accents `pine`, `teal`, `terracotta`, `mustard` and a warm `gray-*` scale. Use these tokens, not raw hex
  values, and keep text at WCAG AA contrast (use `wheat` for text on `night`/`forest`; white text fails on
  `mustard`). Fonts are self-hosted with `@fontsource`: Inter (body) and Poppins (`font-serif`, headings).
- Icons: Font Awesome 6 through `astro-icon`, always via `src/components/Icon.astro`.
- M-Pesa (Safaricom Daraja STK push) in `src/lib/mpesa.ts` and `src/lib/payments.ts`; sandbox unless
  `MPESA_ENV` is `production`. Google Calendar/Meet for class links in `src/lib/google.ts`.
- Vitest unit tests in `tests/unit/` (Node, with `cloudflare:workers` stubbed by `tests/stubs/cloudflare-workers.ts`).

## Layout

| Path | Purpose |
| --- | --- |
| `src/pages/` | File-based routes: public pages, `account/`, `admin/`, `auth/`, `classes/`, `api/` endpoints, `media/[...key].ts` (serves R2 uploads), `sitemap.xml.ts`, `robots.txt.ts` |
| `src/layouts/` | `BaseLayout` (head, SEO, JSON-LD, header/footer, flash), `AccountLayout`, `AdminLayout`, `AuthLayout`, `LegalLayout` |
| `src/components/` | Shared UI: `Header`, `Footer`, `ProductCard`, `ServiceCard`, `CourseCard`, `StatusBadge`, `FormAlert`, `FormResult`, `PasswordInput`, `CheckoutSteps`, `MpesaPanel`, `Price`, `Pagination`, … |
| `src/actions/` | Astro Actions: `forms`, `shop`, `payments`, `training` |
| `src/lib/` | Server helpers: db, auth, cart, checkout, orders, payments, mpesa, training, availability, email, uploads, settings, throttle, format, money, … |
| `src/data/` | Static content: `fabric-collection.ts` (lookbook), `service-details.ts`, `courses.ts` |
| `src/db/schema.ts` | Drizzle schema (source of truth for migrations) |
| `src/scripts/` | Browser JS: `site.ts` (menu, reveal, AJAX forms), `ui.ts` (toasts), `booking.ts` (class booking form) |
| `src/styles/global.css` | Tailwind theme and component classes |
| `migrations/` | Drizzle-generated SQL applied to D1 by wrangler |
| `scripts/` | `seed.ts` (writes `seed/seed.sql`), `journal-posts.ts`, `make-admin.ts` |
| `public/images/` | Site images tracked in git: logo, `fabrics/` lookbook photos, `services/` photos (+ `CREDITS.md`) |
| `tests/` | Vitest unit tests and the Workers stub |
| `legacy-php/` | Old PHP site, reference only |

## Local setup

1. `pnpm install`
2. Copy `.dev.vars.example` to `.dev.vars` and fill it in (at least `BETTER_AUTH_SECRET`). Never commit `.dev.vars`.
   Non-secret settings (`SITE_URL`, `TAX_RATE`, `DELIVERY_FEE`, `MPESA_ENV`, `EMAIL_FROM`) are `vars` in `wrangler.jsonc`.
3. `pnpm db:migrate:local` creates the local D1 database (Miniflare, stored in `.wrangler/`).
4. `pnpm db:seed:local` adds categories, the 18-fabric catalogue (placeholder prices), services, the starter
   journal, the 7 virtual courses and default settings. Every insert is `INSERT OR IGNORE` on a unique slug/key,
   so it is safe to re-run and never overwrites admin edits. `seed/` is generated and gitignored.
5. `pnpm dev` and open http://localhost:4321.
6. Register at `/auth/register`, then promote the account: `pnpm make-admin you@example.com` (`--remote` for production).

After changing `wrangler.jsonc` bindings or vars, run `pnpm cf-typegen` to refresh `worker-configuration.d.ts`.

## Conventions

- Pages wrap content in `BaseLayout` (props `title`, `description`, `structuredData`, `image`, `noindex`) or one
  of the section layouts. Use root-relative links (`/shop`); absolute URLs (canonical, sitemap, emails,
  callbacks) are built from `env.SITE_URL`.
- Astro escapes `{}` expressions; never use `set:html` with user or database text. The one exception is journal
  bodies, which must go through `journalBodyHtml()` (escapes first, then a tiny Markdown subset: blank line =
  paragraph, `## ` = heading, `- ` = bullet).
- Queries go through Drizzle (parameterised). Multi-step writes that must succeed or fail together use
  `db.batch()` / `runBatch()`; overlaps and double bookings are prevented with UNIQUE/CHECK constraints, detected
  with `constraintFailed()`.
- Money is stored as integer cents: `formatMoney()`, `parseKes()`, `taxOn()` in `~/lib/money`. Dates and times
  are shown in Nairobi time with the helpers in `~/lib/format`.
- Forms must work without JavaScript. Public forms post to an Action (`action={actions.name}`), show the no-JS
  result with `<FormResult>`, and add `data-ajax-form="name"` to be enhanced by `src/scripts/site.ts`. Admin
  pages handle their own POST in frontmatter with the readers in `~/lib/admin-form` and finish with `done()`
  (post/redirect/get plus a flash message).
- CSRF: `security.checkOrigin` in `astro.config.mjs` rejects cross-site non-GET requests; don't add token fields.
- Auth and access: `src/middleware.ts` loads the session into `Astro.locals.user`, redirects signed-out visitors
  away from `/account` and `/admin`, and serves `/403` to non-admins under `/admin`. Guests reach their own
  orders and class bookings with `canAccess()` (random token in a cookie or emailed link; only its hash is
  stored). Throttle sign-in style endpoints with `allow()` from `~/lib/throttle`.
- Flash messages: `setFlash(cookies, type, message)`; `BaseLayout` shows them on the next page.
- Uploads go through `saveUploadedImage(file, folder)` (magic-byte check, JPEG/PNG/WebP, 5 MB, random name in R2)
  and are served from `/media/<folder>/<name>`.
- Email: `sendEmail()` never throws; an order or booking must not fail because an email did not send.
- Settings stored in the database are read with `getSettings(Astro.locals)` + `setting()`. Use `whatsappUrl()`,
  `socialLinks()`, `telHref()` and `siteNumbers()` (live counts plus admin-verified milestones only; never
  hard-code statistics).
- Status pills use `<StatusBadge>`; labels come from `~/lib/labels` (`statusLabel`, `paymentMethodLabel`).
- Header navigation is defined once in the `navLinks` and `serviceLinks` arrays in `src/components/Header.astro`
  and rendered for desktop and mobile; add pages there, not in two places. New public pages also go in
  `STATIC_PAGES` in `src/pages/sitemap.xml.ts`; private areas go in `src/pages/robots.txt.ts`.
- Put each page block in a top-level `<section>` inside `<main>`: `site.ts` fades sections in on scroll (skipped
  for reduced motion).
- Component classes (`btn-primary`, `btn-secondary`, `form-control`, `form-label`, `eyebrow`, `nav-link`,
  `icon-button`, `mobile-link`, `footer-list`, `back-to-top`, `admin-link`, `journal-body`, `legal-body`, …) live
  in `src/styles/global.css`. Keep markup in the existing style: Tailwind utilities, compact single-line sections.
- Icons: write the full name literally, e.g. `<Icon name="fa6-solid:couch" />`, or as a literal string in a
  `.ts`/`.astro` data object. `astro.config.mjs` scans `src/` and bundles only the names it finds (all three sets
  would add ~10 MB to the Worker), so a name assembled at runtime or stored in the database will not render.
  `Icon` is decorative by default; pass `title` when the icon is a control's only label.
- Errors: catch expected failures (validation, constraint violations, payment API errors) and show a friendly
  message; never show raw database or API error text to visitors. Log with `console.error` (Workers observability is on).
- Footer grids need an explicit `grid-cols-1` on mobile, or a form input's natural width overflows 360 px.

## Images

- Images of the business's own fabrics live in `public/images/fabrics/`, named `<fabric-name>-<fabric-type>.jpeg`
  in lowercase kebab-case (for example `santa-boucle.jpeg`).
- The lookbook list is `src/data/fabric-collection.ts`. When adding a fabric photo, add an entry there with its
  name and type exactly as labelled on the card, and its colour and description to `fabricDetails` in
  `scripts/seed.ts`; the homepage preview, gallery lookbook and catalogue seed update from these. Use
  `fabricImage('file.jpeg')` to build its URL. `PRODUCT_PLACEHOLDER_IMAGE` is the fallback for products without a photo.
- Most lookbook cards are portrait collages with a label in the middle. When cropping one to a wide frame, show the
  top band (`object-top`).
- Service photos live in `public/images/services/`; `src/data/service-details.ts` gives each service (by slug) its
  colour theme, icon, photo, tagline and longer copy, rendered by `ServiceCard`. Services in the
  `fabric-sourcing` / `interior-styling` categories are booked through the consultation form.
- External images must be free to use commercially (e.g. the Unsplash License, never Unsplash+). Download them at
  web size into `public/images/` and record the photographer and source in that folder's `CREDITS.md`.
- Runtime uploads belong in R2 only; never commit them or put site imagery there.
- Every `<img>` needs meaningful `alt` text; add `loading="lazy"` below the fold.

## Database changes

- Edit `src/db/schema.ts`, run `pnpm db:generate`, review the new SQL in `migrations/`, then
  `pnpm db:migrate:local`. Never edit an applied migration; add a new one.
- `pnpm db:migrate:remote` changes production: ask first.

## Verify before reporting done

- `pnpm check` (type check), `pnpm test` (unit tests) and `pnpm build` all pass. If `dist/` is locked by a running
  `wrangler dev`, build elsewhere with `pnpm astro build --outDir <temp folder>`.
- Load the changed pages with `pnpm dev` at about 360, 768 and 1280 px wide: no broken images or icons, no
  horizontal scroll, no console errors. Check forms both with and without JavaScript.

## Safety

- Ask before deploying (`wrangler deploy`), running remote migrations or seeds, `wrangler d1 execute --remote`,
  deleting R2 objects, or loosening `checkOrigin`, middleware guards or security headers.
- Secrets (`BETTER_AUTH_SECRET`, M-Pesa keys, Google client secret, `TOKEN_ENCRYPTION_KEY`) stay in `.dev.vars`
  locally and `wrangler secret put` in production; never print or commit them.
