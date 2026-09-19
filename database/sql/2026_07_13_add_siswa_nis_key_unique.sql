-- Fix active NIS uniqueness (MySQL treats NULL deleted_at as distinct in UNIQUE(nis, deleted_at)).
-- 1) Soft-delete duplicate active NIS (keep lowest id)
-- 2) Drop old composite unique
-- 3) Add nis_key + unique index

-- Preview duplicates
-- SELECT nis, COUNT(*) AS total, GROUP_CONCAT(id) AS ids
-- FROM siswa WHERE deleted_at IS NULL GROUP BY nis HAVING total > 1;

UPDATE siswa s
INNER JOIN (
    SELECT nis, MIN(id) AS keep_id
    FROM siswa
    WHERE deleted_at IS NULL
    GROUP BY nis
    HAVING COUNT(*) > 1
) d ON d.nis = s.nis
SET s.deleted_at = NOW(), s.updated_at = NOW()
WHERE s.deleted_at IS NULL
  AND s.id <> d.keep_id;

ALTER TABLE siswa DROP INDEX siswa_nis_deleted_at_unique;

ALTER TABLE siswa
    ADD COLUMN nis_key VARCHAR(255) NULL AFTER nis;

UPDATE siswa SET nis_key = nis WHERE deleted_at IS NULL;
UPDATE siswa SET nis_key = NULL WHERE deleted_at IS NOT NULL;

ALTER TABLE siswa ADD UNIQUE INDEX siswa_nis_key_unique (nis_key);
