# Absensi, Cashless / Dompet Digital, Keuangan (Serang)

- Follow Serang module layout: admin absensi, `dompet-digital` / cashless, `keuangan`, plus Finance API services under `app/Services/Finance`.
- Permissions and menus come from Serang seeders / `config/admin-menu.php`.
- Cashless/finance ledgers: `Sccttran`, `SccttranCashless`, and `SmTopup` where existing Serang flows already write them (`CUSTID` = `siswa.id`). Do not invent SIKEU `sm_tran*` / `scctcust` POS stacks.
- Keuangan: tagihan + `pembayaran` / `pembayaran_detail`; online VA via `FINANCE_JWT_*`, `VA_PREFIX`, `FINANCE_PAYMENT_MODE`, `BIAYA_ADMIN*`, infaq settings in `config/finance.php`.
- QRIS tagihan: `qris_payments` + `qris_payment_tagihan`; generate ke Lazismu (`QRIS_*` in `config/finance.php`); pushNotif `api/finance/qris/push-notif`; settle seperti kasir (`method=qris`, tanpa `sccttran`). Panduan: `docs/keuangan-bayar-tagihan.md`.
- QRIS HTTP ke `103.23.103.43/.../server.php`: POST token di query, **body kosong** + `Content-Type: application/json`. Jangan `Http::asJson()` (mengirim `[]`) — server Lazismu gagal decode JWT dan membalas HTML error, sehingga muncul “Respons server QRIS tidak valid.”
- QRIS `QRIS_VANO_PREFIX` Markaz = `880088` (bukan `111111` dummy). IC switch: `docs/islamic_center/qris/pushNotif.php` → `pushNotif/markaz.php` forward ke `/api/finance/qris/push-notif` (URL di `config/connectMarkaz.php`).
- Face absensi: reference photo via `siswa_wajah` + `has_foto_wajah` (see [identity-storage.md](identity-storage.md)); detection docs in `deteksi_wajah.md` / `Rekam_Wajah.md`.
- RFID: registry `rfid` + `RfidResolver` — not `sm_pin`.
- Hukuman threshold: `HUKUMAN_MIN_POINTS` / `config/prestasi-pelanggaran.php`.
- Manual saldo toggles: `MANUAL_SALDO_KEUANGAN_ADJUSTMENT_ENABLED`, `MANUAL_SALDO_CASHLESS_ADJUSTMENT_ENABLED`.
