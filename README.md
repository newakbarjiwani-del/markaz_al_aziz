# Yayasan Ittihad Pekanbaru — Sistem Informasi Sekolah

Aplikasi manajemen sekolah (**Yayasan Ittihad Pekanbaru**) dibangun dengan **Laravel 13**. Codebase ini adalah **salinan penuh** dari proyek **Serang Nurul Muhtadin**, dengan identitas/branding Ittihad. Sistem mencakup panel admin untuk staf sekolah dan portal terpisah untuk guru, orang tua, serta siswa — dengan navigasi dan akses data yang disesuaikan per peran.

## Asal codebase & status salinan

Proyek ini mengikuti arsitektur, skema, modul, dan konvensi **Serang Nurul Muhtadin**. Layer SIKEU lama (`scctcust`, `sm_*` Eloquent wrappers, FaceAbsen, portal `cust_id`) **sudah dihapus**. Identitas sekolah diganti ke Yayasan Ittihad Pekanbaru (`APP_*`, logo, portal name). Ledger Serang `Sccttran` / `SccttranCashless` tetap dipakai. Face/RFID memakai tabel terpisah Ittihad (`siswa_wajah`, `rfid`) — lihat `.ai/rules/identity-storage.md`.

### Sudah disalin / selaras

- `app/`, `config/`, `database/`, `resources/`, `routes/`, seeders, tests (pola Serang modern: `Siswa`, `Guru`, `Tagihan`, `Dompet`, Spatie roles, portal multi-peran)
- Konfigurasi env lengkap di `.env.example` (VA, finance, seed, PWA, portal, hukuman, dll.)

### Aset `public/` & root

Sudah disalin dari Serang: `public/pwa/`, `templates/`, `vendor/` (aset frontend, mis. WhatsApp editor — **bukan** Composer `vendor/`), CSS fitur, JS yang belum ada, `buku.json`, `bun.lock`. `public/storage` via `php artisan storage:link`. Template gambar kartu Serang (`kartu_depan.png` / `kartu_belakang.jpeg`) **tidak dipakai** — kartu pelajar/guru memakai HTML/CSS Ittihad.

**Warna brand Ittihad dipertahankan** di `public/css/app.css` (dan file CSS yang sudah ada) serta `@theme` di `layouts/partials/head` — primary forest green `#189e61` / accent `#f3ea0e`. Jangan timpa dengan palet Serang (`#516b48` / `#b8992a`).

Composer PHP `vendor/` tidak disalin: jalankan `composer install` lokal (catatan: `composer.json` belum 100% identik dengan Serang — DomPDF/Excel vs phpspreadsheet, versi framework/Pest).

Dokumentasi wajah: `Rekam_Wajah.md`, `deteksi_wajah.md`.

Konvensi agen: [`.ai/rules/`](.ai/rules/).

---

## Fitur Utama

### Panel Admin (`/admin`)

Modul lengkap untuk administrator dan super admin:

| Modul | Ringkasan |
|-------|-----------|
| **Master Data** | CRUD **Sekolah**, **Kelas**, **Tahun Akademik** — hapus/ubah diblokir jika data masih dipakai modul lain |
| **Manajemen Siswa** | Data siswa (NIS numerik, nomor VA, **status tinyint** `0` nonaktif / `1` aktif / `2` menunggu; siswa non-aktif tidak bisa transaksi), detail siswa (edit, **foto profil** untuk kartu pelajar, **rekam wajah** terpisah untuk absensi, portal, hapus), **Profil Siswa** (edit nama panggilan/gol. darah; filter cari, kelas, status), orang tua (data ayah & ibu terpisah), riwayat akademik, pindah kelas, kartu pelajar, berkas, **import Excel** (pratinjau dua langkah / **data import sementara** 2 jam; **ekspor** lewat Data Siswa Excel/PDF), **link login portal** (WhatsApp / salin / cabut); form siswa: peringatan + konfirmasi jika ubah NIS (memengaruhi No. VA) |
| **Manajemen Guru** | Data guru (filter sekolah opsional), **detail guru** + kartu guru, **akun login portal** (buat/reset password), **Profil Guru** (read-only: filter cari NIP/nama/jabatan, sekolah, status), kartu guru, riwayat mengajar, **import Excel** (pratinjau dua langkah; **ekspor** lewat Data Guru Excel/PDF) |
| **Prestasi & Pelanggaran** | Dashboard (rekap terbanyak), CRUD prestasi/pelanggaran siswa & guru + upload bukti (gambar → WebP, lightbox), **Rekap per Siswa** (filter tanggal / nama-NIS / min poin), **Hukuman Siswa** (hanya siswa dengan total poin pelanggaran **≥ 250** — daftar eligible + terbit; `HUKUMAN_MIN_POINTS`), **Katalog Prestasi** (kosong awal), **Katalog Pelanggaran** (152 jenis dari form sekolah), import Excel NIS-keyed |
| **Keuangan SPP** | Tagihan, pembayaran kasir multi-tagihan (kuitansi `pembayaran` + baris `pembayaran_detail`), **API online VA/bank** (`sccttran`, `saldo_keuangan`), riwayat kuitansi, **Saldo Siswa** (hanya saldo > 0; `show()` juga mengembalikan `cashless_balance`), **Pindah Saldo** keuangan → cashless (summary hidden by default, estimasi saldo cashless setelah pindah), laporan, rekening |
| **Absensi** | Absensi siswa & guru, QR check-in, **Absensi RFID** (kiosk), rekap, laporan, export |
| **Cashless** | Dompet digital, uang saku, top-up / **tarik saldo** manual (`sccttran_cashless`, tarik: `FIDBANK=CASH`), **PIN cashless** (wajib hanya tarik di atas limit harian), transaksi, **Saldo Cashless** (hanya saldo > 0; detail modal dengan gap form-section), **Saldo RFID (Kiosk)** (tap kartu → saldo cashless; panel saldo keuangan disiapkan tapi disembunyikan / belum memanggil API finance), **Top-up & Tarik Saldo** (summary section hidden by default, **Saldo Akhir** ditampilkan, modalReset reset form), **Transfer Kantin** (`saldo_us` → `saldo_kantin`), **Pengajuan Tambahan** uang saku (approve admin), **Kontrol RFID** (UID, blokir kartu, set/reset PIN), menu kantin, limit kontrol, **Pendapatan Kantin** (omzet POS + **Sisa belum ditarik**), **Tarik Tunai** settlement (`penarikan_pendapatan_kantin`, cash handoff — tidak membalik BELANJA siswa), **Riwayat Penarikan** |
| **Perpustakaan** | Katalog buku (**CRUD** modal + import Excel inventaris Kemenag), peminjaman, pengembalian, denda, **Setting Denda**, rating & review |

Setiap modul memiliki halaman **Setting** yang dikonfigurasi melalui `config/module-settings.php` (item **Setting** modul Keuangan disembunyikan dari sidebar admin; route tetap dapat diakses langsung).

### Portal Pengguna

