# Master data & Data Siswa (Serang schema)

- Controllers under `Admin\MasterData` and `Admin\ManajemenSiswa` use `DataTableTrait` + `datatable-page`.
- Domain models: `Sekolah`, `Kelas`, `TahunAkademik`, `Siswa`, `OrangTua`, `ProfilSiswa`, etc.
- Permissions: `master_data.*` for masters; `students.*` for Data Siswa / import / portal invite.
- Master delete/update blocked when still referenced (`MasterDataUsage`).
- Siswa status is tinyint (`SiswaStatus`: nonaktif / aktif / menunggu). Non-active siswa cannot transact.
- NIS is numeric; VA = `VA_PREFIX` (6) + last 10 NIS digits (`VirtualAccountNumber` / `Siswa::virtualAccountNumber()`).
- **Foto profil** (`profil_siswa.photo_path`, WebP) ≠ **rekam wajah** (`siswa.foto_wajah`, base64) — see `Rekam_Wajah.md`.
- Import Excel: two-step preview (temporary import ~2h); templates via spreadsheet helpers; super admin picks target sekolah.
- Filters: Serang filter forms + school scope from `users.sekolah_id`.
