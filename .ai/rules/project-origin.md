# Project origin (Serang full copy)

- **Source of truth:** Serang Nurul Muhtadin Laravel app (modern schema: `siswa`, `guru`, `tagihan`, `pembayaran`, `dompet` / cashless ledgers, Spatie roles, multi-portal).
- **This repo:** MARKAZ_AL_AZIZ branding (`APP_NAME`, `APP_NAMA_INSTANSI`, `PORTAL_APP_NAME`, logo/colors). Keep feature parity with Serang unless a MARKAZ_AL_AZIZ-specific change is explicit.
- **SIKEU removed:** Do not reintroduce `scctcust`, lowercase `sm_*` / `mst_*` / `u_*` Eloquent wrappers, FaceAbsen, or `cust_id` portal linking. Portal siswa uses `users.siswa_id`.
- **Keep Serang ledger models** `Sccttran` / `SccttranCashless` (`CUSTID` = `siswa.id`) and `SmTopup` where Finance/Cashless already use them.
- **Keep Ittihad identity splits:** `siswa_wajah` / `has_foto_wajah` and global `rfid` + `RfidResolver` (see [identity-storage.md](identity-storage.md)).
- **Public assets:** Serang `public/pwa`, `templates`, frontend `public/vendor`, missing JS/CSS are copied. Brand tokens in `public/css/app.css` / `@theme` follow the Markaz logo (primary brown `#8c4600`, accent gold `#b08d3e`) — do **not** restore Ittihad green `#189e61` / yellow `#f3ea0e` or Serang olive. Do **not** copy Composer PHP `vendor/` — use `composer install`.
- Env keys must stay aligned with Serang’s `.env.example` (VA, finance, seed, PWA, portal, hukuman) plus Ittihad identity values. See root `README.md` → *Asal codebase & status salinan*.
