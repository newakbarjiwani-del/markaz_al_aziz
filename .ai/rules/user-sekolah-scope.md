# User school scope (`sekolah_id`)

- `users.sekolah_id` is a nullable FK to `sekolah.id` (Serang model), **not** a legacy `CODE01` string.
- When set, admin list/write queries scope by that school via `AdminSchoolScope` / equivalent helpers.
- `null` means all schools (typically `super_admin`).
- Staff accounts: Super Admin at `/super-admin/users`; Admin at `/admin/manajemen-user` (limited to portal roles per Serang rules).
- Multi-role via `MultiRoleConstraint`: single-role-only `super_admin`, `orang_tua`, `siswa`; others may combine.
- Portal links: siswa → `users` linked to `siswa`; orang tua → pivot `orang_tua_siswa`; guru → `guru`. Scope portal data with `PortalAccess` / role traits — never by guessing legacy CUSTID columns for new work.
- Login gate: `users.status` tinyint (`UserStatus`); disabled users cannot log in (web, API, magic link).
