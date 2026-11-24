# Yucca Homeware

Online shop and booking site for Yucca Homeware, a Kenyan upholstery and interior-design studio.

Customers can:

- browse and buy upholstery fabrics (bag, coupons, checkout, M-Pesa or bank transfer)
- request upholstery services and book interior-design consultations
- book paid virtual classes on Google Meet, Zoom or WhatsApp video
- read the journal, view the fabric lookbook and manage orders, bookings and favourites in their account

Staff run the business from `/admin`: orders, products and categories, services, consultations, service
requests, classes (bookings, courses, opening hours, Google Meet), customers, coupons, reviews, journal and
gallery, messages, reports with CSV export, and website settings.

## Tech stack

| Part | Choice |
| --- | --- |
| Framework | [Astro 7](https://astro.build) (server rendering), TypeScript |
| Hosting | Cloudflare Workers via `@astrojs/cloudflare` |
| Database | Cloudflare D1 (SQLite) with [Drizzle ORM](https://orm.drizzle.team) |
| File storage | Cloudflare R2 |
| Auth | [Better Auth](https://www.better-auth.com) (email and password) |
| Styling | Tailwind CSS 4, self-hosted Inter and Poppins fonts, Font Awesome icons |
| Payments | M-Pesa STK push (Safaricom Daraja), sandbox by default |
| Email | Cloudflare Email Service |
| Tests | Vitest |

The previous PHP/MySQL version of the site is kept in `legacy-php/` for reference only.

## Getting started

Requirements: Node 24 and pnpm 10.

```powershell
pnpm install
Copy-Item .dev.vars.example .dev.vars   # then fill in the values (at least BETTER_AUTH_SECRET)
pnpm db:migrate:local                   # create the local D1 database
pnpm db:seed:local                      # add the catalogue, services, journal and courses
pnpm dev                                # http://localhost:4321
```

To get into `/admin`, register an account at `/auth/register`, then run:

```powershell
pnpm make-admin you@example.com
```

The local database, R2 bucket and email are simulated by Wrangler (Miniflare) and stored in `.wrangler/`.

## Scripts

| Command | What it does |
| --- | --- |
| `pnpm dev` | Start the dev server |
| `pnpm build` | Build the Worker into `dist/` |
| `pnpm preview` | Build, then run the built Worker with `wrangler dev` |
| `pnpm check` | Type-check Astro and TypeScript files |
| `pnpm test` | Run the unit tests |
| `pnpm db:generate` | Generate a SQL migration after editing `src/db/schema.ts` |
| `pnpm db:migrate:local` / `db:migrate:remote` | Apply migrations to the local / production database |
| `pnpm db:seed:local` | Seed the local database (safe to re-run; never overwrites admin edits) |
| `pnpm make-admin <email> [--remote]` | Give an existing account admin access |
| `pnpm cf-typegen` | Regenerate binding types after changing `wrangler.jsonc` |

## Configuration

Non-secret settings are `vars` in `wrangler.jsonc`: `SITE_URL`, `TAX_RATE`, `DELIVERY_FEE`, `MPESA_ENV`
(`sandbox` or `production`) and `EMAIL_FROM`. Set `SITE_URL` to the real domain before going live; it is used for
canonical links, the sitemap, emails and the M-Pesa and Google callback URLs.

Secrets are listed in `.dev.vars.example`. Locally they go in `.dev.vars` (gitignored); in production set each
one with `pnpm wrangler secret put NAME`.

- **M-Pesa**: Daraja consumer key and secret, shortcode, passkey, and `MPESA_CALLBACK_TOKEN` (a random string;
  the callback URL is `/api/mpesa/callback/<token>`).
- **Google Meet** (optional): an OAuth client with redirect URI `<SITE_URL>/api/google/oauth-callback`, plus
  `TOKEN_ENCRYPTION_KEY`. Connect the studio's Google account in Admin → Settings → Google.

## Deploying

```powershell
pnpm build
pnpm wrangler deploy          # first deploy creates the D1 database and R2 bucket
pnpm db:migrate:remote
node scripts/seed.ts; pnpm wrangler d1 execute yucca-db --remote --file=seed/seed.sql
pnpm make-admin you@example.com --remote
```

Then replace the placeholder fabric prices and business details in `/admin`.

## Project structure

```text
src/
  pages/        routes (public pages, account/, admin/, auth/, classes/, api/)
  layouts/      page shells (base, account, admin, auth, legal)
  components/   shared UI
  actions/      form handlers (Astro Actions)
  lib/          server logic: database, auth, cart, checkout, payments, classes, email, uploads
  data/         fabric lookbook, service details, course list
  db/schema.ts  database schema
  scripts/      browser JavaScript
  styles/       Tailwind theme and component classes
migrations/     SQL migrations for D1
scripts/        seed and admin scripts
public/images/  logo, fabric and service photos
tests/          unit tests
```

Contributor and AI-agent conventions are in [AGENTS.md](AGENTS.md).
