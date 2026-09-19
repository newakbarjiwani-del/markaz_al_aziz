# Project rules index

Map of committed conventions. Read every rule file whose globs cover paths you are editing.

This app is a **full copy** of **Serang Nurul Muhtadin** with Yayasan Ittihad Pekanbaru branding. Use Serang domain models (`Siswa`, `Guru`, `Tagihan`, `Dompet`, `Sccttran`, `SccttranCashless`, …) and `users.sekolah_id`. Legacy SIKEU wrappers (`scctcust`, lowercase `sm_*` / `mst_*` / `u_*`) have been removed. Keep Ittihad face/RFID splits — see [identity-storage.md](identity-storage.md).

| Globs | Rule |
|-------|------|
| `README.md`, `.env.example`, `.ai/rules/**` | [project-origin.md](project-origin.md) |
| `app/Http/Controllers/Auth/**`, `app/Support/LoginIdentifier.php`, `resources/views/auth/**` | [auth-username-login.md](auth-username-login.md) |
| `app/Models/User.php`, `app/Http/Controllers/SuperAdmin/**`, `resources/views/super-admin/**`, `database/migrations/*users*` | [user-sekolah-scope.md](user-sekolah-scope.md) |
| `app/Models/Siswa.php`, `app/Models/Guru.php`, `app/Models/Kelas.php`, `app/Models/Sekolah.php`, `app/Http/Controllers/Admin/MasterData/**`, `app/Http/Controllers/Admin/StudentManagement/**`, `resources/views/admin/master-data/**`, `resources/views/admin/manajemen-siswa/**` | [master-data-siswa.md](master-data-siswa.md) |
| `app/Http/Controllers/Admin/Attendance/**`, `app/Http/Controllers/Admin/Cashless/**`, `app/Http/Controllers/Admin/Finance/**`, `app/Services/Finance/**`, `app/Services/Cashless/**`, `resources/views/admin/absensi/**`, `resources/views/admin/dompet-digital/**`, `resources/views/admin/keuangan/**` | [face-cashless-keuangan.md](face-cashless-keuangan.md) |
| `app/Support/FilterHandler.php`, `resources/views/**/filters/**`, `resources/views/layouts/export/**` | [filters-and-pdf.md](filters-and-pdf.md) |
| `resources/views/layouts/**`, `resources/views/components/**`, `public/css/**`, `public/js/**` | [ui-kit-datatable.md](ui-kit-datatable.md) |
| `app/Models/{Siswa,Guru,PengunjungPerpustakaan,Rfid,*Wajah}.php`, `app/Services/RfidResolver.php`, `app/Http/Traits/HandlesFaceCapture.php`, `app/Http/Controllers/**/*{Rfid,RFID,Face,Wajah,Pengunjung,Pos}*.php`, `database/migrations/*{rfid,wajah,face}*`, `database/seeders/**` | [identity-storage.md](identity-storage.md) |
| `app/Http/Controllers/{Admin/Spmb,Spmb}/**`, `app/Models/Spmb*.php`, `app/Services/SpmbAcceptanceService.php`, `app/Support/SpmbRegistrationNumber.php`, `resources/views/{admin/spmb,spmb,layouts/spmb}/**`, `database/migrations/*spmb*`, `database/migrations/*nomor_pendaftaran*` | [spmb.md](spmb.md) |
| `app/Http/Controllers/{Admin/Akademik,Portal/Siswa/RaporController,Portal/OrangTua/RaporController}.php`, `app/Http/Requests/Akademik/**`, `app/Models/{MataPelajaran,Kurikulum*,KompetensiDasar,JadwalPelajaran*,KalenderPendidikan,NilaiEntry,Rapor*}.php`, `app/Services/RaporBuilderService.php`, `app/Support/AkademikSemester.php`, `resources/views/{admin/akademik,portal/siswa/rapor,portal/ortu/rapor}/**`, `database/migrations/*akademik*` | [akademik.md](akademik.md) |
| `app/Http/Controllers/{Admin/Ujian,Portal/Siswa/UjianController}.php`, `app/Http/Requests/Ujian/**`, `app/Models/Ujian*.php`, `app/Services/UjianAttemptService.php`, `resources/views/{admin/ujian,portal/siswa/ujian}/**`, `database/migrations/*ujian*` | [ujian.md](ujian.md) |
| `app/Http/Controllers/{Admin/Booklet,Portal/BookletController}.php`, `app/Http/Requests/Booklet/**`, `app/Models/Booklet*.php`, `resources/views/{admin/booklet,portal/booklet}/**`, `database/migrations/*booklet*` | [booklet.md](booklet.md) |
| `app/Http/Controllers/{Admin/Alumni,Alumni}/**`, `app/Http/Requests/Alumni/**`, `app/Models/Alumni*.php`, `app/Services/AlumniTracerService.php`, `app/Support/AlumniTracerStatus.php`, `resources/views/{admin/alumni,alumni,layouts/alumni}/**`, `database/migrations/*alumni*` | [alumni.md](alumni.md) |
| `app/Http/Controllers/{Admin/Tahfidz,Portal/Siswa/TahfidzController,Portal/Guru/TahfidzController,Portal/OrangTua/TahfidzController}.php`, `app/Http/Requests/Tahfidz/**`, `app/Models/Tahfidz*.php`, `app/Services/Tahfidz*.php`, `resources/views/{admin/tahfidz,portal/*/tahfidz}/**`, `database/migrations/*tahfidz*`, `plans/tahfidz.md` | [tahfidz.md](tahfidz.md) |
