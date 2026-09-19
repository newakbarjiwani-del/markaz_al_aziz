-- Clear legacy auto-generated siswa RFID.
-- Patterns:
--   S{sekolah 2 dig}{NIS padded to 10}   e.g. S010000012345
--   S{sekolah 2 dig}{full NIS}           e.g. S01512336041084200004 (NIS longer than 10)
-- Also matches when the 2-digit prefix no longer equals current sekolah_id.
--
-- Prefer: php artisan siswa:clear-auto-rfid --dry-run
--         php artisan siswa:clear-auto-rfid --force
--
-- Note: MySQL LPAD(nis, 10) TRUNCATES when NIS length > 10 — never use that alone.

DELETE r
FROM rfid r
INNER JOIN siswa s ON s.id = r.siswa_id
WHERE s.deleted_at IS NULL
  AND r.uid IS NOT NULL
  AND r.uid <> ''
  AND (
      -- Exact match with current sekolah_id (short NIS → left-pad to 10)
      (
          s.sekolah_id IS NOT NULL
          AND CHAR_LENGTH(REGEXP_REPLACE(s.nis, '[^0-9]', '')) < 10
          AND r.uid = CONCAT(
                'S',
                LPAD(s.sekolah_id, 2, '0'),
                LPAD(REGEXP_REPLACE(s.nis, '[^0-9]', ''), 10, '0')
              )
      )
      -- Exact match with current sekolah_id (full / long NIS, no truncate)
      OR (
          s.sekolah_id IS NOT NULL
          AND r.uid = CONCAT(
                'S',
                LPAD(s.sekolah_id, 2, '0'),
                REGEXP_REPLACE(s.nis, '[^0-9]', '')
              )
      )
      -- Any S{2 digits} + full NIS digits
      OR r.uid REGEXP CONCAT(
            '^S[0-9]{2}',
            REGEXP_REPLACE(s.nis, '[^0-9]', ''),
            '$'
          )
      -- Any S{2 digits} + 10-digit padded NIS (short NIS only)
      OR (
          CHAR_LENGTH(REGEXP_REPLACE(s.nis, '[^0-9]', '')) < 10
          AND r.uid REGEXP CONCAT(
                '^S[0-9]{2}',
                LPAD(REGEXP_REPLACE(s.nis, '[^0-9]', ''), 10, '0'),
                '$'
              )
      )
  );