| Peran | Prefix | Akses |
|-------|--------|-------|
| **Guru** | `/portal/guru` | Absensi siswa (RFID / manual / deteksi wajah), rekap siswa, absensi pribadi, profil, **Prestasi & Pelanggaran** (CRUD **semua siswa**, bukan hanya kelas ajar) |
| **Orang Tua** | `/portal/ortu` | Data anak terhubung (termasuk alamat), tagihan (**No. VA**), **saldo keuangan**, bayar dari saldo, **pindah saldo** keuangan → cashless (summary hidden by default, estimasi saldo cashless setelah pindah), **cashless** (saldo & transaksi + detail, PIN), absensi, perpustakaan, **Link Login** (magic URL), **Prestasi Siswa** (lihat anak; menu Pelanggaran disembunyikan) |
| **Siswa** | `/portal/siswa` | Dashboard sapaan interaktif, profil (alamat; tanpa agama), rekam wajah, tagihan (**No. VA**), pembayaran, absensi, dompet, perpustakaan, **Link Login**, **Prestasi & Pelanggaran** (lihat diri sendiri) |
| **Kantin** | `/portal/kantin` | Dashboard sapaan + omzet, transaksi, menu, scan |
| **Pimpinan** | `/portal/pimpinan` | Dashboard ringkasan absensi/keuangan/kantin + laporan; juga **kelola Prestasi & Pelanggaran** di admin (CRUD + katalog + hukuman) |
| **Perpustakaan** | `/portal/perpustakaan` | Dashboard + katalog CRUD, import, peminjaman/pengembalian (siswa/guru/tamu), denda, setting denda, rekap pengunjung |

Data portal di-scope otomatis: orang tua hanya melihat anak yang terhubung di pivot `orang_tua_siswa`, siswa hanya data miliknya, guru melihat absensi siswa berdasarkan **jadwal absen** yang menugaskan guru tersebut (`jadwal_absen_slot.guru_id`; satu jadwal bisa mencakup beberapa kelas via `jadwal_absen_kelas`). Operator **kantin** dan **perpustakaan** ter-scope per `users.sekolah_id` bila diisi; jika `sekolah_id` kosong, data semua sekolah dapat diakses (kunjungan perpustakaan tanpa sekolah masih butuh entitas siswa/guru/RFID untuk menentukan `sekolah_id` record).

### Data Orang Tua

Satu baris `orang_tua` = satu keluarga, dengan kolom terpisah untuk ayah dan ibu:

| Kolom | Keterangan |
|-------|------------|
| `nama_ayah`, `telepon_ayah`, `email_ayah`, `pekerjaan_ayah` | Data ayah |
| `nama_ibu`, `telepon_ibu`, `email_ibu`, `pekerjaan_ibu` | Data ibu |
| `nama_wali`, `telepon_wali`, `email_wali`, `pekerjaan_wali` | Data wali (opsional; untuk siswa diasuh wali) |
| `alamat`, `status` | Alamat & status |

Relasi ke siswa melalui pivot `orang_tua_siswa` (tanpa kolom `relation`). Form admin: **minimal satu** nama ayah, ibu, atau wali wajib diisi (`ValidatesOrangTuaContact`).

### NIS & Nomor Virtual Account

Setiap siswa memiliki **NIS numerik** (maks. **30** digit; contoh produksi hingga 18 digit). Dari NIS dibentuk **nomor virtual account 16 digit** untuk pembayaran SPP:

| Bagian | Panjang | Sumber |
|--------|---------|--------|
| Awalan (prefix) | 6 digit | `VA_PREFIX` di `.env` |
| 10 digit terakhir NIS | 10 digit | **10 digit terakhir** NIS (nol di depan bila lebih pendek) |

Contoh (`VA_PREFIX=770000`):

- NIS `12345` → VA `7700000000012345`
- NIS `2025001234` → VA `7700002025001234`
- NIS `512336041084210044` → VA `7700001084210044`

Nomor VA ditampilkan di halaman **Data Siswa** (kolom *No. VA*), dihitung otomatis saat mengisi form siswa, serta di **Daftar Tagihan** (admin & portal ortu/siswa) dan header **Pembayaran SPP** setelah memilih siswa. Form siswa menampilkan **peringatan** jika mengubah NIS dapat mengubah No. VA, dan **konfirmasi** (NIS/VA lama → baru) saat menyimpan perubahan NIS. Jika 10 digit terakhir NIS bentrok dengan siswa aktif lain, muncul peringatan soft (pembayaran VA online bisa gagal). Logika ada di `App\Support\VirtualAccountNumber` (`fromNis()`, `nisFromVano()`) dan `Siswa::virtualAccountNumber()`.

### Perpustakaan (Shared & 1-to-N normalized)

Sistem perpustakaan bersifat global dan dapat digunakan lintas sekolah (shared library). Transaksi peminjaman dipecah menggunakan struktur normalized 1-to-N:
- **Tabel `peminjaman` (Parent)**: Menyimpan informasi transaksi utama seperti tipe peminjam (`borrower_type`: siswa, guru, atau tamu), relasi peminjam (`siswa_id`, `guru_id`, data tamu), tanggal pinjam (`loan_date`), tanggal jatuh tempo (`due_date`), catatan pinjam, serta ID admin/staf yang memproses peminjaman.
- **Tabel `peminjaman_buku` (Child)**: Menyimpan detail dari setiap buku yang dipinjam, termasuk `qty` (jumlah eksemplar, default 1), `status` (dipinjam, dikembalikan, hilang), tanggal kembali (`return_date`), jumlah denda (`fine_amount`), jumlah hari terlambat, kondisi pengembalian, catatan pengembalian, jumlah perpanjangan, serta ID admin/staf yang memproses pengembalian/perpanjangan.

Peminjam dapat meminjam beberapa buku sekaligus maupun beberapa salinan (copy) dari buku yang sama dalam satu transaksi. Jumlah salinan diatur melalui field **Jml** pada form peminjaman; backend menyimpan dalam satu baris `peminjaman_buku` dengan kolom `qty` (bukan baris duplikat). Denda keterlambatan dan denda kondisi dihitung secara proporsional terhadap `qty`. Kolom `qty` juga ditampilkan di semua DataTable peminjaman, pengembalian, history, dan denda (admin & portal), serta di modal detail peminjaman.

Semua query datatable perpustakaan (baik admin maupun portal) menggunakan eager loading relasi `peminjaman` dan database table join untuk memastikan query tetap optimal (bebas N+1 query) dan sorting berdasarkan tanggal peminjaman/jatuh tempo berfungsi dengan benar. Statistik dashboard (sedang dipinjam, terlambat, dll.) menggunakan `SUM(qty)` untuk akurasi jumlah eksemplar, bukan jumlah baris.

### Super Admin (`/super-admin`)

Dashboard khusus super admin dengan akses penuh ke semua permission, termasuk menu **UI Kit**, **Manajemen User**, dan **Log Login** di sidebar.

### Manajemen User

- **Super Admin** (`/super-admin/users`): full CRUD user + reset password untuk peran portal (kecuali hapus akun sendiri; tidak bisa reset/ubah password `super_admin` lain). Form menggunakan **`role[]` array multi-select** — pengguna bisa memegang beberapa peran sekaligus (misal `admin` + `guru`).
- **Admin** (`/admin/manajemen-user`): dapat melihat, mengubah, dan reset password user role `siswa`, `orang_tua`, dan `guru`.
- Akses admin dibatasi scope sekolah: jika admin punya `sekolah_id`, hanya bisa kelola user pada sekolah yang sama.
- Admin **tidak** bisa membuat atau menghapus user.
- **Single-role only** (tidak bisa dikombinasikan): `super_admin`, `orang_tua`, `siswa`. Multi-role eligible: `admin`, `guru`, `kantin`, `bendahara`, `cashless`, `pimpinan`, `perpustakaan`. Validasi dilakukan oleh `MultiRoleConstraint`.

**Log Login** (`/super-admin/login-logs`) — audit trail login web (username/email), API Sanctum, dan token portal magic link; filter metode, status, dan rentang tanggal; **modal detail** per baris (browser, platform, perangkat, user agent).

