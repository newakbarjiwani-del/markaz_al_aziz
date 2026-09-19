-- Convert siswa.status from varchar labels to unsigned TINYINT.
-- Run on MySQL/MariaDB databases that still store aktif/nonaktif/pending.
--
-- Mapping:
--   nonaktif → 0
--   aktif    → 1  (default)
--   pending  → 2  (reserved for future admission)
--
-- After this, application expects tinyint and blocks transactions unless status = 1.

-- 1) Preview current distribution
-- SELECT status, COUNT(*) AS total FROM siswa WHERE deleted_at IS NULL GROUP BY status;

-- 2) Add temporary column
ALTER TABLE siswa
    ADD COLUMN status_code TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER address;

-- 3) Map legacy labels (+ numeric strings if any)
UPDATE siswa SET status_code = 0 WHERE status IN ('nonaktif', '0');
UPDATE siswa SET status_code = 1 WHERE status IN ('aktif', '1');
UPDATE siswa SET status_code = 2 WHERE status IN ('pending', '2');

-- Unknown leftovers stay at default 1 from ADD COLUMN.

-- 4) Swap columns
ALTER TABLE siswa DROP COLUMN status;
ALTER TABLE siswa CHANGE status_code status TINYINT UNSIGNED NOT NULL DEFAULT 1;

-- 5) Verify
-- SELECT status, COUNT(*) AS total FROM siswa WHERE deleted_at IS NULL GROUP BY status;
