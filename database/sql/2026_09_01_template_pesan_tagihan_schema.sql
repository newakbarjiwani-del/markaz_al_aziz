-- Template pesan tagihan WA (MySQL production)
-- Run in Navicat after backup.

CREATE TABLE IF NOT EXISTS `template_pesan_tagihan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(120) NOT NULL,
    `kategori` VARCHAR(30) NOT NULL,
    `isi_pesan` TEXT NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tpl_pesan_tagihan_nama_deleted_unique` (`nama`, `deleted_at`),
    KEY `tpl_pesan_tagihan_kategori_active_idx` (`kategori`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
