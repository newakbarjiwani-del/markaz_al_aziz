CREATE TABLE rfid (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uid VARCHAR(64) NOT NULL,
    siswa_id BIGINT UNSIGNED NULL,
    guru_id BIGINT UNSIGNED NULL,
    blocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY rfid_uid_unique (uid),
    UNIQUE KEY rfid_siswa_id_unique (siswa_id),
    UNIQUE KEY rfid_guru_id_unique (guru_id),
    CONSTRAINT rfid_siswa_id_foreign FOREIGN KEY (siswa_id) REFERENCES siswa (id) ON DELETE CASCADE,
    CONSTRAINT rfid_guru_id_foreign FOREIGN KEY (guru_id) REFERENCES guru (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER //
CREATE PROCEDURE abort_on_rfid_collision()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM siswa s
        INNER JOIN guru g ON g.rfid_uid = s.rfid_uid
        WHERE s.deleted_at IS NULL
          AND g.deleted_at IS NULL
          AND s.rfid_uid IS NOT NULL
          AND s.rfid_uid <> ''
    ) OR EXISTS (
        SELECT 1
        FROM siswa
        WHERE deleted_at IS NULL AND rfid_uid IS NOT NULL AND rfid_uid <> ''
        GROUP BY rfid_uid
        HAVING COUNT(*) > 1
    ) OR EXISTS (
        SELECT 1
        FROM guru
        WHERE deleted_at IS NULL AND rfid_uid IS NOT NULL AND rfid_uid <> ''
        GROUP BY rfid_uid
        HAVING COUNT(*) > 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migrasi RFID dibatalkan: UID aktif digunakan lebih dari satu pemilik';
    END IF;
END//
DELIMITER ;

CALL abort_on_rfid_collision();
DROP PROCEDURE abort_on_rfid_collision;

INSERT INTO rfid (uid, siswa_id, guru_id, blocked, created_at, updated_at)
SELECT rfid_uid, id, NULL, IFNULL(rfid_blocked, 0), NOW(), NOW()
FROM siswa
WHERE deleted_at IS NULL AND rfid_uid IS NOT NULL AND rfid_uid <> '';

INSERT INTO rfid (uid, siswa_id, guru_id, blocked, created_at, updated_at)
SELECT rfid_uid, NULL, id, 0, NOW(), NOW()
FROM guru
WHERE deleted_at IS NULL AND rfid_uid IS NOT NULL AND rfid_uid <> '';

ALTER TABLE siswa
    DROP INDEX siswa_rfid_uid_deleted_at_unique,
    DROP COLUMN rfid_uid,
    DROP COLUMN rfid_blocked;
ALTER TABLE guru
    DROP INDEX guru_rfid_uid_deleted_at_unique,
    DROP COLUMN rfid_uid;
