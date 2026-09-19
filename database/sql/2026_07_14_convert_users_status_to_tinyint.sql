-- Convert users.status from varchar labels to unsigned TINYINT.
-- Run on MySQL/MariaDB databases that still store aktif/nonaktif (or English aliases).
--
-- Mapping:
--   nonaktif / disabled / blocked / inactive / '0' → 0
--   aktif / active / '1'                          → 1  (default)
--
-- After this, application expects tinyint and blocks login unless status = 1.
-- Prefer running this before or with deploy of the users.status tinyint app code;
-- do not rely on migrate:fresh on production.

-- 1) Preview current distribution
-- SELECT status, COUNT(*) AS total FROM users WHERE deleted_at IS NULL GROUP BY status;

-- 2) Add temporary column
ALTER TABLE users
    ADD COLUMN status_code TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER password;

-- 3) Map legacy labels (+ numeric strings if any)
UPDATE users SET status_code = 0
WHERE LOWER(TRIM(status)) IN ('nonaktif', 'disabled', 'blocked', 'inactive', '0');

UPDATE users SET status_code = 1
WHERE LOWER(TRIM(status)) IN ('aktif', 'active', '1');

-- Unknown leftovers stay at default 1 from ADD COLUMN.

-- 4) Swap columns
ALTER TABLE users DROP COLUMN status;
ALTER TABLE users CHANGE status_code status TINYINT UNSIGNED NOT NULL DEFAULT 1;

-- 5) Verify
-- SELECT status, COUNT(*) AS total FROM users WHERE deleted_at IS NULL GROUP BY status;
