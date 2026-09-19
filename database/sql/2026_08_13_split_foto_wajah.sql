CREATE TABLE siswa_wajah (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id BIGINT UNSIGNED NOT NULL,
    foto_wajah LONGTEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY siswa_wajah_siswa_id_unique (siswa_id),
    CONSTRAINT siswa_wajah_siswa_id_foreign FOREIGN KEY (siswa_id) REFERENCES siswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pengunjung_perpustakaan_wajah (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pengunjung_perpustakaan_id BIGINT UNSIGNED NOT NULL,
    foto_wajah LONGTEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY pengunjung_perpustakaan_wajah_pengunjung_id_unique (pengunjung_perpustakaan_id),
    CONSTRAINT pengunjung_perpustakaan_wajah_pengunjung_id_foreign
        FOREIGN KEY (pengunjung_perpustakaan_id) REFERENCES pengunjung_perpustakaan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE siswa
    ADD COLUMN has_foto_wajah TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD INDEX siswa_has_foto_wajah_index (has_foto_wajah);

ALTER TABLE pengunjung_perpustakaan
    ADD COLUMN has_foto_wajah TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD INDEX pengunjung_perpustakaan_has_foto_wajah_index (has_foto_wajah);

INSERT INTO siswa_wajah (siswa_id, foto_wajah, created_at, updated_at)
SELECT id, foto_wajah, NOW(), NOW()
FROM siswa
WHERE foto_wajah IS NOT NULL AND CHAR_LENGTH(foto_wajah) > 30;

UPDATE siswa
SET has_foto_wajah = 1
WHERE foto_wajah IS NOT NULL AND CHAR_LENGTH(foto_wajah) > 30;

INSERT INTO pengunjung_perpustakaan_wajah
    (pengunjung_perpustakaan_id, foto_wajah, created_at, updated_at)
SELECT id, foto_wajah, NOW(), NOW()
FROM pengunjung_perpustakaan
WHERE foto_wajah IS NOT NULL AND CHAR_LENGTH(foto_wajah) > 30;

UPDATE pengunjung_perpustakaan
SET has_foto_wajah = 1
WHERE foto_wajah IS NOT NULL AND CHAR_LENGTH(foto_wajah) > 30;

ALTER TABLE siswa DROP COLUMN foto_wajah;
ALTER TABLE pengunjung_perpustakaan DROP COLUMN foto_wajah;
