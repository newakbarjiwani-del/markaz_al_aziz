-- Production SQL companion for 2026_07_20_100000_rename_and_extend_buku_table.php
-- Run on MySQL after backup when not using php artisan migrate.

ALTER TABLE `buku` DROP FOREIGN KEY `buku_sekolah_id_foreign`;
ALTER TABLE `buku` MODIFY `sekolah_id` BIGINT UNSIGNED NULL;
ALTER TABLE `buku` ADD CONSTRAINT `buku_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE SET NULL;

ALTER TABLE `buku`
    CHANGE `title` `judul` VARCHAR(255) NOT NULL,
    CHANGE `author` `pengarang` VARCHAR(255) NULL,
    CHANGE `stock` `jumlah` INT UNSIGNED NOT NULL DEFAULT 0,
    CHANGE `available` `tersedia` INT UNSIGNED NOT NULL DEFAULT 0,
    CHANGE `category` `kategori` VARCHAR(255) NULL,
    CHANGE `rating` `nilai_rata` DECIMAL(3,2) NULL;

ALTER TABLE `buku`
    ADD `isbn_key` VARCHAR(255) NULL AFTER `isbn`,
    ADD `kode_buku` VARCHAR(255) NULL AFTER `isbn_key`,
    ADD `penerbit` VARCHAR(255) NULL AFTER `pengarang`,
    ADD `tahun_terbit` SMALLINT UNSIGNED NULL AFTER `kategori`,
    ADD `cetak_ke` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `tahun_terbit`,
    ADD `keadaan_baik` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `jumlah`,
    ADD `keadaan_rusak_ringan` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `keadaan_baik`,
    ADD `keadaan_rusak_berat` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `keadaan_rusak_ringan`,
    ADD `tanggal_penerimaan` DATE NULL AFTER `nilai_rata`,
    ADD `sumber_dana` VARCHAR(255) NULL AFTER `tanggal_penerimaan`,
    ADD `keterangan` TEXT NULL AFTER `sumber_dana`;

UPDATE `buku` SET `keadaan_baik` = `jumlah`, `cetak_ke` = 1 WHERE `deleted_at` IS NULL;
UPDATE `buku` SET `isbn_key` = `isbn` WHERE `deleted_at` IS NULL AND `isbn` IS NOT NULL AND `isbn` != '';

ALTER TABLE `buku` ADD UNIQUE `buku_isbn_key_unique` (`isbn_key`);
