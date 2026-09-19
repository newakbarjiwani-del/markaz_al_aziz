# Ujian Online (Phase 3a MVP)

- Admin module `admin.ujian.*` with permissions `ujian.view|create|update|delete` (menu label **Ujian Online** must match `AdminModuleAccess`).
- Controllers under `Admin\Ujian` use `DataTableTrait` + `jsonSuccess`, school scope via `AdminSchoolScope`.
- Soal embedded on exam (`ujian_soal`); no bank soal in MVP.
- Jenis: `pilihan_ganda` (auto-score via `kunci`) and `essay` (store only).
- Portal siswa: `portal.siswa.ujian.*` — list published in-window exams for kelas/sekolah, start/submit via `UjianAttemptService`.
- `max_attempts` default 1; second start after submit is rejected.
- Demo: `php artisan db:seed --class=UjianDemoSeeder` (also in `DummyDataSeeder`).
- Do **not** add in-page `master-data-nav` strips; rely on sidebar + dashboard launcher.
