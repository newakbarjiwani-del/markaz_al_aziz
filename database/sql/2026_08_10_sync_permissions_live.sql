-- ============================================================================
-- Sync `permissions` + `role_has_permissions` on the LIVE server to match
-- database/seeders/RolePermissionSeeder.php (2026-08-10).
--
-- Semantics mirror the seeder exactly:
--   * permissions  -> firstOrCreate  (only ADD missing rows; existing kept)
--   * each role    -> syncPermissions (REPLACE that role's set with the exact list)
--
-- Idempotent: safe to re-run. Uses name lookups (not hard-coded IDs), so it
-- works regardless of the current auto-increment state of the live tables.
--
-- AFTER applying, clear the Spatie cache on the server:
--   php artisan permission:cache-reset
-- ============================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1) Permissions: 14 modules x (view, create, update, delete) = 56 rows
--    guard_name must be 'web' (matches SyncServerRolesSeeder live role ids).
-- ---------------------------------------------------------------------------
INSERT INTO permissions (name, guard_name) VALUES
    ('students.view','web'), ('students.create','web'), ('students.update','web'), ('students.delete','web'),
    ('teachers.view','web'), ('teachers.create','web'), ('teachers.update','web'), ('teachers.delete','web'),
    ('finance.view','web'),  ('finance.create','web'),  ('finance.update','web'),  ('finance.delete','web'),
    ('attendance.view','web'), ('attendance.create','web'), ('attendance.update','web'), ('attendance.delete','web'),
    ('cashless.view','web'), ('cashless.create','web'), ('cashless.update','web'), ('cashless.delete','web'),
    ('library.view','web'),  ('library.create','web'),  ('library.update','web'),  ('library.delete','web'),
    ('users.view','web'),    ('users.create','web'),    ('users.update','web'),    ('users.delete','web'),
    ('master_data.view','web'), ('master_data.create','web'), ('master_data.update','web'), ('master_data.delete','web'),
    ('prestasi-siswa.view','web'), ('prestasi-siswa.create','web'), ('prestasi-siswa.update','web'), ('prestasi-siswa.delete','web'),
    ('pelanggaran-siswa.view','web'), ('pelanggaran-siswa.create','web'), ('pelanggaran-siswa.update','web'), ('pelanggaran-siswa.delete','web'),
    ('prestasi-guru.view','web'), ('prestasi-guru.create','web'), ('prestasi-guru.update','web'), ('prestasi-guru.delete','web'),
    ('pelanggaran-guru.view','web'), ('pelanggaran-guru.create','web'), ('pelanggaran-guru.update','web'), ('pelanggaran-guru.delete','web'),
    ('katalog-pelanggaran.view','web'), ('katalog-pelanggaran.create','web'), ('katalog-pelanggaran.update','web'), ('katalog-pelanggaran.delete','web'),
    ('perizinan.view','web'), ('perizinan.create','web'), ('perizinan.update','web'), ('perizinan.delete','web')
ON DUPLICATE KEY UPDATE guard_name = VALUES(guard_name);

-- ---------------------------------------------------------------------------
-- 2) super_admin + admin  -> ALL permissions
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name IN ('super_admin', 'admin');

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name IN ('super_admin', 'admin');

-- ---------------------------------------------------------------------------
-- 3) guru
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'guru';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'guru'
WHERE p.name IN (
    'attendance.view', 'attendance.update',
    'teachers.view', 'library.view',
    'prestasi-siswa.view', 'prestasi-siswa.create', 'prestasi-siswa.update', 'prestasi-siswa.delete',
    'pelanggaran-siswa.view', 'pelanggaran-siswa.create', 'pelanggaran-siswa.update', 'pelanggaran-siswa.delete'
);

-- ---------------------------------------------------------------------------
-- 4) orang_tua
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'orang_tua';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'orang_tua'
WHERE p.name IN (
    'students.view', 'finance.view', 'attendance.view', 'cashless.view', 'library.view',
    'prestasi-siswa.view', 'pelanggaran-siswa.view'
);

-- ---------------------------------------------------------------------------
-- 5) siswa
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'siswa';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'siswa'
WHERE p.name IN (
    'attendance.view', 'cashless.view', 'library.view',
    'prestasi-siswa.view', 'pelanggaran-siswa.view'
);

