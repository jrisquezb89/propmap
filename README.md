# PropMap

Property search on a map: filter Miami listings by price, bedrooms and type, and see only what's inside the current map view.

**Live demo:** [propmap-alpha.vercel.app](https://propmap-alpha.vercel.app) � **API:** [propmap-api-97jr.onrender.com/api/properties](https://propmap-api-97jr.onrender.com/api/properties?per_page=5)

> The API runs on Render's free tier and sleeps when idle, so the first visit can take up to a minute while it wakes up. The app shows a "Waking up the server�" state while that happens.

| Layer | Stack |
|---|---|
| Backend (`/backend`) | Laravel 13, PHP 8.5, PostgreSQL (Neon), FrankenPHP, Docker on Render |
| Frontend (`/frontend`) | Vue 3, TypeScript, Vite, Pinia, Vue Router, Tailwind v4, Google Maps (Advanced Markers + clustering), on Vercel |

![backend CI](https://github.com/jrisquezb89/propmap/actions/workflows/backend.yml/badge.svg)

## API

Base URL: `http://localhost:8000/api` locally.

### `GET /api/properties`

Paginated list, newest listings first.

| Param | Type | Meaning |
|---|---|---|
| `min_price`, `max_price` | integer (USD) | Inclusive price range |
| `bedrooms` | integer | **At least** N bedrooms ("3+") |
| `type` | `house` \| `condo` \| `townhouse` | Property type |
| `north`, `south`, `east`, `west` | decimal degrees | Map bounding box. All four or none; edges inclusive |
| `per_page` | 1–100 (default 20) | Page size |
| `page` | integer | Page number |

```bash
# Condos up to $1M in the current map view (roughly Brickell)
curl "localhost:8000/api/properties?type=condo&max_price=1000000&north=25.77&south=25.75&east=-80.19&west=-80.205"
```

```jsonc
{
  "data": [{
    "id": 38,
    "title": "Bright Studio Condo in Brickell",
    "price": 530000, "bedrooms": 0, "bathrooms": 1, "sqft": 590,
    "type": "condo",
    "address": "2212 Brickell Bay Dr, Unit 2206, Miami, FL 33131",
    "neighborhood": "Brickell",
    "lat": 25.7557952, "lng": -80.1971007,
    "image_url": "https://images.unsplash.com/photo-...",
    "listed_at": "2026-10-24T19:38:00+00:00",
    "description": "..."
  }],
  "links": { "first": "...", "last": "...", "prev": null, "next": "...&page=2" },
  "meta": { "current_page": 1, "last_page": 17, "per_page": 2, "total": 34 }
}
```

Invalid input returns `422` with per-field `errors`.

### `GET /api/properties/{id}`

A single property (`{ "data": { ... } }`), or `404`.

### `GET /api/health`

`{ "status": "ok" }`. It doesn't touch the database, so it answers quickly. The frontend calls it on page load to wake the free-tier server.

## Running locally

Requirements: PHP 8.3+ with `pdo_pgsql`, Composer, Docker.

```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
docker compose up -d           # Postgres on localhost:5433 (+ a propmap_test database)
php artisan migrate --seed     # 400 Miami listings
php artisan serve              # http://localhost:8000
php artisan test               # feature tests against Postgres
```

Running the production image locally:

```bash
docker build -t propmap-api backend
docker run -p 10000:10000 -e PORT=10000 -e APP_KEY=base64:... \
  -e DB_URL="postgresql://propmap:propmap@host.docker.internal:5433/propmap?sslmode=disable" propmap-api
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env   # VITE_API_URL, VITE_GOOGLE_MAPS_API_KEY, VITE_GOOGLE_MAPS_MAP_ID
npm run dev            # http://localhost:5173
npm test               # Vitest unit tests
npm run build          # type-check + production build
```

Without a Maps key the app still works: the list searches all of Miami and the map shows a "Map unavailable" panel.

## Deploying


1. **Neon**: create a project in AWS us-east-2 and copy the **pooled** connection string.
2. **Render**: New → Blueprint → this repo. `render.yaml` sets up one free Docker web service. Set these env vars in the dashboard:
   - `APP_KEY`: from `php artisan key:generate --show`
   - `DB_URL`: the Neon pooled URL, with `?sslmode=require`
   - `FRONTEND_URLS`: the Vercel domain, comma-separated if there's more than one
   - `APP_URL`
3. **Vercel**: import the repo with root directory `frontend`, and set the three `VITE_*` env vars. `vercel.json` rewrites every path to `index.html`, so deep links like `/properties/38` work. Then add the Vercel domain to `FRONTEND_URLS` on Render.
4. **Google Cloud**: restrict the Maps key to your HTTP referrers (`localhost:5173`, the Vercel domain) and to the Maps JavaScript API, and set a budget alert. The key is public by design (it ships in the JS bundle); restrictions are what secure it.
5. On every boot the container caches config, runs migrations, and seeds only if the table is empty.

Secrets live only in `.env` (gitignored) and in the Render dashboard.

## Design decisions

- **Bounding box on plain `lat`/`lng` columns with B-tree indexes, not PostGIS.** A map viewport query is two range conditions. A composite `(lat, lng)` index narrows by latitude and filters longitude within that range, which is plenty at this scale and needs no extension. For radius search, polygons or millions of rows I'd switch to a PostGIS `geography(Point)` column with a GiST index (Neon supports it).
- **Money is an integer number of dollars.** Floats are never used for money. Listing prices have no cents.
- **`type` is a string column with a CHECK constraint, cast to a PHP backed enum.** The database guarantees integrity, PHP gets type safety, and changing the allowed values later doesn't involve Postgres `ALTER TYPE`.
- **Thin controller.** A Form Request validates input (including cross-field rules like min ≤ max and south ≤ north), chainable model scopes apply each filter, and an API Resource fixes the JSON contract regardless of the schema.
- **Offset pagination** because the UI shows totals and page numbers. Cursor pagination would suit an infinite feed on a large table. Results are sorted by `listed_at DESC, id DESC`, so equal timestamps can't make items jump between pages.
- **Realistic seed data.** Each neighborhood is a hand-drawn polygon, kept slightly inland. Points are generated by rejection sampling with a point-in-polygon test, so no listing lands in Biscayne Bay. Price = sqft × the neighborhood's $/sqft × a factor for the property type × ±15% noise. The Faker seed is fixed, so every environment gets the same 400 listings.
- **Tests run against Postgres, not SQLite**, locally and in CI, so they exercise the same engine as production.
- **FrankenPHP in a single container.** Render's free tier runs one process on `$PORT`. FrankenPHP is PHP plus Caddy in one binary, so there's no nginx + php-fpm + supervisor. The Docker build has two stages, and Composer dependencies are installed in their own cached layer.
- **Idempotent boot tasks.** The free tier has no pre-deploy hook or shell, so the entrypoint runs `migrate --force` and `propmap:seed-if-empty`. Both are safe to run on every restart.
- **Cold starts.** Render's free tier sleeps after 15 minutes idle, and Neon scales to zero. The health endpoint is used to wake the server early, and both services run in the same region.

## Frontend design decisions

- **The URL is the single source of truth for search state.** Filter controls write to the URL and everything else reads from it, so data flows one way and there are no sync loops. Shared links and reloads just work. Filter changes use `router.push`, so Back undoes them; map moves use `router.replace`, so panning doesn't flood the history. Parsing is a pure, unit-tested function that drops invalid values.
- **Google Maps objects are never reactive.** The map, markers and clusterer live in plain variables, not Vue refs: deep proxies around Google's objects are slow and can break their identity checks. Markers are kept in a `Map<id, marker>`, and new results are diffed against it (add/remove only what changed). Hover and selection toggle a CSS class on one pin.
- **Stale responses can't win.** Each search aborts the previous request with an `AbortController`, so a slow response for an old viewport never overwrites the current one. There's a test for this.
- **List and markers stay one-to-one.** The map shows exactly the results in the list (up to 100, the API cap), with a "Showing 100 of 312" notice and Load more. Clustering keeps zoomed-out views readable.
- **Cold-start state machine.** Most loads answer within 2 seconds and show no message. Otherwise a "Waking up the server�" banner and skeleton cards appear, with retries and backoff for up to 90 seconds, then an error with Retry. This is unit-tested with fake timers.
- **Mobile toggle keeps both views mounted.** The hidden one uses `visibility: hidden` + `inert`, not `display: none`: a `display:none` map has zero size (it would report a degenerate viewport), and recreating it costs a new map load.
- **Dark theme via semantic tokens.** Components use tokens like `bg-surface` and `text-fg`, which change under a `.dark` class, so the dark theme is one block of CSS. An inline script applies the saved theme before first paint (no flash). Google's `colorScheme` can only be set when a map is created, so a theme switch remounts the map, which reopens at the same place because the camera lives in the URL.
- **Accessibility.** Cards are real links (stretched-link pattern), markers are keyboard-focusable with descriptive labels, an `aria-live` region announces result counts, focus moves to the page heading on navigation, there's a skip link, price uses native selects (dual sliders are hard to make accessible), and animations respect `prefers-reduced-motion`.

## Known limitations

- Bounding boxes crossing the antimeridian (`west > east`) aren't supported. That can't happen for Miami.
- Each response has at most 100 properties; the UI says so and offers Load more. Many more listings would call for server-side clustering.
