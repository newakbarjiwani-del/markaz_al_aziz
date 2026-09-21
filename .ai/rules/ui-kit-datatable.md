# UI kit and DataTables

- Shell from Serang: `layouts/app`, `guest`, attendance terminals, `public/css/app.css`, kit JS under `public/js`.
- Brand tokens in `public/css/app.css` + Tailwind `@theme` in `layouts/partials/head`: Markaz logo palette (chocolate brown primary `#8c4600`, gold accent `#b08d3e`). Keep both files in sync; bump `app.css?v=` when tokens change. Prefer flat/layered surfaces without gradient chrome.
- Dashboard shortcuts use shared `<x-dashboard.launcher>` + `.dashboard-launcher*` CSS (not per-module quick-action markup). When syncing Serang CSS, copy launcher/layout rules only — never overwrite Markaz brand tokens or theme-color meta.
- Spacing tokens on `:root` (`--space-*`, …). Prefer tokens over one-off rem values.
- Toast stacking: `.toast-container { position: fixed; z-index: 100; }` — do **not** put Tailwind `z-50` on the markup.
- Server-side DataTables via `DataTableTrait` + `public/js/datatable.js` + `<x-admin.datatable-page>`; column options via `data-column-options`.
- Face enrollment UI: `face-capture-modal` + `face-capture.js` (needs Serang public assets if missing).
- Living style guide: `admin/komponen-ui` (local_only).
- If a Serang view references JS/CSS/PWA files that are missing under `public/`, copy them from the Serang repo rather than rewriting the feature.