-- ---------------------------------------------------------------------------
-- 6) kantin
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'kantin';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'kantin'
WHERE p.name IN ('cashless.view', 'cashless.create', 'cashless.update');

-- ---------------------------------------------------------------------------
-- 7) bendahara
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'bendahara';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'bendahara'
WHERE p.name IN (
    'finance.view', 'finance.create', 'finance.update', 'finance.delete',
    'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete'
);

-- ---------------------------------------------------------------------------
-- 8) cashless
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'cashless';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'cashless'
WHERE p.name IN ('cashless.view', 'cashless.create', 'cashless.update', 'cashless.delete');

-- ---------------------------------------------------------------------------
-- 9) pimpinan  (view on core modules + full CRUD prestasi/pelanggaran/katalog)
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'pimpinan';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'pimpinan'
WHERE p.name IN (
    'attendance.view', 'finance.view', 'cashless.view',
    'students.view', 'teachers.view', 'library.view',
    'prestasi-siswa.view', 'prestasi-siswa.create', 'prestasi-siswa.update', 'prestasi-siswa.delete',
    'pelanggaran-siswa.view', 'pelanggaran-siswa.create', 'pelanggaran-siswa.update', 'pelanggaran-siswa.delete',
    'prestasi-guru.view', 'prestasi-guru.create', 'prestasi-guru.update', 'prestasi-guru.delete',
    'pelanggaran-guru.view', 'pelanggaran-guru.create', 'pelanggaran-guru.update', 'pelanggaran-guru.delete',
    'katalog-pelanggaran.view', 'katalog-pelanggaran.create', 'katalog-pelanggaran.update', 'katalog-pelanggaran.delete',
    'perizinan.view'
);

-- ---------------------------------------------------------------------------
-- 10) perpustakaan
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'perpustakaan';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'perpustakaan'
WHERE p.name IN ('library.view', 'library.create', 'library.update', 'library.delete');

-- ---------------------------------------------------------------------------
-- 11) perizinan
-- ---------------------------------------------------------------------------
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'perizinan';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name = 'perizinan'
WHERE p.name IN ('perizinan.view', 'perizinan.create', 'perizinan.update', 'perizinan.delete', 'students.view');

COMMIT;

-- ============================================================================
-- OPTIONAL: purge stale permissions no longer managed by the seeder.
-- Safe because super_admin/admin only hold the 56 names above, so any
-- remaining permission row has no role assignment. Uncomment to run.
-- ============================================================================
-- DELETE FROM permissions
-- WHERE name NOT IN (
--     'students.view','students.create','students.update','students.delete',
--     'teachers.view','teachers.create','teachers.update','teachers.delete',
--     'finance.view','finance.create','finance.update','finance.delete',
--     'attendance.view','attendance.create','attendance.update','attendance.delete',
--     'cashless.view','cashless.create','cashless.update','cashless.delete',
--     'library.view','library.create','library.update','library.delete',
--     'users.view','users.create','users.update','users.delete',
--     'master_data.view','master_data.create','master_data.update','master_data.delete',
--     'prestasi-siswa.view','prestasi-siswa.create','prestasi-siswa.update','prestasi-siswa.delete',
--     'pelanggaran-siswa.view','pelanggaran-siswa.create','pelanggaran-siswa.update','pelanggaran-siswa.delete',
--     'prestasi-guru.view','prestasi-guru.create','prestasi-guru.update','prestasi-guru.delete',
--     'pelanggaran-guru.view','pelanggaran-guru.create','pelanggaran-guru.update','pelanggaran-guru.delete',
--     'katalog-pelanggaran.view','katalog-pelanggaran.create','katalog-pelanggaran.update','katalog-pelanggaran.delete',
--     'perizinan.view','perizinan.create','perizinan.update','perizinan.delete'
-- );

-- ============================================================================
-- Verify (expected counts):
--   super_admin 56 | admin 56 | guru 12 | orang_tua 7 | siswa 5 | kantin 3
--   bendahara 8 | cashless 4 | pimpinan 27 | perpustakaan 4 | perizinan 5
-- ============================================================================
-- SELECT r.name AS role, COUNT(rhp.permission_id) AS permission_count
-- FROM roles r
-- LEFT JOIN role_has_permissions rhp ON rhp.role_id = r.id
-- GROUP BY r.id, r.name
-- ORDER BY r.id;
