-- Make orang_tua.sekolah_id nullable (parent can span multiple schools).
-- Safe on MySQL/MariaDB.

ALTER TABLE orang_tua DROP FOREIGN KEY orang_tua_sekolah_id_foreign;

ALTER TABLE orang_tua
    MODIFY COLUMN sekolah_id BIGINT UNSIGNED NULL;

ALTER TABLE orang_tua
    ADD CONSTRAINT orang_tua_sekolah_id_foreign
    FOREIGN KEY (sekolah_id) REFERENCES sekolah (id) ON DELETE SET NULL;
