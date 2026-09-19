-- ============================================================================
-- Production schema sync (live MySQL) — 2026-09-03
--
-- Idempotent bundle for Navicat / mysql CLI. Safe to re-run.
-- Uses DATABASE() — no hardcoded schema name.
--
-- Covers:
--   • absensi_sesi_pengecualian — soft-delete aware unique (slot + date)
--   • potongan tagihan (jenis_potongan, potongan_siswa, pivot, pemakaian, tagihan cols)
--   • kamar + status_santri + siswa FK columns
--   • pelanggaran_siswa is_punished / point_asli
--   • template_pesan_tagihan (Kirim Tagihan WA)
--
-- BEFORE (backup):
--   mysqldump -u USER -p DATABASE > backup_$(date +%F).sql
--
-- RUN:
--   mysql -u USER -p DATABASE < database/sql/2026_09_03_sync_live_schema.sql
--
-- AFTER:
--   mysql -u USER -p DATABASE < database/sql/2026_08_28_sync_potongan_permissions_live.sql
--   php artisan permission:cache-reset
--
-- Optional seed (demo / first deploy of master data):
--   php artisan db:seed --class=Database\\Seeders\\KamarStatusSantriSeeder
--   php artisan db:seed --class=Database\\Seeders\\TemplatePesanTagihanSeeder
--   php artisan db:seed --class=Database\\Seeders\\PotonganCatalogSeeder
--
-- If absensi unique index fails: deduplicate absensi_sesi_pengecualian rows first
-- (same jadwal_absen_slot_id + date with deleted_at IS NULL).
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. absensi_sesi_pengecualian — composite unique (slot + date + deleted_at)
--    Do NOT drop the FK index on jadwal_absen_slot_id; only add missing unique.
-- ---------------------------------------------------------------------------
SET @db := DATABASE();

SET @has_absensi_unique := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @db
      AND table_name = 'absensi_sesi_pengecualian'
      AND index_name = 'absensi_sesi_pengecualian_slot_day_unique'
);

SET @absensi_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = @db
      AND table_name = 'absensi_sesi_pengecualian'
);

SET @sql_absensi := IF(
    @absensi_table_exists > 0 AND @has_absensi_unique = 0,
    'ALTER TABLE `absensi_sesi_pengecualian` ADD UNIQUE INDEX `absensi_sesi_pengecualian_slot_day_unique` (`jadwal_absen_slot_id`, `date`, `deleted_at`)',
    'SELECT ''absensi_sesi_pengecualian unique index OK or table missing'' AS info'
);

PREPARE stmt_absensi FROM @sql_absensi;
EXECUTE stmt_absensi;
DEALLOCATE PREPARE stmt_absensi;

