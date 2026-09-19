-- Pelanggaran siswa: is_punished + point_asli (MySQL production)
-- Run in Navicat after backup.

ALTER TABLE `pelanggaran_siswa`
    ADD COLUMN `is_punished` TINYINT(1) NOT NULL DEFAULT 0 AFTER `point`,
    ADD COLUMN `point_asli` INT UNSIGNED NULL AFTER `is_punished`;

CREATE INDEX `pelanggaran_siswa_punished_idx` ON `pelanggaran_siswa` (`is_punished`);
