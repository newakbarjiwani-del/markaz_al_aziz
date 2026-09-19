-- ============================================================================
-- Potongan Tagihan permissions (live MySQL) — 2026-08-28
--
-- Do NOT Navicat-sync permissions / role_has_permissions wholesale.
-- AFTER: php artisan permission:cache-reset
-- ============================================================================

START TRANSACTION;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT v.name, 'web', NOW(), NOW()
FROM (
    SELECT 'katalog-potongan.view' AS name
    UNION ALL SELECT 'katalog-potongan.create'
    UNION ALL SELECT 'katalog-potongan.update'
    UNION ALL SELECT 'katalog-potongan.delete'
    UNION ALL SELECT 'potongan-tagihan.view'
    UNION ALL SELECT 'potongan-tagihan.create'
    UNION ALL SELECT 'potongan-tagihan.update'
    UNION ALL SELECT 'potongan-tagihan.delete'
) AS v
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.name = v.name AND p.guard_name = 'web'
);

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.name IN ('super_admin', 'admin', 'bendahara')
WHERE p.name IN (
    'katalog-potongan.view', 'katalog-potongan.create', 'katalog-potongan.update', 'katalog-potongan.delete',
    'potongan-tagihan.view', 'potongan-tagihan.create', 'potongan-tagihan.update', 'potongan-tagihan.delete'
)
AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions rhp
    WHERE rhp.permission_id = p.id AND rhp.role_id = r.id
);

COMMIT;