### Profil Akun (`/profil-akun`)

Semua pengguna web (admin, portal, kantin, dll.) dapat mengelola data akun sendiri:

- Ubah nama, email, telepon
- Ubah password (wajib password saat ini)
- Tampilan read-only: username, peran, status, sekolah, profil terhubung (siswa/guru/ortu)

Akses dari **navbar** (klik nama) atau menu sidebar **Profil Akun** (otomatis ditambahkan `MenuService`).

### UI & Navigasi

- Sidebar dinamis per peran (`MenuService` + config menu); ikon unik per item menu (Tabler)
- **Tooltip sidebar** saat sidebar diciutkan di desktop — label menu muncul saat hover/fokus
- **Bottom navigation** di mobile (`config/mobile-bottom-nav.php`) — shortcut per peran, tombol **Menu** membuka sidebar lengkap
- **Dark / light mode** — toggle di navbar; preferensi disimpan di `localStorage` (`theme.js`)
- **Toast notifications** — implementasi custom vanilla JS (`toast.js`); **desktop: kanan atas**; **mobile/tablet: bawah tengah** (di atas bottom nav); pesan sukses menyertakan konteks entitas via `App\Support\ActionMessage`
- **Modal alert & konfirmasi** — validasi form, hapus data, dan pesan error server via `dialog.js` (bukan `alert()` / `confirm()` browser); detail konfirmasi bisa teks biasa atau baris label/nilai lewat `App\Support\ConfirmDetail`
- **Portal login links** — admin dapat buat token, salin link, kirim WhatsApp, dan cabut token dari Data Siswa, Data Orang Tua, dan halaman detail; **Salin link** hanya menyalin token aktif (tanpa membuat token baru); kolom **Token Login** menampilkan status aktif + tanggal kedaluwarsa; tabel otomatis di-refresh setelah buat token / WA / cabut
- **Detail siswa** — halaman `/admin/manajemen-siswa/data-siswa/{id}` dengan edit, **foto profil** (form edit / kartu pelajar) dan **rekam wajah** terpisah (absensi), aksi portal, dan hapus
- **Foto profil vs rekam wajah** — dua sistem terpisah: **foto profil** (`profil_siswa.photo_path`, WebP di storage) untuk kartu pelajar & identitas; blob **rekam wajah** berada di tabel 1:1 `siswa_wajah`, sedangkan daftar memakai flag terindeks `siswa.has_foto_wajah`
- **Dashboard modul** — stat cards responsif + grafik Chart.js (tagihan, absensi, cashless, perpustakaan, dll.); footnote "Tidak termasuk pembayaran yang dibatalkan" pada stat card Penerimaan Bulan Ini (admin keuangan, admin main, pimpinan portal)
- **Dashboard portal** — hero sapaan (Selamat pagi/siang/sore/malam), jam live, tips kontekstual, akses cepat; komponen `<x-portal.dashboard-hero />` + `PortalGreeting`
- **UI Kit** (`/admin/komponen-ui`, local only) — navigasi komponen **accordion** di mobile (collapsed default), sticky sidebar di desktop
- **Select2 AJAX** — pilih siswa, tagihan, atau peminjaman dengan pencarian (min. 3 karakter), tanpa memuat seluruh data di halaman
- DataTables server-side dengan filter kelas, rentang tanggal, layout filter dua baris, dan export Excel/PDF
- Standar kolom DataTables menggunakan `data-column-options` (`orderable`, `searchable`, `exportable`, `html`) — `data-html-columns` tidak dipakai lagi
- Payload DataTables bersifat hybrid: sel sederhana dikirim scalar; sel terformat memakai struktur `{display, raw, type}` untuk sorting/export yang akurat
- Export mengambil semua baris terfilter (bukan hanya halaman aktif) dengan batas aman 3000 baris, ringkasan filter di XLSX/PDF, dan tombol export terkunci saat proses berjalan
- Import data Excel (.xlsx / .xls) untuk **siswa** dan **guru** — kolom template ditampilkan di kartu Import, unduh template, unggah file, **Buat Pratinjau** → pilih metode penyimpanan → **Proses Import**; pratinjau disimpan sebagai **data import sementara** 2 jam dengan paginasi; tombol **Hapus Data Import Sementara**; super admin memilih **sekolah tujuan import**
- Upload **foto profil** siswa/guru via form/FilePond (kartu pelajar, identitas); file disimpan sebagai **WebP** (`ImageConverter`). **Rekam wajah** terpisah via kamera (base64 di `siswa_wajah`) untuk absensi. UID kartu siswa/guru tersimpan secara global-unik di tabel `rfid`; field HTTP tetap `rfid_uid`.
- **Kartu pelajar/guru** — pratinjau di galeri admin/portal dan halaman detail siswa/guru; **cetak** memakai `id-card.js` (clone kartu di halaman yang sama lalu `window.print()` agar hasil cetak sama dengan pratinjau). Siswa dan guru memakai **layout HTML/CSS** (`components/id-card/*`) dengan branding Ittihad — tanpa template gambar Serang.
- Login dengan **username atau email** dalam satu field

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | PHP 8.3, Laravel 13 |
| Auth & RBAC | Session auth, [Spatie Laravel Permission](https://github.com/spatie/laravel-permission) |
| API tokens | Laravel Sanctum |
| Database | SQLite (default), MySQL/MariaDB didukung |
| Frontend | Blade, Tailwind CSS (CDN), Tabler Icons, Select2 4.0.13, Chart.js, FilePond (foto profil), custom drop zone (import Excel) |
| Assets | `public/css/app.css`, `public/js/*` (dialog, theme, ajax-select, fetch-form, datatable, pembayaran, export, dll.) |

## Persyaratan

- PHP ≥ 8.3 dengan ekstensi: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- Composer 2.x
- Node.js 18+ (opsional, untuk Vite dev tooling)
- SQLite atau MySQL

## Instalasi

```bash
# Clone & masuk ke direktori proyek
cd pekanbaru_ittihad

# Salin environment & generate key
cp .env.example .env
php artisan key:generate

# Database SQLite (default)
touch database/database.sqlite

# Migrasi, seed data demo, & symlink storage (foto profil)
php artisan migrate --seed
php artisan storage:link

# Jalankan server
php artisan serve
```

Buka `http://localhost:8000` — akan diarahkan ke halaman login.

Untuk **production**, ikuti tutorial lengkap di bagian [Deploy & Migrasi Production](#deploy--migrasi-production).

### Setup cepat (Composer script)

```bash
composer setup
```

### Development dengan Vite + queue + logs

```bash
composer dev
```

## Akun Demo

Setelah `php artisan db:seed`, gunakan akun berikut (password: **`password`**):

| Peran | Username | Email |
|-------|----------|-------|
| Super Admin | `superadmin` | `superadmin@school.local` |
| Admin | `admin` | `admin@school.local` |
| Guru | `guru` | `guru@school.local` |
| Orang Tua | `ortu` | `ortu@school.local` |
| Siswa | `siswa` | `siswa@school.local` |
| Kantin (MA demo) | `kantin` | `kantin@school.local` |
| Kantin (per sekolah) | `kantin.{paud\|mts\|ma\|takhasus}` | `kantin.{code}@school.local` |
| Cashless | `cashless` | `cashless@school.local` |
| Bendahara | `bendahara` | `bendahara@school.local` |

Login mengarahkan otomatis ke dashboard sesuai peran. Field login menerima **username atau email**. Akun dengan `users.status = 0` (nonaktif) **tidak** bisa login (web, API, magic link).

### API Token (Orang Tua & Siswa)

Untuk aplikasi mobile, gunakan Sanctum bearer token:

```http
POST /api/auth/ortu/login
POST /api/auth/siswa/login
Authorization: Bearer {token}
```

Contoh login ortu:

```json
{
  "login": "ortu",
  "password": "password",
  "device_name": "android-ortu"
}
```

Response berisi `token`, data `user`, dan `profile` (anak terhubung untuk ortu; profil siswa + VA untuk siswa).

Endpoint terproteksi awal:

- `GET /api/ortu/dashboard`, `GET /api/ortu/children`
- `GET /api/siswa/dashboard`, `GET /api/siswa/profil`
- `GET /api/auth/me`, `POST /api/auth/logout`

### API Keuangan Online (Virtual Account / Bank)

Integrasi bank atau middleware pembayaran VA — **bukan Sanctum**; JWT ditandatangani dengan **`FINANCE_JWT_KEY`** (terpisah dari `JWT_KEY` portal).

```http
GET /api/finance/payment?token=<jwt-request>
```

Response: body **plain text** berisi JWT respons — decode untuk membaca `ERR`, `METHOD`, `BILL`, `DESCRIPTION`, dll. (Content-Type: `text/plain`).

**JWT `METHOD`:**

| METHOD | Fungsi |
|--------|--------|
| `INQUIRY` | Cek tagihan belum lunas pertama (urutan `due_date` → `urutan` → `id`) |
| `PAYMENT` | TOP UP (`PAYMENT` − `biaya_admin`) lalu alokasi tagihan |
| `REVERSAL` | Stub (legacy, tidak dipakai) |

**Kode respons:** `ERR` **`00`** = sukses; **`15`** = gagal (ada field `err`). INQUIRY tanpa tagihan belum lunas tetap **`00`** dengan `BILL=0`. PAYMENT yang TOP UP berhasil tapi tidak ada tagihan terbayar tetap **`00`** (saldo terkredit) — tanpa baris `pembayaran`.

**Dua jalur keuangan (jangan digabung):**

| Jalur | Tabel | Keterangan |
|-------|-------|------------|
| **Kasir admin** | `pembayaran`, `pembayaran_detail`, `tagihan` | Satu header kuitansi per transaksi kasir; metadata bayar per tagihan (`reference`, `fidbank`, `user_id`, `sccttran_id`). Tunai/transfer tidak menyentuh `sccttran` / `saldo_keuangan`; metode **SALDO** (`1140002`) debit saldo + `sccttran` `FROM SALDO` |
| **Online API** | `sccttran`, `saldo_keuangan`, `pembayaran`, `pembayaran_detail`, `tagihan` | TOP UP bank → saldo → alokasi tagihan (`FROM INVOICE`); satu header `pembayaran` per REFNO bila ≥1 tagihan lunas |

`sccttran.CUSTID` = `siswa.id`. `NOREFF` = `REFNO` bank (sama di TOP UP & FROM INVOICE; **bukan unique**). Idempotensi: tolak `TOP UP` duplikat dengan `NOREFF` sama.

Mode alokasi (`FINANCE_PAYMENT_MODE`): `auto_loop` (bayar semua tagihan eligible) atau `single` (satu tagihan pertama saja). Hanya lunas penuh — tidak ada cicilan.

```env
FINANCE_JWT_KEY=
FINANCE_PAYMENT_MODE=auto_loop
BIAYA_ADMIN=0

# Infaq (donation) on pindah saldo: off | on | optional
INFAQ_MODE=off
INFAQ_MAX=10000
```

Log gagal PAYMENT: `storage/logs/payment.log`. Detail lengkap: **AGENTS.md → Main finance ledger**.

## Peran & Permission

Peran didefinisikan di `RolePermissionSeeder`. **Multi-role support** — pengguna dapat memegang beberapa peran secara bersamaan (kecuali `super_admin`, `orang_tua`, `siswa` yang single-role only):

- `super_admin` — semua permission
- `admin` — CRUD semua modul operasional (termasuk **Master Data**)
- `guru` — absensi, profil guru, perpustakaan (view)
- `orang_tua` — view data siswa, keuangan, absensi, cashless, perpustakaan (ter-scope ke anak)
- `siswa` — view absensi, cashless, perpustakaan (ter-scope ke diri sendiri)
- `kantin` — operasi kantin POS portal (transaksi, menu) per sekolah
- `bendahara` — admin **Keuangan** + **Absensi** (`sekolah_id` opsional)
- `cashless` — admin **Cashless** (`/admin/dompet-digital`) saja; permission `cashless.*`; `sekolah_id` opsional seperti bendahara
- `pimpinan` — portal laporan absensi/keuangan/kantin; **manage** prestasi/pelanggaran (CRUD + katalog prestasi/pelanggaran + hukuman siswa) di admin
- `perpustakaan` — portal perpustakaan

Contoh penggunaan multi-role: akun `admin` juga bisa menjadi `guru` — sidebar menampilkan menu admin + portal guru (dipisahkan divider), bottom nav mengikuti prioritas role pertama berdasarkan `roles.id`.

Status akun (`users.status`): **`0` nonaktif** / **`1` aktif** — helper `App\Support\UserStatus`. Login web di-throttle **5/menit** per IP + identifier login.

Model `User` memiliki relasi opsional: `siswa_id`, `guru_id`, `orang_tua_id` untuk menghubungkan akun portal ke data entitas.

### Prestasi, Pelanggaran & Hukuman

Modul admin `/admin/prestasi-pelanggaran`:

| Fitur | Keterangan |
|-------|------------|
| **Katalog Prestasi** | Master `jenis_prestasi` (universal, `sekolah_id` nullable); seed **kosong** — diisi lewat UI; form prestasi bisa pilih katalog → isi `judul` + `point` |
| **Katalog Pelanggaran** | Master `jenis_pelanggaran` (152 baris seed); wajib di form pelanggaran; nama terkunci bila sudah dipakai |
| **Rekap per Siswa** | Agregat poin; filter **rentang tanggal**, **nama/NIS**, **min total poin**, sekolah, kelas |
| **Hukuman Siswa** | Hanya siswa dengan Σ poin pelanggaran **≥ `HUKUMAN_MIN_POINTS`** (default **250**); daftar **Siswa Eligible** + terbit hukuman; rekap pelanggaran menampilkan tombol terbit hanya jika eligible |

Detail implementasi: **AGENTS.md → Prestasi & Pelanggaran / Hukuman siswa / Katalog prestasi**.

### Soft Delete

Semua tabel data aplikasi menggunakan **soft delete** (`deleted_at`). Menghapus record dari admin tidak menghapus baris secara permanen — data tetap di database dan tidak muncul di query normal.

- Model domain memakai `BaseModel` (trait `SoftDeletes`); `User` memakai `SoftDeletes` langsung
- NIS, NIP, email, dan field unik lain dapat dipakai ulang setelah record lama di-soft-delete
- Validasi `unique` / `exists` mengabaikan baris yang sudah dihapus (`SoftDeleteRules`)

### Transaksi database (multi-write)

Operasi yang menulis **≥2 baris terkait** (ledger + cache saldo, pindah dompet + audit, batch tagihan, provisi siswa/guru + dompet/kartu, user + role/token) dibungkus **`DB::transaction(fn () => …)`** — tanpa `beginTransaction()` manual. Jalur uang/dompet yang rawan race condition memakai **`lockForUpdate()`** pada baris saldo/status di dalam transaksi.

Contoh: penyesuaian **Saldo Siswa** (`JURNAL SALDO` + `saldo_keuangan`), **Transfer Kantin**, approve **Pengajuan Tambahan**, generate tagihan batch, buat siswa/guru (dompet/kartu), pindah kelas, aktivasi tahun akademik. Detail daftar lengkap: **AGENTS.md → Database transactions (multi-write)**.

## Pencarian AJAX (Select2)

Form yang membutuhkan banyak data siswa/tagihan memakai komponen Blade + Select2:

| Komponen | Kegunaan |
|----------|----------|
| `<x-siswa-select />` | Pilih siswa aktif (tagihan, saldo, pindah kelas, cashless, dll.) |
| `<x-tagihan-select />` | Pilih tagihan (cari nama/NIS, jenis, periode) |
| `<x-peminjaman-select />` | Pilih peminjaman buku aktif |

API lookup (admin, min. 3 karakter):

- `GET /admin/siswa/lookup?term=...`
- `GET /admin/tagihan/lookup?term=...`
- `GET /admin/peminjaman/lookup?term=...`

Halaman **Pembayaran SPP** (kasir) memakai pencarian siswa kustom (`student-search`), tabel tagihan dengan checkbox multi-select, dan pembayaran beberapa tagihan sekaligus — setiap tagihan dibayar **lunas penuh** (`pembayaran.js`). Response kasir menyertakan data kuitansi untuk `payment-receipt.js`; riwayat di **Riwayat Pembayaran** (`riwayat-pembayaran.js`). Jalur ini **terpisah** dari API online VA di atas.

### Tagihan — periode & tahun ajaran

Setiap tagihan punya `tahun_akademik_id` dan `periode` (integer 6 digit: **tahun ajaran akhir + bulan**). Sekolah memakai tahun ajaran **Juli–Juni** (bukan Jan–Des seperti perguruan tinggi). Form memilih bulan kalender; sistem meng-encode otomatis.

| periode | Bulan (kalender) | Tahun ajaran |
|---------|------------------|--------------|
| `202607` | Juli 2025 | 2025/2026 |
| `202601` | Januari 2026 | 2025/2026 |
| `202707` | Juli 2026 | 2026/2027 |
| `202801` | Januari 2028 | 2027/2028 |

Contoh: pilih **Juli 2026** di form → tersimpan `202707` (TA 2026/2027).

- **Status:** `0` = belum lunas, `1` = lunas (tidak ada bayar sebagian/cicilan)
- **Metadata bayar (saat lunas):** `paid_dt` (tanggal bayar bank/bukti), `paid_dt_actual` (waktu server proses), `reference`, `fidbank`, `user_id`, `sccttran_id` — disalin ke `tagihan` dari transaksi pembayaran
- **Tampilan:** `TagihanPeriode::display()` → mis. `Januari 2026 · TA 2025/2026`
- **Form admin:** input bulan (`type="month"`) + pilih tahun akademik + jenis tagihan (sinkron ke sekolah siswa via `tagihan-form.js`)
- **Generate SPP:** pilih tahun akademik, kelas (atau semua), periode; preview API menampilkan siswa eligible vs yang sudah punya tagihan SPP
- **Master jenis tagihan:** `admin/master-data/jenis-tagihan` — nominal default & flag SPP; jenis terpakai hanya bisa diedit sebagian
- Kolom **No. VA** di tabel tagihan (admin & portal) dan di header pembayaran SPP
- Filter **"Tgl Bayar Dari/Sampai"** pada halaman admin/keuangan/tagihan — memfilter berdasarkan `paid_dt` (tanggal bayar bank); menggunakan `FilterTrait::applyDateRange()` dengan custom param names

### Dialog & Validasi Form

Semua form memakai validasi kustom via `public/js/dialog.js`:

| Fungsi | Kegunaan |
|--------|----------|
| `showAlert()` | Peringatan validasi, error server, info |
| `showConfirm()` | Konfirmasi hapus / aksi berisiko (tone, ikon, footnote opsional) |
| `validateFormWithDialog()` | Cek field wajib sebelum submit AJAX |

Tombol hapus / aksi berisiko memakai `data-fetch-delete` atau `data-confirm-submit` dengan modal konfirmasi. Atribut umum: `data-confirm-title`, `data-confirm-message`, `data-confirm-detail`, `data-confirm-tone`, `data-confirm-icon`, `data-confirm-footnote`.

**Detail berlabel** (mis. Nama / NIS pada blokir RFID atau hapus siswa): encode array `[{label, value}, …]` dengan `App\Support\ConfirmDetail::attr($rows)` — **jangan** `Js::from()` di atribut HTML (itu untuk konteks JS dan bisa tampil sebagai JSON mentah). Susun `$rows` di blok `@php` agar Blade tidak error `Unclosed '['`.

Setelah aksi yang mengubah data (simpan form, hapus, token portal, akun guru, dll.), tabel server-side di-refresh otomatis jika halaman memiliki `#main_table`.
### Toast & pesan sukses

Notifikasi toast via `showToast(message, type)`:

| Viewport | Posisi |
|----------|--------|
| Desktop (≥ 1024px) | Kanan atas |
| Mobile / tablet (< 1024px) | **Bawah tengah**, di atas bottom nav |
| Halaman login (guest) | Kanan atas |

Pesan dari server disusun dengan detail kontekstual, contoh:

- `Siswa berhasil diperbarui: Ahmad Siswa · NIS 1000001 · X IPA 1.`
- `Link login portal siswa berhasil dibuat: Ahmad Siswa · NIS 1000001 · X IPA 1.`
- `Top-up dompet kantin Rp 50.000 berhasil: Ahmad Siswa · NIS 1000001 · X IPA 1.`

Helper backend: `App\Support\ActionMessage`.

### Kartu identitas & cetak

| Jenis | Pratinjau | Cetak |
|-------|-----------|-------|
| **Kartu pelajar** | Galeri admin/portal, halaman detail siswa | Tombol **Cetak Kartu** — depan & belakang |
| **Kartu guru** | Galeri admin/portal, halaman detail guru | Tombol **Cetak Kartu** — depan & belakang |

- **Siswa / Guru**: layout HTML/CSS (`id-card/student-*`, `id-card/teacher-*`); foto dari profil; identitas sekolah dari `config('app.*')`
- **Cetak**: `public/js/id-card.js` meng-clone kartu ke `#id-card-print-root` lalu memanggil `window.print()` di halaman yang sama — hasil cetak mengikuti pratinjau (font, warna, clip-path)
- Kartu aktif otomatis dibuat saat siswa/guru baru ditambahkan atau diimpor (`KartuSiswa::provisionFor`, `KartuGuru::provisionFor`)

## Konfigurasi

| File / Env | Keterangan |
|------------|------------|
| `APP_NAME` | Nama aplikasi (default: YAYASAN ITTIHAD PEKANBARU) |
| `DB_*` | Koneksi database |
| `VA_PREFIX` | Prefix 6 digit nomor virtual account siswa (default: `770000`). Digabung dengan NIS zero-pad 10 digit → VA 16 digit |
| `FINANCE_JWT_KEY` | Kunci penandatangan JWT API pembayaran online VA/bank |
| `FINANCE_PAYMENT_MODE` | `auto_loop` (default) atau `single` — alokasi tagihan setelah TOP UP |
| `BIAYA_ADMIN` | Biaya admin (dikurangi dari `PAYMENT` sebelum kredit saldo; default: `0`) |
| `MANUAL_SALDO_KEUANGAN_ADJUSTMENT_ENABLED` | Izinkan penyesuaian saldo keuangan admin (default: `false`) |
| `MANUAL_SALDO_CASHLESS_ADJUSTMENT_ENABLED` | Izinkan top-up / tarik saldo cashless manual (default: `false`). Tarik di atas limit harian membutuhkan PIN cashless siswa. |
| `HUKUMAN_MIN_POINTS` | Ambang poin pelanggaran untuk terbit hukuman / daftar eligible (default: `250`) |
| `TURNSTILE`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile pada login (default: nonaktif) |
| `JWT_*` | Pengaturan token JWT portal (Sanctum login ortu/siswa) — **bukan** `FINANCE_JWT_KEY` |
| `SEED_MODE` | Mode seed database: `full` (demo, default) atau `production` (sekolah/kelas + user awal saja) |
| `SEED_SUPERADMIN_*`, `SEED_ADMIN_*` | Kredensial user awal saat `SEED_MODE=production` (lihat `.env.example`) |
| `config/seed.php` | Mode seed + konfigurasi user production |
| `config/school.php` | Konfigurasi sekolah (`va_prefix` dari env) |
| `config/finance.php` | API keuangan online (`FINANCE_JWT_KEY`, mode alokasi, `biaya_admin`) |
| `config/prestasi-pelanggaran.php` | Ambang hukuman (`hukuman_min_points`) |
| `config/admin-menu.php` | Menu sidebar admin |
| `config/portal-menus/*.php` | Menu sidebar portal per peran |
| `config/mobile-bottom-nav.php` | Item bottom nav mobile per peran |
| `config/module-settings.php` | Field setting per modul admin |

## Struktur Proyek (ringkas)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Panel admin per modul
│   │   ├── Portal/         # Dashboard & portal per peran
│   │   ├── Auth/
│   │   ├── Api/FinancePaymentController.php  # GET /api/finance/payment?token=
│   │   ├── ProfileController.php
│   │   └── SuperAdmin/     # Users + login logs
│   └── Traits/
│       ├── DataTableTrait.php
│       ├── FilterTrait.php
│       ├── PortalAccess.php    # Scope data portal per peran
│       ├── KantinPortal.php    # Scope kantin per sekolah (opsional)
│       ├── PerpustakaanPortal.php  # Scope perpustakaan per sekolah (opsional)
│       └── HandlesProfilePhotoUpload.php
├── Models/
│   └── BaseModel.php           # SoftDeletes untuk semua model domain
├── Support/
│   ├── ActionMessage.php           # Pesan toast kontekstual (siswa, guru, tagihan, …)
│   ├── CashlessPin.php             # Aturan PIN cashless 4 digit
│   ├── ConfirmDetail.php           # Encode data-confirm-detail aman untuk atribut HTML
│   ├── AjaxSelect.php
│   ├── DashboardChart.php
│   ├── DisplayDate.php
│   ├── ImageConverter.php          # Konversi foto → WebP
│   ├── LoginIdentifier.php         # Login username atau email
│   ├── LoginLogMethod.php          # Label metode log login
│   ├── PortalGreeting.php          # Sapaan dashboard portal
│   ├── ProfilePhotoRules.php
│   ├── SoftDeleteRules.php
│   ├── SiswaStatus.php             # siswa.status tinyint
│   ├── UserStatus.php              # users.status tinyint (login gate)
│   ├── VirtualAccountNumber.php  # NIS + VA 16 digit
│   ├── TagihanPeriode.php        # Encode/decode periode tagihan (TA akhir + bulan)
│   ├── AdminSchoolScope.php      # Scope data admin per sekolah (operatorSekolahId)
│   ├── AdminSekolahResolver.php  # Resolve sekolah import (admin vs super admin)
│   ├── MasterDataUsage.php       # Blokir hapus/ubah master data yang masih dipakai
│   ├── ImportPreviewStatus.php / ImportStoreMethod.php
│   ├── StudentSpreadsheetTemplate.php / TeacherSpreadsheetTemplate.php
│   └── ImportSpreadsheetRules.php # Validasi unggah Excel import
└── Services/
    ├── MenuService.php         # Menu sidebar & bottom nav; append Profil Akun
    ├── LoginLogService.php     # Audit login web / API / token portal
    ├── CashlessTransactionGuard.php
    ├── Cashless/
    │   ├── CashlessPinService.php               # Set/ubah/reset PIN cashless
    │   └── PendapatanKantinWithdrawService.php  # Settlement tunai omzet kantin
    ├── Finance/                # API pembayaran online (sccttran, saldo_keuangan)
    │   ├── FinancePaymentService.php
    │   ├── FinanceJwtCodec.php
    │   ├── SccttranLogger.php
    │   ├── TagihanPaymentService.php
    │   ├── PembayaranRecordingService.php
    │   ├── BillAllocator.php
    │   └── Handlers/           # Inquiry, Payment, Reversal
    ├── SpreadsheetImportPreviewService.php
    ├── StudentSpreadsheetImporter.php
    └── TeacherSpreadsheetImporter.php

config/
├── admin-menu.php
├── finance.php                 # FINANCE_JWT_KEY, FINANCE_PAYMENT_MODE, BIAYA_ADMIN
├── mobile-bottom-nav.php
├── module-settings.php
├── school.php                  # VA_PREFIX
└── portal-menus/

public/
├── css/app.css                 # Tokens, toast placement, portal hero, sidebar tooltips, UI kit nav
├── css/id-card.css             # Kartu pelajar & kartu guru (HTML layout); @media print same-page
└── js/                         # sidebar, portal-dashboard, id-card, tagihan-form, ui-components, toast, datatable, …

resources/views/
├── admin/
│   ├── master-data/            # Route: admin/master-data (sekolah, kelas, tahun akademik, jenis tagihan)
│   ├── manajemen-siswa/        # Route: admin/manajemen-siswa
│   ├── keuangan/               # Route: admin/keuangan
│   ├── dompet-digital/         # Route: admin/dompet-digital
│   └── partials/               # dashboard-charts, datatable-page, impor-ekspor
├── portal/                     # Dashboard dengan greeting hero
├── profile/                    # Profil Akun
├── super-admin/                # Users + login-logs
├── layouts/
└── components/                 # portal/dashboard-hero, portal/quick-actions, app-dialog, …

routes/web.php                  # Semua route web
```

## Seeding Data Demo

`DatabaseSeeder` menjalankan seed **full** (default, `SEED_MODE=full`):

1. `SyncServerRolesSeeder` — sync role tambahan di DB lama (cashless, bendahara, …)
2. `RolePermissionSeeder` — peran & permission
3. `DummyDataSeeder` — orchestrator untuk 4 sekolah (PAUD, MTs, MA, Takhasus):
   - `SchoolSeeder` (semua kelas dari `database/seeders/data/kelas.json`)
   - `StudentSeeder` — hingga **10 siswa/kelas** untuk **8 kelas pertama** per sekolah (`MAX_CLASSES_WITH_STUDENTS`; total demo ~220 siswa)
   - `TeacherSeeder`, `AttendanceSeeder`
   - `FinanceSeeder` — tagihan/ledger maksimal **40 siswa/sekolah** (chunk + bulk insert; aman di `memory_limit=128M`)
   - `CashlessSeeder` — pengaturan, menu, sample top-up/belanja
   - `LibrarySeeder`
4. `UserSeeder` — akun demo admin & portal (MA) + `cashless` + `bendahara`
5. `KantinUserSeeder` — satu operator kantin per sekolah (+ legacy `kantin` @ MA)
6. `KantinOmzetSeeder` — BELANJA POS dengan `user_id` operator (multi-hari) + sample **penarikan pendapatan** (outstanding tersisa)

Data siswa dummy menggunakan **NIS numerik** (`{sekolah_id}{counter:06d}`). Orang tua di-seed per keluarga (nama ayah/ibu dari marga siswa, atau `nama_wali` jika kolom WALI diisi). Nomor VA dihitung dari `VA_PREFIX` + NIS.

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

## Deploy & Migrasi Production

Panduan ini untuk **instalasi pertama** di server production (MySQL/MariaDB). Jangan gunakan `migrate:fresh` di production — itu menghapus seluruh data.

### 1. Persyaratan server

- PHP ≥ 8.3 + ekstensi Laravel (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd` atau `imagick` untuk foto)
- Composer 2.x
- MySQL 8+ / MariaDB 10.6+
- Web server (Nginx/Apache) dengan `public/` sebagai document root
- Writable: `storage/`, `bootstrap/cache/`

### 2. Deploy kode

```bash
git clone <repo-url> /var/www/pekanbaru_ittihad
cd /var/www/pekanbaru_ittihad
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

### 3. Konfigurasi `.env` production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-sekolah.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pekanbaru_ittihad
DB_USERNAME=...
DB_PASSWORD=...

VA_PREFIX=770000

FINANCE_JWT_KEY=<kunci-rahasia-bank>
FINANCE_PAYMENT_MODE=auto_loop
BIAYA_ADMIN=0

# Infaq (donation) on pindah saldo
INFAQ_MODE=off
INFAQ_MAX=10000

# Ambang poin untuk terbit hukuman (default 250)
HUKUMAN_MIN_POINTS=250

# Wajib untuk seed production — jangan pakai password demo
SEED_MODE=production
SEED_SUPERADMIN_USERNAME=superadmin
SEED_SUPERADMIN_PASSWORD=<password-kuat>
SEED_SUPERADMIN_EMAIL=superadmin@sekolah.example
SEED_ADMIN_ENABLED=true
SEED_ADMIN_USERNAME=admin
SEED_ADMIN_PASSWORD=<password-kuat>
SEED_ADMIN_EMAIL=admin@sekolah.example
SEED_ADMIN_SEKOLAH_CODE=ma
```

| Variabel | Keterangan |
|----------|------------|
| `SEED_MODE=production` | `DatabaseSeeder` hanya menjalankan `ProductionSeeder` (bukan data demo) |
| `SEED_SUPERADMIN_PASSWORD` | Wajib; user `super_admin` tanpa `sekolah_id` |
| `SEED_ADMIN_PASSWORD` | Wajib jika `SEED_ADMIN_ENABLED=true` |
| `SEED_ADMIN_SEKOLAH_CODE` | Opsional — link admin ke sekolah (`paud`, `mts`, `ma`, `takhasus`) |

### 4. Buat database & jalankan migrasi

```bash
# Buat database kosong di MySQL terlebih dahulu, lalu:
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Urutan production seeder** (`ProductionSeeder`, idempotent):

| Urutan | Isi |
|--------|-----|
| `RolePermissionSeeder` | Peran & permission (termasuk `master_data.*`) |
| `ProductionSchoolSeeder` | 4 sekolah + kelas awal (katalog `DummySchoolCatalog`) |
| `ProductionUserSeeder` | `super_admin` + `admin` dari env |

Tidak meng-seed siswa, guru demo, keuangan, cashless, perpustakaan, atau akun portal demo. Username yang sudah ada **dilewati** (aman dijalankan ulang).

### 5. Permission direktori

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

Sesuaikan user/group dengan web server Anda.

### 6. Setelah deploy — langkah operasional

1. Login sebagai **super admin** → verifikasi menu **Master Data** (Sekolah, Kelas, Tahun Akademik)
2. Sesuaikan master data sekolah/kelas/tahun akademik jika perlu
3. Import siswa & guru lewat **Import/Export** (Excel, lihat di bawah)
4. Buat akun portal guru/kantin via **Super Admin → Manajemen User** atau fitur akun guru di Data Guru
5. Opsional: aktifkan Turnstile (`TURNSTILE=true`) dan isi kunci Cloudflare

### 7. Migrasi berikutnya (update aplikasi)

Saat deploy versi baru **tanpa** reset database:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Jangan** jalankan `db:seed` ulang kecuali menambah environment baru — production seeder idempotent tapi tidak mengubah password user yang sudah ada.

#### Migrasi kolom status (database lama)

Jika production **sudah** punya data sebelum status disimpan sebagai tinyint, jalankan skrip SQL manual (atau pastikan `php artisan migrate` menjalankan migrasi konversi):

| Tabel | Skrip | Mapping |
|-------|--------|---------|
| `siswa.status` | [`database/sql/2026_07_13_convert_siswa_status_to_tinyint.sql`](database/sql/2026_07_13_convert_siswa_status_to_tinyint.sql) | `nonaktif`→0, `aktif`→1, `pending`→2 |
| `users.status` | [`database/sql/2026_07_14_convert_users_status_to_tinyint.sql`](database/sql/2026_07_14_convert_users_status_to_tinyint.sql) | `nonaktif`/disabled/blocked→0, `aktif`/active→1 |
| Potongan tagihan (tables + `tagihan.amount_bruto`/`potongan_amount`) | [`database/sql/2026_08_28_potongan_tagihan_schema.sql`](database/sql/2026_08_28_potongan_tagihan_schema.sql) + [`database/sql/2026_08_28_sync_potongan_permissions_live.sql`](database/sql/2026_08_28_sync_potongan_permissions_live.sql) | New module; run permissions script then `php artisan permission:cache-reset` |

Jalankan sebelum atau bersamaan dengan deploy kode yang mengharapkan tinyint. **Jangan** `migrate:fresh` di production.

### 8. Rollback migrasi (hati-hati)

```bash
php artisan migrate:rollback --step=1 --force
```

Hanya jika migrasi terakhir bermasalah dan belum ada data produksi kritis.

### Seeding Development (lokal)

Mode default `SEED_MODE=full` — data demo lengkap (lihat **Seeding Data Demo** di atas: cap siswa/finance + `KantinOmzetSeeder`). Cocok untuk PHP `memory_limit=128M`.

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

---

### Import Excel (siswa & guru)

Halaman **Import/Export** di masing-masing modul (`admin/manajemen-siswa/impor-ekspor`, `admin/manajemen-guru/impor-ekspor`):

| Modul | Template | Route template | Ekspor |
|-------|----------|----------------|--------|
| Siswa | `public/templates/format-input-siswa.xlsx` | `GET admin/manajemen-siswa/impor-ekspor/template` | Tombol → **Data Siswa** (DataTables Excel/PDF); `impor-ekspor/export` redirect ke sana |
| Guru | `public/templates/format-input-guru.xlsx` | `GET admin/manajemen-guru/impor-ekspor/template` | Tombol → **Data Guru** (DataTables Excel/PDF); `impor-ekspor/export` redirect ke sana |

**Alur import (dua langkah):**

1. **Kartu Import** — baca **Kolom template**, unduh template Excel, pilih sekolah (super admin), unggah file (`.xlsx`/`.xls`, max 10 MB), klik **Buat Pratinjau**
2. **Panel Pratinjau** — ringkasan baris (baru / perbarui / tidak valid), tabel dengan paginasi (10/25/50/100), pilih **Metode Penyimpanan**, klik **Proses Import**; opsional **Hapus Data Import Sementara**

| Metode | Perilaku |
|--------|----------|
| Tambah dan perbarui data | Baris baru + baris existing (by NIS/NIP) |
| Hanya tambah data baru | Lewati baris yang NIS/NIP sudah ada |
| Hanya perbarui data existing | Lewati baris yang NIS/NIP belum ada |

Pratinjau disimpan sebagai **data import sementara** di server **2 jam** per user. Super admin (tanpa `sekolah_id`) wajib memilih **Sekolah Tujuan Import**; admin biasa otomatis ter-scope ke sekolahnya (`AdminSekolahResolver`).

Import membuat/update record by **NIS** (siswa) atau **NIP** (guru) per sekolah.

**Validasi terhadap Master Data:**

| Entitas | Siswa | Guru |
|---------|-------|------|
| **Sekolah** | Harus sudah ada — resolver memvalidasi `sekolah_id` (admin terhubung atau pilihan super admin); tidak auto-create | Sama |
| **Kelas** | **Harus sudah ada** di Master Data — baris dengan kelas tidak dikenal ditandai invalid di pratinjau | Tidak relevan — guru import **tidak** memakai/men-assign kelas |

Pastikan **sekolah** dan **kelas siswa** sudah di Master Data sebelum import siswa.

## Testing

```bash
composer test
# atau
php artisan test
```

Proyek menggunakan [Pest](https://pestphp.com/) untuk testing. Test feature memakai `RefreshDatabase` pada sqlite `:memory:` (lihat `tests/TestCase.php`) — **jangan** mendefinisikan ulang skema tabel di test; andalkan migrasi.

Helper finance: `Tests\Support\FinanceFixtures` — `schoolWithStudent()`, `jenisTagihan()`, `tagihan()`, `adminUser()`.  
JWT API: `Tests\Support\FinanceJwtTestHelper` — encode/decode token untuk test.

Contoh test feature:

- `tests/Feature/FinancePaymentApiTest.php` — API keuangan online (INQUIRY termasuk `BILL=0` tanpa tagihan, PAYMENT, ERR 00/15)
- `tests/Feature/PembayaranTest.php` — kasir bulk pay → pembayaran + detail + metadata tagihan
- `tests/Feature/RiwayatPembayaranTest.php` — riwayat kuitansi & endpoint receipt
- `tests/Feature/PortalOrtuPindahSaldoTest.php` — portal ortu pindah saldo keuangan → cashless
- `tests/Feature/ManualSaldoAdjustmentTest.php` — top-up / tarik saldo cashless (`FIDBANK=CASH`) + PIN over-limit
- `tests/Feature/CashlessPinTest.php` — set/ubah/reset PIN (admin/cashless); ortu ubah anak terhubung saja
- `tests/Unit/ConfirmDetailTest.php` — encode detail konfirmasi dialog aman untuk atribut HTML
- `tests/Feature/PendapatanKantinTest.php` — pendapatan kantin, Tarik Tunai, void penarikan, scope sekolah
- `tests/Unit/UserStatusTest.php` — status akun user (aktif/nonaktif)
- `tests/Feature/StudentExportTest.php` — impor-ekspor siswa redirect ekspor ke Data Siswa
- `tests/Feature/SccttranSaldoTest.php` — daftar saldo keuangan (default saldo > 0) + penyesuaian JURNAL SALDO
- `tests/Feature/TransferKantinAndPengajuanTransactionTest.php` — transfer dompet kantin + approve pengajuan uang saku (atomik; tolak saldo kurang / double-approve)
- `tests/Unit/VirtualAccountNumberTest.php` — format NIS & VA 16 digit
- `tests/Unit/TagihanPeriodeTest.php` — encode/decode periode tagihan (TA akhir + bulan)
- `tests/Feature/TagihanTest.php` — status tagihan biner & sync pembayaran
- `tests/Feature/TagihanGenerateTest.php` — generate SPP (preview, filter kelas, alignment sekolah)
- `tests/Feature/AdminSchoolScopeTest.php` — scope admin per `sekolah_id`
- `tests/Unit/ActionMessageTest.php` — format pesan toast kontekstual
- `tests/Feature/StudentLookupTest.php` — API lookup siswa (min. 3 karakter)
- `tests/Feature/SoftDeleteTest.php` — soft delete & reuse NIS
- `tests/Feature/LoginTest.php` — login via username/email, throttle 429, blokir user nonaktif
- `tests/Feature/ImageConverterTest.php` — konversi WebP pada upload foto
- `tests/Feature/ApiPortalAuthTest.php` — token login Sanctum ortu/siswa
- `tests/Feature/PortalAccessTest.php` — link portal admin, revoke, kolom token, halaman detail
- `tests/Feature/PortalSelfAccessTokenTest.php` — Link Login portal ortu/siswa
- `tests/Feature/GuruAccountTest.php` — akun login guru & kolom status di Data Guru
- `tests/Feature/ProfileTest.php` — Profil Akun & ubah password
- `tests/Feature/LoginLogTest.php` — audit log login & akses super admin
- `tests/Unit/UserAgentInfoTest.php` — parsing browser/platform/device dari user agent
- `tests/Feature/ProfilSiswaTest.php` — edit profil tambahan siswa
- `tests/Feature/StudentImportTest.php` — import Excel siswa + validasi kelas master data
- `tests/Feature/TeacherImportTest.php` — import Excel guru
- `tests/Feature/TeacherSekolahTest.php` — guru opsional per sekolah + filter Data Guru
- `tests/Feature/StudentStoreTest.php` — buat siswa manual + provisi kartu pelajar; cek bentrok 10 digit terakhir NIS (VA)
- `tests/Feature/BukuImportTest.php` — import inventaris buku Kemenag
- `tests/Feature/BukuCatalogCrudTest.php` — CRUD katalog buku (validasi keadaan, ISBN, hapus dengan pinjaman aktif)
- `tests/Feature/LibraryLoanTest.php` — peminjaman buku (siswa/guru/tamu) & denda
- `tests/Feature/PrestasiPelanggaranTest.php` — CRUD prestasi/pelanggaran, rekap filter, hukuman (≥250 / eligible / tolak di bawah ambang), permission, scope portal
- `tests/Feature/PrestasiPelanggaranImportTest.php` — import Excel prestasi/pelanggaran (template, preview valid/invalid/duplikat, confirm create, index pages)
- `tests/Feature/KatalogPelanggaranTest.php` — CRUD katalog pelanggaran, name-lock, lookup
- `tests/Feature/KatalogPrestasiTest.php` — CRUD katalog prestasi (seed kosong), name-lock, store dengan `jenis_prestasi_id`
- `tests/Feature/SaldoRfidKioskTest.php` — kiosk saldo RFID cashless (lookup, kartu tidak dikenal / siswa nonaktif; tanpa panggilan API finance)
- `tests/Feature/ProductionSeederTest.php` — production seed (sekolah, kelas, user)
- `tests/Feature/MasterDataTest.php` — CRUD master data, blokir hapus saat dipakai, edit terbatas jenis tagihan
- `tests/Feature/MultiRoleTest.php` — multi-role CRUD, constraint single-role, self-lock, output, menu merging, bottom nav, canEdit/canReset
- `tests/Unit/PortalGreetingTest.php` — sapaan berdasarkan jam

## Lisensi

Proyek aplikasi ini menggunakan framework [Laravel](https://laravel.com) yang dilisensikan di bawah [MIT license](https://opensource.org/licenses/MIT).
