-- Reset only portal login users with roles:
--   siswa, orang_tua, guru
-- Keep other users (admin, super_admin, kantin, dll.)
--
-- Target: MySQL / MariaDB (Navicat-compatible)

SET @old_fk_checks := @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

DROP TEMPORARY TABLE IF EXISTS tmp_target_user_ids;
CREATE TEMPORARY TABLE tmp_target_user_ids (
    id BIGINT UNSIGNED PRIMARY KEY
);

INSERT INTO tmp_target_user_ids (id)
SELECT DISTINCT u.id
FROM users u
JOIN model_has_roles mhr
    ON mhr.model_id = u.id
   AND mhr.model_type = 'App\\Models\\User'
JOIN roles r
    ON r.id = mhr.role_id
WHERE r.name IN ('siswa', 'orang_tua', 'guru');

-- Cleanup auth mappings/tokens tied to those users
DELETE mhr
FROM model_has_roles mhr
JOIN tmp_target_user_ids t ON t.id = mhr.model_id
WHERE mhr.model_type = 'App\\Models\\User';

DELETE mhp
FROM model_has_permissions mhp
JOIN tmp_target_user_ids t ON t.id = mhp.model_id
WHERE mhp.model_type = 'App\\Models\\User';

DELETE pat
FROM personal_access_tokens pat
JOIN tmp_target_user_ids t
  ON t.id = pat.tokenable_id
WHERE pat.tokenable_type = 'App\\Models\\User';

DELETE s
FROM sessions s
JOIN tmp_target_user_ids t ON t.id = s.user_id;

DELETE pt
FROM portal_access_tokens pt
JOIN tmp_target_user_ids t ON t.id = pt.user_id;

-- Finally delete users (hard delete)
DELETE u
FROM users u
JOIN tmp_target_user_ids t ON t.id = u.id;

SET FOREIGN_KEY_CHECKS = @old_fk_checks;

-- Summary
SELECT COUNT(*) AS remaining_siswa_users
FROM users u
JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
JOIN roles r ON r.id = mhr.role_id
WHERE r.name = 'siswa'
UNION ALL
SELECT COUNT(*) AS remaining_ortu_users
FROM users u
JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
JOIN roles r ON r.id = mhr.role_id
WHERE r.name = 'orang_tua'
UNION ALL
SELECT COUNT(*) AS remaining_guru_users
FROM users u
JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
JOIN roles r ON r.id = mhr.role_id
WHERE r.name = 'guru';
