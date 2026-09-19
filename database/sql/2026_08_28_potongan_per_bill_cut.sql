-- Per-bill custom cut + quota on potongan_siswa_jenis_tagihan pivot (2026-08-28)
-- Run after 2026_08_28_potongan_tagihan_schema.sql if table already exists without tipe/nilai/max_pemakaian.

START TRANSACTION;

SET @db := DATABASE();

SELECT COUNT(*) INTO @has_tipe
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'potongan_siswa_jenis_tagihan'
  AND COLUMN_NAME = 'tipe';

SET @sql := IF(
    @has_tipe = 0,
    'ALTER TABLE potongan_siswa_jenis_tagihan ADD COLUMN tipe VARCHAR(10) NULL COMMENT ''percent|fixed'' AFTER jenis_tagihan_id',
    'SELECT ''skip tipe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_nilai
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'potongan_siswa_jenis_tagihan'
  AND COLUMN_NAME = 'nilai';

SET @sql := IF(
    @has_nilai = 0,
    'ALTER TABLE potongan_siswa_jenis_tagihan ADD COLUMN nilai INT UNSIGNED NULL AFTER tipe',
    'SELECT ''skip nilai'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_max
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'potongan_siswa_jenis_tagihan'
  AND COLUMN_NAME = 'max_pemakaian';

SET @sql := IF(
    @has_max = 0,
    'ALTER TABLE potongan_siswa_jenis_tagihan ADD COLUMN max_pemakaian SMALLINT UNSIGNED NULL AFTER nilai',
    'SELECT ''skip max_pemakaian'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