-- ---------------------------------------------------------------------------
-- 2. Potongan tagihan — tables + tagihan columns
--    (from 2026_08_28_potongan_tagihan_schema.sql)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jenis_potongan` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `sekolah_id` bigint UNSIGNED NULL DEFAULT NULL,
    `kode` varchar(50) NULL DEFAULT NULL,
    `nama` varchar(255) NOT NULL,
    `tipe_default` varchar(10) NOT NULL DEFAULT 'percent' COMMENT 'percent|fixed',
    `nilai_default` int UNSIGNED NOT NULL DEFAULT 0,
    `keterangan` text NULL,
    `sort_order` smallint UNSIGNED NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `jenis_potongan_nama_deleted_unique` (`nama`, `deleted_at`),
    KEY `jenis_potongan_sekolah_active_idx` (`sekolah_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `potongan_siswa` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `sekolah_id` bigint UNSIGNED NOT NULL,
    `siswa_id` bigint UNSIGNED NOT NULL,
    `jenis_potongan_id` bigint UNSIGNED NOT NULL,
    `tipe` varchar(10) NOT NULL COMMENT 'percent|fixed',
    `nilai` int UNSIGNED NOT NULL,
    `berlaku_mulai` date NOT NULL,
    `berlaku_sampai` date NOT NULL,
    `max_pemakaian` smallint UNSIGNED NOT NULL DEFAULT 1,
    `status` varchar(20) NOT NULL DEFAULT 'aktif' COMMENT 'aktif|nonaktif|habis',
    `keterangan` text NULL,
    `processed_by_user_id` bigint UNSIGNED NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `potongan_siswa_jenis_potongan_id_foreign` (`jenis_potongan_id`),
    KEY `potongan_siswa_processed_by_user_id_foreign` (`processed_by_user_id`),
    KEY `potongan_siswa_siswa_status_idx` (`siswa_id`, `status`),
    KEY `potongan_siswa_sekolah_status_idx` (`sekolah_id`, `status`),
    KEY `potongan_siswa_date_idx` (`berlaku_mulai`, `berlaku_sampai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `potongan_siswa_jenis_tagihan` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `potongan_siswa_id` bigint UNSIGNED NOT NULL,
    `jenis_tagihan_id` bigint UNSIGNED NOT NULL,
    `tipe` varchar(10) NULL DEFAULT NULL COMMENT 'percent|fixed',
    `nilai` int UNSIGNED NULL DEFAULT NULL,
    `max_pemakaian` smallint UNSIGNED NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `potongan_siswa_jenis_unique` (`potongan_siswa_id`, `jenis_tagihan_id`),
    KEY `potongan_siswa_jenis_tagihan_jenis_tagihan_id_foreign` (`jenis_tagihan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pivot per-jenis override columns (if table existed before 2026-08-28 patch)
SET @has_pivot_tipe := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'potongan_siswa_jenis_tagihan' AND column_name = 'tipe'
);
SET @sql := IF(
    @has_pivot_tipe = 0,
    'ALTER TABLE `potongan_siswa_jenis_tagihan` ADD COLUMN `tipe` varchar(10) NULL DEFAULT NULL COMMENT ''percent|fixed'' AFTER `jenis_tagihan_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_pivot_nilai := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'potongan_siswa_jenis_tagihan' AND column_name = 'nilai'
);
SET @sql := IF(
    @has_pivot_nilai = 0,
    'ALTER TABLE `potongan_siswa_jenis_tagihan` ADD COLUMN `nilai` int UNSIGNED NULL DEFAULT NULL AFTER `tipe`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_pivot_max := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'potongan_siswa_jenis_tagihan' AND column_name = 'max_pemakaian'
);
SET @sql := IF(
    @has_pivot_max = 0,
    'ALTER TABLE `potongan_siswa_jenis_tagihan` ADD COLUMN `max_pemakaian` smallint UNSIGNED NULL DEFAULT NULL AFTER `nilai`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `potongan_pemakaian` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `potongan_siswa_id` bigint UNSIGNED NOT NULL,
    `tagihan_id` bigint UNSIGNED NOT NULL,
    `amount_bruto` decimal(15, 2) NOT NULL,
    `potongan_amount` decimal(15, 2) NOT NULL,
    `amount_net` decimal(15, 2) NOT NULL,
    `urutan` tinyint UNSIGNED NOT NULL DEFAULT 1,
    `applied_at` datetime NOT NULL,
    `user_id` bigint UNSIGNED NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `potongan_pemakaian_ps_tagihan_unique` (`potongan_siswa_id`, `tagihan_id`),
    KEY `potongan_pemakaian_user_id_foreign` (`user_id`),
    KEY `potongan_pemakaian_tagihan_idx` (`tagihan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_amount_bruto := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'tagihan' AND column_name = 'amount_bruto'
);
SET @sql := IF(
    @has_amount_bruto = 0,
    'ALTER TABLE `tagihan` ADD COLUMN `amount_bruto` decimal(15, 2) NULL DEFAULT NULL AFTER `amount`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_potongan_amount := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'tagihan' AND column_name = 'potongan_amount'
);
SET @sql := IF(
    @has_potongan_amount = 0,
    'ALTER TABLE `tagihan` ADD COLUMN `potongan_amount` decimal(15, 2) NOT NULL DEFAULT 0.00 AFTER `amount_bruto`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Potongan FK constraints (idempotent)
SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'jenis_potongan'
      AND constraint_name = 'jenis_potongan_sekolah_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `jenis_potongan` ADD CONSTRAINT `jenis_potongan_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_siswa'
      AND constraint_name = 'potongan_siswa_sekolah_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_siswa` ADD CONSTRAINT `potongan_siswa_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_siswa'
      AND constraint_name = 'potongan_siswa_siswa_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_siswa` ADD CONSTRAINT `potongan_siswa_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_siswa'
      AND constraint_name = 'potongan_siswa_jenis_potongan_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_siswa` ADD CONSTRAINT `potongan_siswa_jenis_potongan_id_foreign` FOREIGN KEY (`jenis_potongan_id`) REFERENCES `jenis_potongan` (`id`) ON DELETE RESTRICT',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_siswa'
      AND constraint_name = 'potongan_siswa_processed_by_user_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_siswa` ADD CONSTRAINT `potongan_siswa_processed_by_user_id_foreign` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_siswa_jenis_tagihan'
      AND constraint_name = 'potongan_siswa_jenis_tagihan_potongan_siswa_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_siswa_jenis_tagihan` ADD CONSTRAINT `potongan_siswa_jenis_tagihan_potongan_siswa_id_foreign` FOREIGN KEY (`potongan_siswa_id`) REFERENCES `potongan_siswa` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_siswa_jenis_tagihan'
      AND constraint_name = 'potongan_siswa_jenis_tagihan_jenis_tagihan_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_siswa_jenis_tagihan` ADD CONSTRAINT `potongan_siswa_jenis_tagihan_jenis_tagihan_id_foreign` FOREIGN KEY (`jenis_tagihan_id`) REFERENCES `jenis_tagihan` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_pemakaian'
      AND constraint_name = 'potongan_pemakaian_potongan_siswa_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_pemakaian` ADD CONSTRAINT `potongan_pemakaian_potongan_siswa_id_foreign` FOREIGN KEY (`potongan_siswa_id`) REFERENCES `potongan_siswa` (`id`) ON DELETE RESTRICT',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_pemakaian'
      AND constraint_name = 'potongan_pemakaian_tagihan_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_pemakaian` ADD CONSTRAINT `potongan_pemakaian_tagihan_id_foreign` FOREIGN KEY (`tagihan_id`) REFERENCES `tagihan` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'potongan_pemakaian'
      AND constraint_name = 'potongan_pemakaian_user_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `potongan_pemakaian` ADD CONSTRAINT `potongan_pemakaian_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 3. Kamar + status santri + siswa FK columns
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kamar` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode` varchar(50) NULL DEFAULT NULL,
    `nama` varchar(255) NOT NULL,
    `blok` varchar(100) NULL DEFAULT NULL,
    `kapasitas` smallint UNSIGNED NULL DEFAULT NULL,
    `sort_order` smallint UNSIGNED NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `kamar_nama_deleted_unique` (`nama`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `status_santri` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` varchar(255) NOT NULL,
    `sort_order` smallint UNSIGNED NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `status_santri_nama_deleted_unique` (`nama`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_kamar_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'siswa' AND column_name = 'kamar_id'
);
SET @sql := IF(
    @has_kamar_id = 0,
    'ALTER TABLE `siswa` ADD COLUMN `kamar_id` bigint UNSIGNED NULL DEFAULT NULL AFTER `kelas_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_status_santri_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'siswa' AND column_name = 'status_santri_id'
);
SET @sql := IF(
    @has_status_santri_id = 0,
    'ALTER TABLE `siswa` ADD COLUMN `status_santri_id` bigint UNSIGNED NULL DEFAULT NULL AFTER `kamar_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @db AND table_name = 'siswa' AND index_name = 'siswa_kamar_id_foreign'
);
SET @sql := IF(
    @idx = 0,
    'ALTER TABLE `siswa` ADD INDEX `siswa_kamar_id_foreign` (`kamar_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @db AND table_name = 'siswa' AND index_name = 'siswa_status_santri_id_foreign'
);
SET @sql := IF(
    @idx = 0,
    'ALTER TABLE `siswa` ADD INDEX `siswa_status_santri_id_foreign` (`status_santri_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'siswa'
      AND constraint_name = 'siswa_kamar_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `siswa` ADD CONSTRAINT `siswa_kamar_id_foreign` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = @db AND table_name = 'siswa'
      AND constraint_name = 'siswa_status_santri_id_foreign'
);
SET @sql := IF(
    @fk = 0,
    'ALTER TABLE `siswa` ADD CONSTRAINT `siswa_status_santri_id_foreign` FOREIGN KEY (`status_santri_id`) REFERENCES `status_santri` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 4. pelanggaran_siswa — hukuman point reset columns
-- ---------------------------------------------------------------------------
SET @has_is_punished := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'pelanggaran_siswa' AND column_name = 'is_punished'
);
SET @sql := IF(
    @has_is_punished = 0,
    'ALTER TABLE `pelanggaran_siswa` ADD COLUMN `is_punished` tinyint(1) NOT NULL DEFAULT 0 AFTER `point`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_point_asli := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db AND table_name = 'pelanggaran_siswa' AND column_name = 'point_asli'
);
SET @sql := IF(
    @has_point_asli = 0,
    'ALTER TABLE `pelanggaran_siswa` ADD COLUMN `point_asli` int UNSIGNED NULL DEFAULT NULL AFTER `is_punished`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @db AND table_name = 'pelanggaran_siswa'
      AND index_name = 'pelanggaran_siswa_punished_idx'
);
SET @sql := IF(
    @idx = 0,
    'ALTER TABLE `pelanggaran_siswa` ADD INDEX `pelanggaran_siswa_punished_idx` (`is_punished`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 5. template_pesan_tagihan — Kirim Tagihan WA
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `template_pesan_tagihan` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` varchar(255) NOT NULL,
    `kategori` varchar(30) NOT NULL COMMENT 'belum_jatuh_tempo|lewat_jatuh_tempo',
    `isi_pesan` text NOT NULL,
    `sort_order` smallint UNSIGNED NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tpl_pesan_tagihan_nama_deleted_unique` (`nama`, `deleted_at`),
    KEY `tpl_pesan_tagihan_kategori_active_idx` (`kategori`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Done. Run permissions sync + cache reset (see header).
-- ============================================================================
