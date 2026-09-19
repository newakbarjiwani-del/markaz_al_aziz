# Plan: Switch from Tailwind CDN to Vite-compiled CSS

**Status:** deferred — keep temporary CDN + CSS fallbacks for now  
**Created:** 2026-09-07  
**Goal:** Serve a built Tailwind CSS file (no `@tailwindcss/browser` CDN) so utilities work offline / in PWA and layout chrome never depends on a remote script.

---

## Current state (temporary)

| Layer | What we use today |
|-------|-------------------|
| Utilities | `https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4` in `layouts/partials/head.blade.php` |
| Brand tokens | Inline `<style type="text/tailwindcss">` `@theme` (YAYASAN ITTIHAD green/gold) in the same head partial |
| App chrome | Hand-maintained `public/css/app.css` (`?v=` cache-bust) |
| JS | CDN (jQuery, DataTables, Select2, Chart.js, ExcelJS, pdfmake) + `public/js/*` |
| Vite | Already wired for CSS-only build (`vite.config.js` → `resources/css/app.css`); used on `welcome.blade.php` only |
| `node_modules` | Slimmed (~61 MB) — Vite + Tailwind + `concurrently` only |

### Why CDN bites us (PWA)

Portal service worker does **not** precache the Tailwind CDN. If the CDN is slow, blocked, or offline, classes like `hidden`, `-translate-x-full`, `lg:hidden` do nothing.

**Temporary mitigations already in `public/css/app.css`** (do not remove until cutover):

- Lightbox closed / prev-next visibility
- Modal / app-dialog `.hidden`
- Sidebar overlay, flyout, tooltip, mobile translate
- Mobile bottom nav hide on desktop (`lg:hidden` equivalent)
- Other high-risk “show/hide” chrome that previously relied only on Tailwind utilities

These fallbacks can stay as defense-in-depth after cutover, or be thinned once compiled CSS is always present (including SW precache).

---

## Target state

1. Layouts load **built** CSS via `@vite(['resources/css/app.css'])` (or equivalent hashed `public/build/assets/*.css`).
2. Remove `@tailwindcss/browser` script and the inline `@theme` block from head (tokens live in `resources/css/app.css`).
3. Keep `public/css/app.css` either:
   - **Option A (preferred):** import / merge its rules into `resources/css/app.css` so one CSS pipeline ships everything, **or**
   - **Option B:** keep `public/css/app.css` as a second stylesheet for hand-written chrome, and only replace the CDN utilities with Vite output.
4. PWA precache the built CSS URL (and bump `config/pwa.cache_version`).
5. Deploy: `npm ci && npm run build` on the server (or CI artifact); do **not** commit `public/build` (already gitignored).

---

## Blockers / prep before cutover

### 1. Align brand tokens in `resources/css/app.css`

Vite source still has **default blue** primary tokens, not logo green/gold.

Copy the `@theme` block from `layouts/partials/head.blade.php` (primary `#436137`, accent `#c0a830`, full 50–950 scales) into `resources/css/app.css` `@theme { … }`.

Also set `@custom-variant dark (&:where(.dark, .dark *));` (already present) to match head.

### 2. Decide CSS merge strategy

| Option | Pros | Cons |
|--------|------|------|
| **A — single Vite CSS** | One cache, one SW entry, no dual sources | Large move: port `public/css/app.css` (+ `datatable-chrome.css`, maybe `id-card.css`) into resources or `@import` them |
| **B — Vite utilities + keep `public/css/app.css`** | Smaller change; less risk to chrome | Two stylesheets forever; must remember both in SW / `?v=` |

Recommendation: **B first** (swap CDN → `@vite` only), then optionally fold hand CSS into Vite later.

### 3. Scan for CDN-only Tailwind features

Browser CDN can JIT any class in the DOM. Compiled CSS only includes classes found in `@source` paths:

```css
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';
```

Also add if needed:

- `@source '../../public/js/**/*.js';` — many class toggles live in `public/js`, not `resources/js`
- Any Blade that builds class strings dynamically (`'bg-' . $tone`) may need `@source inline(…)` or safelist patterns

After first build, spot-check: portals, admin DataTables, modals, dark mode, PWA offline.

### 4. Keep `node_modules` slim

Already done. Do **not** re-add exceljs/pdfmake/jquery for CSS builds — production still uses CDNs for those.

---

## Implementation steps (when ready)

1. **Update `resources/css/app.css`**
   - Replace blue `@theme` with YAYASAN ITTIHAD tokens from head.
   - Extend `@source` to include `public/js/**/*.js` if class names are toggled there.
   - Optionally `@import` shared chrome or leave Option B.

2. **Wire layouts**
   - In `layouts/partials/head.blade.php` (and guest layout if any):
     - Remove `<script src="…@tailwindcss/browser…">` and inline `<style type="text/tailwindcss">`.
     - Add `@vite(['resources/css/app.css'])` when `hot` or `build/manifest.json` exists.
     - Keep Plus Jakarta + Tabler CDN (or move fonts later).
     - Keep `asset('css/app.css')?v=` if Option B.

3. **Local verify**
   ```bash
   npm ci
   npm run build   # or npm run dev
   php artisan serve
   ```
   Smoke: login, sidebar collapse, modal open/close, lightbox, portal PWA install + offline reload, dark toggle.

4. **PWA**
   - Precache built CSS from Vite manifest (or stable hashed path after build).
   - Bump `config/pwa.cache_version`.
   - Confirm SW does **not** need the Tailwind CDN anymore.
   - Update `tests/Feature/PortalPwaTest.php` if precache list is asserted.

5. **Deploy docs**
   - README / deploy section: after `composer install`, run `npm ci && npm run build` (Node required on build host only).
   - Production: `APP_ENV=production`, no `public/hot`.

6. **Cleanup (optional follow-up)**
   - Revisit temporary `.hidden { display: none !important }` rules — keep critical ones or delete if redundant.
   - Remove unused `resources/js/app.js` stub if never referenced.
   - Consider merging `public/css/app.css` into Vite (Option A).

---

## Deploy checklist

- [ ] Node 20+ on build machine
- [ ] `npm ci && npm run build` produces `public/build/manifest.json`
- [ ] Layouts use `@vite` for CSS; CDN Tailwind removed
- [ ] Brand colors match logo (green/gold) in built CSS
- [ ] PWA precache includes built CSS; cache version bumped
- [ ] Smoke test admin + portal (ortu) offline after one online visit
- [ ] No regression on DataTables / Select2 / modals / lightbox

---

## Out of scope (for this plan)

- Bundling jQuery / DataTables / ExcelJS / pdfmake into Vite (stay on CDN + `public/js`)
- Committing `public/build` to git
- Replacing Tabler Icons webfont
- Full design-system rewrite of `public/css/app.css`

---

## Related files

- `resources/views/layouts/partials/head.blade.php` — CDN + `@theme` today
- `resources/css/app.css` — Vite Tailwind entry (tokens need merge)
- `public/css/app.css` — hand chrome + temporary CDN fallbacks
- `vite.config.js` — CSS-only input
- `package.json` — slim Vite/Tailwind deps (~61 MB `node_modules`)
- `config/pwa.php` / `resources/views/portal/service-worker.blade.php` — cache version + precache
- `tests/Feature/PortalPwaTest.php`
