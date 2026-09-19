# Modul Akademik (Phase 2)

- Admin module `admin.akademik.*` with permissions `akademik.view|create|update|delete` (menu label **Akademik** must match `AdminModuleAccess`).
- Controllers under `Admin\Akademik` use `DataTableTrait` + `jsonSuccess`, school scope via `AdminSchoolScope` (optional `sekolah_id` with `applyWithGlobal` / `resolveOptionalFromRequest`).
- Keep akademik `MataPelajaran` / `JadwalPelajaran` separate from absensi `Pelajaran` / `JadwalAbsen*`.
- Nested JSON CRUD: kurikulum mapel/KD on `KurikulumController`; jadwal slots on `JadwalPelajaranController`.
- Rapor draft/finalize via `RaporBuilderService`; portal siswa/ortu show **final** rapor only (`portal.siswa.rapor.index`, `portal.ortu.rapor.index`).
- Semester values: `App\Support\AkademikSemester` (`ganjil`/`genap`).
- Demo data: `php artisan db:seed --class=AkademikDemoSeeder` (also in `DummyDataSeeder`).
