-- ============================================================================
-- Potongan Tagihan schema (live MySQL / Navicat) — 2026-08-28
--
-- Preferred deploy: php artisan migrate --force
-- Manual: mysqldump backup first, then run this script once (idempotent steps).
--
-- AFTER (if permissions script not run): php artisan permission:cache-reset
-- VERIFY:
--   SHOW COLUMNS FROM tagihan LIKE 'amount_bruto';
--   SHOW COLUMNS FROM tagihan LIKE 'potongan_amount';
--   SELECT COUNT(*) FROM jenis_potongan;
-- ============================================================================

START TRANSACTION;

CREATE TABLE IF NOT EXISTS jenis_potongan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sekolah_id BIGINT UNSIGNED NULL,
    kode VARCHAR(50) NULL,
    nama VARCHAR(255) NOT NULL,
    tipe_default VARCHAR(10) NOT NULL DEFAULT 'percent',
    nilai_default INT UNSIGNED NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY jenis_potongan_nama_deleted_unique (nama, deleted_at),
    KEY jenis_potongan_sekolah_active_idx (sekolah_id, is_active),
    CONSTRAINT jenis_potongan_sekolah_fk FOREIGN KEY (sekolah_id) REFERENCES sekolah (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS potongan_siswa (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sekolah_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    jenis_potongan_id BIGINT UNSIGNED NOT NULL,
    tipe VARCHAR(10) NOT NULL,
    nilai INT UNSIGNED NOT NULL,
    berlaku_mulai DATE NOT NULL,
    berlaku_sampai DATE NOT NULL,
    max_pemakaian SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'aktif',
    keterangan TEXT NULL,
    processed_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    KEY potongan_siswa_siswa_status_idx (siswa_id, status),
    KEY potongan_siswa_sekolah_status_idx (sekolah_id, status),
    KEY potongan_siswa_date_idx (berlaku_mulai, berlaku_sampai),
    CONSTRAINT potongan_siswa_sekolah_fk FOREIGN KEY (sekolah_id) REFERENCES sekolah (id) ON DELETE CASCADE,
    CONSTRAINT potongan_siswa_siswa_fk FOREIGN KEY (siswa_id) REFERENCES siswa (id) ON DELETE CASCADE,
    CONSTRAINT potongan_siswa_jenis_fk FOREIGN KEY (jenis_potongan_id) REFERENCES jenis_potongan (id) ON DELETE RESTRICT,
    CONSTRAINT potongan_siswa_user_fk FOREIGN KEY (processed_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS potongan_siswa_jenis_tagihan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    potongan_siswa_id BIGINT UNSIGNED NOT NULL,
    jenis_tagihan_id BIGINT UNSIGNED NOT NULL,
    tipe VARCHAR(10) NULL COMMENT 'percent|fixed',
    nilai INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY potongan_siswa_jenis_unique (potongan_siswa_id, jenis_tagihan_id),
    CONSTRAINT ps_jt_potongan_fk FOREIGN KEY (potongan_siswa_id) REFERENCES potongan_siswa (id) ON DELETE CASCADE,
    CONSTRAINT ps_jt_jenis_fk FOREIGN KEY (jenis_tagihan_id) REFERENCES jenis_tagihan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS potongan_pemakaian (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    potongan_siswa_id BIGINT UNSIGNED NOT NULL,
    tagihan_id BIGINT UNSIGNED NOT NULL,
    amount_bruto DECIMAL(15,2) NOT NULL,
    potongan_amount DECIMAL(15,2) NOT NULL,
    amount_net DECIMAL(15,2) NOT NULL,
    urutan TINYINT UNSIGNED NOT NULL DEFAULT 1,
    applied_at DATETIME NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY potongan_pemakaian_ps_tagihan_unique (potongan_siswa_id, tagihan_id),
    KEY potongan_pemakaian_tagihan_idx (tagihan_id),
    CONSTRAINT pp_potongan_fk FOREIGN KEY (potongan_siswa_id) REFERENCES potongan_siswa (id) ON DELETE RESTRICT,
    CONSTRAINT pp_tagihan_fk FOREIGN KEY (tagihan_id) REFERENCES tagihan (id) ON DELETE CASCADE,
    CONSTRAINT pp_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_amount_bruto := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tagihan' AND COLUMN_NAME = 'amount_bruto'
);
SET @sql_amount_bruto := IF(
    @has_amount_bruto = 0,
    'ALTER TABLE tagihan ADD COLUMN amount_bruto DECIMAL(15,2) NULL AFTER amount',
    'SELECT 1'
);
PREPARE stmt FROM @sql_amount_bruto;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_potongan_amount := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tagihan' AND COLUMN_NAME = 'potongan_amount'
);
SET @sql_potongan_amount := IF(
    @has_potongan_amount = 0,
    'ALTER TABLE tagihan ADD COLUMN potongan_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER amount_bruto',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql_potongan_amount;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

INSERT INTO jenis_potongan (nama, kode, tipe_default, nilai_default, sort_order, is_active, keterangan, created_at, updated_at)
SELECT 'Beasiswa', 'BEASISWA', 'percent', 50, 1, 1, 'Potongan beasiswa (default 50%).', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM jenis_potongan WHERE nama = 'Beasiswa' AND deleted_at IS NULL
);

INSERT INTO jenis_potongan (nama, kode, tipe_default, nilai_default, sort_order, is_active, keterangan, created_at, updated_at)
SELECT 'Kurang Mampu', 'KURANG_MAMPU', 'fixed', 100000, 2, 1, 'Potongan siswa kurang mampu (default Rp 100.000).', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM jenis_potongan WHERE nama = 'Kurang Mampu' AND deleted_at IS NULL
);

COMMIT;
