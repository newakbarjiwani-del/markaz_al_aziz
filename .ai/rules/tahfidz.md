# Tahfidz (Halaqoh + Phase 1)

- Admin module `admin.tahfidz.*` with permissions `tahfidz.view|create|update|delete` (menu label **Tahfidz** must match `AdminModuleAccess`).
- Quran reference: `tahfidz_surah` / `tahfidz_ayat` seeded from `database/seeders/data/quran/` (Al-Fatihah + Juz 30 fixture; see LICENSE.txt). Seed via `TahfidzQuranSeeder` / `TahfidzDemoSeeder`.
- Tracking: `tahfidz_target`, `tahfidz_progress` (status `belum|proses|lancar|mutqin`), `tahfidz_murajaah_log`. Upserts go through `TahfidzProgressService`.
- Halaqoh ops: `tahfidz_program`, `tahfidz_halaqoh` + `tahfidz_halaqoh_anggota`, `tahfidz_jadwal` (weekday slots), `tahfidz_rekap` + `tahfidz_rekap_siswa` (tatsbit/murojaah juz lists, hadir/sakit/pulang, total juz, prestasi).
- Weekly recap text via `TahfidzRekapComposer`. Parent WhatsApp uses `wa.me` through `TahfidzRekapWhatsAppService` + `WhatsAppLink` (no API). Admin sees full recap; parents get per-child messages.
- Admin: dashboard KPIs; DataTables for Program, Halaqoh, Jadwal, Progress, Target, Rekap; Kirim WA preview. Scope with `AdminSchoolScope`.
- Portal guru: only halaqoh where `guru_id` matches `users.guru_id`. Ortu/siswa: read-only child/self weekly recap plus Phase 1 progress.
- Do **not** add in-page `master-data-nav` strips; rely on sidebar + dashboard launcher.
- Do **not** reuse `kelas` / `jadwal_pelajaran` / school `absensi_siswa` for halaqoh attendance (`pulang` is tahfidz-specific).
- Voice/murattal/recorder, blanking, and quiz remain later phases — see `plans/tahfidz.md`.
