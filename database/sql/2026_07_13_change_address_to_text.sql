-- Widen address columns from VARCHAR(255) to TEXT (siswa + profil_guru).
-- Safe on MySQL/MariaDB; no data loss. Run after deploying app code that allows longer addresses.

-- Preview lengths (optional)
-- SELECT MAX(CHAR_LENGTH(address)) AS max_len FROM siswa;
-- SELECT MAX(CHAR_LENGTH(address)) AS max_len FROM profil_guru;

ALTER TABLE siswa
    MODIFY COLUMN address TEXT NULL;

ALTER TABLE profil_guru
    MODIFY COLUMN address TEXT NULL;

-- Verify
-- SHOW COLUMNS FROM siswa LIKE 'address';
-- SHOW COLUMNS FROM profil_guru LIKE 'address';
