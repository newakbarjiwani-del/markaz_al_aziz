# Alumni / Tracer Study (Phase 3c MVP)

- Admin module `admin.alumni.*` with permissions `alumni.view|create|update|delete` (menu label **Alumni** must match `AdminModuleAccess`).
- Controllers under `Admin\Alumni` use `DataTableTrait` + `jsonSuccess`, school scope via `AdminSchoolScope`.
- Tables: `alumni` registry + `alumni_tracer` (unique per alumni + tahun_tracer).
- Status lulusan values: `App\Support\AlumniTracerStatus`.
- Public guest form: `/alumni/tracer` (`layouts.alumni`, throttled) via `AlumniTracerService` (match by NIS/email or create).
- Admin: alumni CRUD + nested tracer on show; tracer rekap datatable at `admin.alumni.tracer.*`.
- Do **not** add in-page `master-data-nav` strips.
- Demo: `php artisan db:seed --class=AlumniDemoSeeder` (also in `DummyDataSeeder`).
