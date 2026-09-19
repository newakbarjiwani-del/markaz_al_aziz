-- ============================================================================
-- LIVE sync based on exported dumps (roles.json / permissions.json /
-- role_has_permissions.json) vs RolePermissionSeeder.php — 2026-08-21
--
-- Live status before this script:
--   roles                       OK (1–12, including prestasi_pelanggaran)
--   permissions                 MISSING only katalog-prestasi.* (4 rows)
--   role_has_permissions        BROKEN:
--     - orphan permission_id 61–64 (permissions rows do not exist)
--       → leftover from failed Navicat INSERT sync
--     - prestasi_pelanggaran (role_id 12) has 0 permissions
--     - pimpinan missing katalog-prestasi.*
--     - perizinan missing pelanggaran-siswa.create
--
-- Do NOT use Navicat table sync for permissions — unique(name,guard_name)
-- causes Error 1062 (e.g. Duplicate entry 'perizinan.view-web').
--
-- AFTER: php artisan permission:cache-reset
-- ============================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1) Add ONLY the 4 missing permissions (safe if re-run)
-- ---------------------------------------------------------------------------
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT v.name, 'web', NOW(), NOW()
FROM (
    SELECT 'katalog-prestasi.view' AS name
    UNION ALL SELECT 'katalog-prestasi.create'
    UNION ALL SELECT 'katalog-prestasi.update'
    UNION ALL SELECT 'katalog-prestasi.delete'
) AS v
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p
    WHERE p.name = v.name AND p.guard_name = 'web'
);

-- ---------------------------------------------------------------------------
-- 2) Rebuild role_has_permissions (name-based — ignores broken local ids)
--    Deletes orphan 61–64 pivots as a side effect.
-- ---------------------------------------------------------------------------

-- 2a) super_admin + admin → ALL web permissions
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name IN ('super_admin', 'admin');

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name IN ('super_admin', 'admin')
WHERE p.guard_name = 'web';

-- 2b) guru
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'guru';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'guru'
WHERE p.name IN (
    'attendance.view', 'attendance.update', 'teachers.view', 'library.view',
    'prestasi-siswa.view', 'prestasi-siswa.create', 'prestasi-siswa.update', 'prestasi-siswa.delete',
    'pelanggaran-siswa.view', 'pelanggaran-siswa.create', 'pelanggaran-siswa.update', 'pelanggaran-siswa.delete'
);

-- 2c) orang_tua
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'orang_tua';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'orang_tua'
WHERE p.name IN (
    'students.view', 'finance.view', 'attendance.view', 'cashless.view', 'library.view',
    'prestasi-siswa.view', 'pelanggaran-siswa.view'
);

-- 2d) siswa
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'siswa';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'siswa'
WHERE p.name IN (
    'attendance.view', 'cashless.view', 'library.view',
    'prestasi-siswa.view', 'pelanggaran-siswa.view'
);

-- 2e) kantin
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'kantin';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'kantin'
WHERE p.name IN ('cashless.view', 'cashless.create', 'cashless.update');

-- 2f) bendahara
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'bendahara';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'bendahara'
WHERE p.name IN (
    'finance.view', 'finance.create', 'finance.update', 'finance.delete',
    'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete'
);

-- 2g) cashless
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'cashless';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'cashless'
WHERE p.name IN ('cashless.view', 'cashless.create', 'cashless.update', 'cashless.delete');

-- 2h) pimpinan
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'pimpinan';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'pimpinan'
WHERE p.name IN (
    'attendance.view', 'finance.view', 'cashless.view',
    'students.view', 'teachers.view', 'library.view', 'perizinan.view',
    'prestasi-siswa.view', 'prestasi-siswa.create', 'prestasi-siswa.update', 'prestasi-siswa.delete',
    'pelanggaran-siswa.view', 'pelanggaran-siswa.create', 'pelanggaran-siswa.update', 'pelanggaran-siswa.delete',
    'prestasi-guru.view', 'prestasi-guru.create', 'prestasi-guru.update', 'prestasi-guru.delete',
    'pelanggaran-guru.view', 'pelanggaran-guru.create', 'pelanggaran-guru.update', 'pelanggaran-guru.delete',
    'katalog-pelanggaran.view', 'katalog-pelanggaran.create', 'katalog-pelanggaran.update', 'katalog-pelanggaran.delete',
    'katalog-prestasi.view', 'katalog-prestasi.create', 'katalog-prestasi.update', 'katalog-prestasi.delete',
    'hukuman-siswa.view', 'hukuman-siswa.create', 'hukuman-siswa.update', 'hukuman-siswa.delete'
);

-- 2i) prestasi_pelanggaran  ← currently 0 on live; this is the main fix
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'prestasi_pelanggaran';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'prestasi_pelanggaran'
WHERE p.name IN (
    'prestasi-siswa.view', 'prestasi-siswa.create', 'prestasi-siswa.update', 'prestasi-siswa.delete',
    'pelanggaran-siswa.view', 'pelanggaran-siswa.create', 'pelanggaran-siswa.update', 'pelanggaran-siswa.delete',
    'prestasi-guru.view', 'prestasi-guru.create', 'prestasi-guru.update', 'prestasi-guru.delete',
    'pelanggaran-guru.view', 'pelanggaran-guru.create', 'pelanggaran-guru.update', 'pelanggaran-guru.delete',
    'katalog-pelanggaran.view', 'katalog-pelanggaran.create', 'katalog-pelanggaran.update', 'katalog-pelanggaran.delete',
    'katalog-prestasi.view', 'katalog-prestasi.create', 'katalog-prestasi.update', 'katalog-prestasi.delete',
    'hukuman-siswa.view', 'hukuman-siswa.create', 'hukuman-siswa.update', 'hukuman-siswa.delete'
);

-- 2j) perpustakaan
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'perpustakaan';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'perpustakaan'
WHERE p.name IN ('library.view', 'library.create', 'library.update', 'library.delete');

-- 2k) perizinan
DELETE rhp FROM role_has_permissions rhp
    JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'perizinan';

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name = 'perizinan'
WHERE p.name IN (
    'perizinan.view', 'perizinan.create', 'perizinan.update', 'perizinan.delete',
    'students.view',
    'pelanggaran-siswa.create'
);

COMMIT;

-- ============================================================================
-- Verify:
-- SELECT name FROM permissions WHERE name LIKE 'katalog-prestasi.%';
--   → 4 rows
--
-- SELECT r.name, COUNT(*) cnt FROM roles r
-- LEFT JOIN role_has_permissions rhp ON rhp.role_id = r.id
-- GROUP BY r.id, r.name ORDER BY r.id;
--
-- Expected counts:
--   super_admin/admin     64
--   guru                  12
--   orang_tua              7
--   siswa                  5
--   kantin                 3
--   pimpinan              35
--   perpustakaan           4
--   bendahara              8
--   cashless               4
--   perizinan              6
--   prestasi_pelanggaran  28
--
-- Orphans should be gone:
-- SELECT rhp.* FROM role_has_permissions rhp
-- LEFT JOIN permissions p ON p.id = rhp.permission_id
-- WHERE p.id IS NULL;
-- ============================================================================
