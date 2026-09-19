-- Production SQL companion for 2026_07_27_000000_rename_paid_at_add_paid_dt_actual.php
-- Run on MySQL after backup when not using php artisan migrate.
--
-- Renames paid_at → paid_dt on tagihan & pembayaran,
-- adds paid_dt_actual (server processing timestamp) to both tables.

-- 1. tagihan: rename paid_at → paid_dt
ALTER TABLE `tagihan` CHANGE `paid_at` `paid_dt` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `tagihan` ADD `paid_dt_actual` TIMESTAMP NULL DEFAULT NULL AFTER `paid_dt`;

-- 2. pembayaran: rename paid_at → paid_dt
ALTER TABLE `pembayaran` CHANGE `paid_at` `paid_dt` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `pembayaran` ADD `paid_dt_actual` TIMESTAMP NULL DEFAULT NULL AFTER `paid_dt`;

-- 3. Backfill paid_dt_actual from existing paid_dt (production snapshot)
UPDATE `tagihan` SET `paid_dt_actual` = `paid_dt` WHERE `paid_dt` IS NOT NULL;
UPDATE `pembayaran` SET `paid_dt_actual` = `paid_dt` WHERE `paid_dt` IS NOT NULL;
