# Booklet Sekolah (Phase 3b MVP)

- Admin module `admin.booklet.*` with permissions `booklet.view|create|update|delete` (menu label **Booklet Sekolah** must match `AdminModuleAccess`).
- Controllers under `Admin\Booklet` use `DataTableTrait` + `jsonSuccess`, school scope via `AdminSchoolScope`.
- Nested pages on booklet show (`booklet_page`: title/body/file); cover + page files on `public` disk under `booklet/`.
- Portal read-only via shared `Portal\BookletController` for **siswa / ortu / guru** (`portal.*.booklet.index|show`); only `is_published` booklets; sekolah filter via `visibleForSekolah`.
- Do **not** add in-page `master-data-nav` strips.
- Demo: `php artisan db:seed --class=BookletDemoSeeder` (also in `DummyDataSeeder`).
