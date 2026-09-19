-- Master Data: kamar + status_santri + siswa FKs (MySQL production)
-- Run in Navicat after backup.

CREATE TABLE IF NOT EXISTS `kamar` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode` VARCHAR(50) NULL,
    `nama` VARCHAR(255) NOT NULL,
    `blok` VARCHAR(100) NULL,
    `kapasitas` SMALLINT UNSIGNED NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `kamar_nama_deleted_unique` (`nama`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `status_santri` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(255) NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `status_santri_nama_deleted_unique` (`nama`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `siswa`
    ADD COLUMN IF NOT EXISTS `kamar_id` BIGINT UNSIGNED NULL AFTER `kelas_id`,
    ADD COLUMN IF NOT EXISTS `status_santri_id` BIGINT UNSIGNED NULL AFTER `kamar_id`;

-- MySQL 8.0.12+ supports IF NOT EXISTS on ADD COLUMN; on older MySQL run manually if column exists:
-- ALTER TABLE `siswa` ADD CONSTRAINT `siswa_kamar_id_foreign` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE SET NULL;
-- ALTER TABLE `siswa` ADD CONSTRAINT `siswa_status_santri_id_foreign` FOREIGN KEY (`status_santri_id`) REFERENCES `status_santri` (`id`) ON DELETE SET NULL;
