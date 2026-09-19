# SPMB (Seleksi Penerimaan Murid Baru)

- Public landing at `/spmb` (own `layouts/spmb`, not admin shell): home, pengumuman, berita, galeri, daftar.
- Guest root `/` redirects to `spmb.home` (via `HomeRedirect`); authenticated users still go to their role dashboard. Login remains at `/login`.
- Admin module `admin.spmb.*` with permissions `spmb.view|create|update|delete`.
- Registration number lives on **`siswa.nomor_pendaftaran`** (unique, nullable). Spreadsheet **NODAF** maps to this column — not `profil_siswa.extra_fields`.
- Accept flow: verify pendaftar → accept with NIS → creates active `Siswa` + Dompet / SaldoKeuangan / KartuSiswa / ProfilSiswa via `SpmbAcceptanceService`.
- Generator: `SpmbRegistrationNumber` → `SPMB-{Ymd}-{NNNN}`.
- Demo content: `php artisan db:seed --class=SpmbDemoSeeder` (tahun ajaran **2027/2028**, periode terbuka + pengumuman/berita/galeri). Also included in `DummyDataSeeder`.
