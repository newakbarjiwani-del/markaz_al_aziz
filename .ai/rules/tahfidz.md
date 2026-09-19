# Tahfidz (Phase 1)

- Admin module `admin.tahfidz.*` with permissions `tahfidz.view|create|update|delete` (menu label **Tahfidz** must match `AdminModuleAccess`).
- Quran reference: `tahfidz_surah` / `tahfidz_ayat` seeded from `database/seeders/data/quran/` (Al-Fatihah + Juz 30 fixture; see LICENSE.txt). Seed via `TahfidzQuranSeeder` / `TahfidzDemoSeeder`.
- Tracking: `tahfidz_target`, `tahfidz_progress` (status `belum|proses|lancar|mutqin`), `tahfidz_murajaah_log`. Upserts go through `TahfidzProgressService`.
- Admin: dashboard KPIs; DataTables for Progress + Target (`AdminSchoolScope`). Jadwal remains Phase 3 placeholder.
- Portal siswa: mushaf by surah / juz / page + self progress. Guru: list + verify progress. Ortu: read-only child progress/targets.
- Do **not** add in-page `master-data-nav` strips; rely on sidebar + dashboard launcher.
- Voice/murattal/recorder, blanking, quiz, and push reminders are later phases — see `plans/tahfidz.md`.
