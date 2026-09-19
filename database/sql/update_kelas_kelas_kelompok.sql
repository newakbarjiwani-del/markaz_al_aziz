-- Update kelas.kelas + kelas.kelompok from production export (kelas.json)
-- kelas: roman (IX), named (Tsaniyah), or Kelompok for PAUD
-- Scope: sekolah_id 1-4 only; ICT test schools (5-6) untouched
SET NAMES utf8mb4;

UPDATE kelas SET kelas = 'Kelompok', kelompok = 'A', unit = 'PAUD', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Kelompok A' AND sekolah_id = 1;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 1, 'Kelompok A', 'Kelompok', 'A', 'PAUD', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Kelompok A' AND sekolah_id = 1);

UPDATE kelas SET kelas = 'Kelompok', kelompok = 'B', unit = 'PAUD', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Kelompok B' AND sekolah_id = 1;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 1, 'Kelompok B', 'Kelompok', 'B', 'PAUD', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Kelompok B' AND sekolah_id = 1);

UPDATE kelas SET kelas = 'Kelompok', kelompok = 'KB', unit = 'PAUD', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Kelompok KB' AND sekolah_id = 1;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 1, 'Kelompok KB', 'Kelompok', 'KB', 'PAUD', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Kelompok KB' AND sekolah_id = 1);

UPDATE kelas SET kelas = 'VII', kelompok = 'A', unit = 'MTs', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VII A' AND sekolah_id = 2;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 2, 'VII A', 'VII', 'A', 'MTs', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VII A' AND sekolah_id = 2);

UPDATE kelas SET kelas = 'VII', kelompok = 'B', unit = 'MTs', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VII B' AND sekolah_id = 2;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 2, 'VII B', 'VII', 'B', 'MTs', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VII B' AND sekolah_id = 2);

UPDATE kelas SET kelas = 'VIII', kelompok = 'A', unit = 'MTs', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII A' AND sekolah_id = 2;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 2, 'VIII A', 'VIII', 'A', 'MTs', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII A' AND sekolah_id = 2);

UPDATE kelas SET kelas = 'VIII', kelompok = 'B', unit = 'MTs', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII B' AND sekolah_id = 2;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 2, 'VIII B', 'VIII', 'B', 'MTs', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII B' AND sekolah_id = 2);

UPDATE kelas SET kelas = 'IX', kelompok = 'A', unit = 'MTs', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX A' AND sekolah_id = 2;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 2, 'IX A', 'IX', 'A', 'MTs', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX A' AND sekolah_id = 2);

UPDATE kelas SET kelas = 'IX', kelompok = 'B', unit = 'MTs', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX B' AND sekolah_id = 2;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 2, 'IX B', 'IX', 'B', 'MTs', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX B' AND sekolah_id = 2);

UPDATE kelas SET kelas = 'X', kelompok = 'IPA 1', unit = 'MA', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X IPA 1' AND sekolah_id = 3;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 3, 'X IPA 1', 'X', 'IPA 1', 'MA', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X IPA 1' AND sekolah_id = 3);

UPDATE kelas SET kelas = 'X', kelompok = 'IPA 2', unit = 'MA', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X IPA 2' AND sekolah_id = 3;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 3, 'X IPA 2', 'X', 'IPA 2', 'MA', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X IPA 2' AND sekolah_id = 3);

UPDATE kelas SET kelas = 'XI', kelompok = 'IPA 1', unit = 'MA', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI IPA 1' AND sekolah_id = 3;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 3, 'XI IPA 1', 'XI', 'IPA 1', 'MA', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI IPA 1' AND sekolah_id = 3);

UPDATE kelas SET kelas = 'XI', kelompok = 'IPS 1', unit = 'MA', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI IPS 1' AND sekolah_id = 3;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 3, 'XI IPS 1', 'XI', 'IPS 1', 'MA', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI IPS 1' AND sekolah_id = 3);

UPDATE kelas SET kelas = 'XII', kelompok = 'IPA 1', unit = 'MA', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII IPA 1' AND sekolah_id = 3;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 3, 'XII IPA 1', 'XII', 'IPA 1', 'MA', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII IPA 1' AND sekolah_id = 3);

UPDATE kelas SET kelas = 'Tsaniyah', kelompok = 'I', unit = 'Takhasus', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Tsaniyah I' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'Tsaniyah I', 'Tsaniyah', 'I', 'Takhasus', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Tsaniyah I' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'Tsaniyah', kelompok = 'II', unit = 'Takhasus', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Tsaniyah II' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'Tsaniyah II', 'Tsaniyah', 'II', 'Takhasus', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Tsaniyah II' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'Aliyah', kelompok = 'I', unit = 'Takhasus', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Aliyah I' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'Aliyah I', 'Aliyah', 'I', 'Takhasus', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Aliyah I' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'Aliyah', kelompok = 'II', unit = 'Takhasus', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Aliyah II' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'Aliyah II', 'Aliyah', 'II', 'Takhasus', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Aliyah II' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'Takhossus', kelompok = NULL, unit = 'Takhasus', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'Takhossus' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'Takhossus', 'Takhossus', NULL, 'Takhasus', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'Takhossus' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '21-AN-NAWAWI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 21-AN-NAWAWI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 21-AN-NAWAWI', 'XII', '21-AN-NAWAWI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 21-AN-NAWAWI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '24-ABU ZAHRO', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 24-ABU ZAHRO' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 24-ABU ZAHRO', 'XII', '24-ABU ZAHRO', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 24-ABU ZAHRO' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '07-IMAM SYAFE''I', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 07-IMAM SYAFE''I' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 07-IMAM SYAFE''I', 'XII', '07-IMAM SYAFE''I', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 07-IMAM SYAFE''I' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '01-IBNU HAJAR', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 01-IBNU HAJAR' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 01-IBNU HAJAR', 'XII', '01-IBNU HAJAR', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 01-IBNU HAJAR' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '06-IMAM MALIKI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 06-IMAM MALIKI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 06-IMAM MALIKI', 'XII', '06-IMAM MALIKI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 06-IMAM MALIKI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '26-AL-KINDI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 26-AL-KINDI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 26-AL-KINDI', 'XII', '26-AL-KINDI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 26-AL-KINDI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '19-AL-MUSLIM', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 19-AL-MUSLIM' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 19-AL-MUSLIM', 'XII', '19-AL-MUSLIM', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 19-AL-MUSLIM' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '08-ABU BAKAR AS-SHIDIQ', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 08-ABU BAKAR AS-SHIDIQ' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 08-ABU BAKAR AS-SHIDIQ', 'XII', '08-ABU BAKAR AS-SHIDIQ', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 08-ABU BAKAR AS-SHIDIQ' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '02-IMAM GHOJALI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 02-IMAM GHOJALI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 02-IMAM GHOJALI', 'XII', '02-IMAM GHOJALI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 02-IMAM GHOJALI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '25-ABU YAZID', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 25-ABU YAZID' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 25-ABU YAZID', 'XII', '25-ABU YAZID', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 25-ABU YAZID' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '22-AL-ASFIHANI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 22-AL-ASFIHANI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 22-AL-ASFIHANI', 'XII', '22-AL-ASFIHANI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 22-AL-ASFIHANI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '03-ABU NAWAS', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 03-ABU NAWAS' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 03-ABU NAWAS', 'XII', '03-ABU NAWAS', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 03-ABU NAWAS' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '23-AL-KAILANI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 23-AL-KAILANI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 23-AL-KAILANI', 'XII', '23-AL-KAILANI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 23-AL-KAILANI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '20-AL-BUKHORI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 20-AL-BUKHORI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 20-AL-BUKHORI', 'XII', '20-AL-BUKHORI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 20-AL-BUKHORI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '15-NADZOM MAQSHUD', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 15-NADZOM MAQSHUD' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 15-NADZOM MAQSHUD', 'XII', '15-NADZOM MAQSHUD', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 15-NADZOM MAQSHUD' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '04-IMAM HANAFI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 04-IMAM HANAFI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 04-IMAM HANAFI', 'XII', '04-IMAM HANAFI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 04-IMAM HANAFI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '01-IBNU MALIK', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 01-IBNU MALIK' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 01-IBNU MALIK', 'XII', '01-IBNU MALIK', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 01-IBNU MALIK' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '15-UMMU KULSUM', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 15-UMMU KULSUM' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 15-UMMU KULSUM', 'XII', '15-UMMU KULSUM', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 15-UMMU KULSUM' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '22-AL KAROMAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 22-AL KAROMAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 22-AL KAROMAH', 'XII', '22-AL KAROMAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 22-AL KAROMAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '04-AL KAHFI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 04-AL KAHFI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 04-AL KAHFI', 'XII', '04-AL KAHFI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 04-AL KAHFI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '06-AL WAQIAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 06-AL WAQIAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 06-AL WAQIAH', 'XII', '06-AL WAQIAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 06-AL WAQIAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '14-SITI KHODIJAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 14-SITI KHODIJAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 14-SITI KHODIJAH', 'XII', '14-SITI KHODIJAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 14-SITI KHODIJAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '02-ASSYARQOWI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 02-ASSYARQOWI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 02-ASSYARQOWI', 'XII', '02-ASSYARQOWI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 02-ASSYARQOWI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '05-AL MULK', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 05-AL MULK' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 05-AL MULK', 'XII', '05-AL MULK', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 05-AL MULK' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '07-AL MAIDAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 07-AL MAIDAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 07-AL MAIDAH', 'XII', '07-AL MAIDAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 07-AL MAIDAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '03-IBNU SINA', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 03-IBNU SINA' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 03-IBNU SINA', 'XII', '03-IBNU SINA', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 03-IBNU SINA' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '08-AL BAQARAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 08-AL BAQARAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 08-AL BAQARAH', 'XII', '08-AL BAQARAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 08-AL BAQARAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '09-AL ANFAL', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 09-AL ANFAL' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 09-AL ANFAL', 'XII', '09-AL ANFAL', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 09-AL ANFAL' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '19-AR RAHMAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 19-AR RAHMAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 19-AR RAHMAH', 'XII', '19-AR RAHMAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 19-AR RAHMAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '20-AL MAGFIROH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 20-AL MAGFIROH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 20-AL MAGFIROH', 'XI', '20-AL MAGFIROH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 20-AL MAGFIROH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '06-AL WAQIAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 06-AL WAQIAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 06-AL WAQIAH', 'XI', '06-AL WAQIAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 06-AL WAQIAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '02-ASSYARQOWI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 02-ASSYARQOWI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 02-ASSYARQOWI', 'XI', '02-ASSYARQOWI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 02-ASSYARQOWI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '09-UMAR BIN KHATTAB', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 09-UMAR BIN KHATTAB' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 09-UMAR BIN KHATTAB', 'XI', '09-UMAR BIN KHATTAB', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 09-UMAR BIN KHATTAB' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '08-ABU BAKAR AS-SHIDIQ', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 08-ABU BAKAR AS-SHIDIQ' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 08-ABU BAKAR AS-SHIDIQ', 'XI', '08-ABU BAKAR AS-SHIDIQ', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 08-ABU BAKAR AS-SHIDIQ' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '11-AL QODR', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 11-AL QODR' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 11-AL QODR', 'XI', '11-AL QODR', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 11-AL QODR' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '01-IBNU MALIK', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 01-IBNU MALIK' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 01-IBNU MALIK', 'XI', '01-IBNU MALIK', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 01-IBNU MALIK' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '18-AL BAROKAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 18-AL BAROKAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 18-AL BAROKAH', 'XI', '18-AL BAROKAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 18-AL BAROKAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '03-IBNU SINA', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 03-IBNU SINA' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 03-IBNU SINA', 'XI', '03-IBNU SINA', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 03-IBNU SINA' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '05-AL MULK', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 05-AL MULK' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 05-AL MULK', 'XI', '05-AL MULK', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 05-AL MULK' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '08-AL BAQARAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 08-AL BAQARAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 08-AL BAQARAH', 'XI', '08-AL BAQARAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 08-AL BAQARAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = NULL, unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI', 'XI', NULL, 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '04-AL KAHFI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 04-AL KAHFI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 04-AL KAHFI', 'XI', '04-AL KAHFI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 04-AL KAHFI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '06-AL WAQIAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 06-AL WAQIAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 06-AL WAQIAH', 'X', '06-AL WAQIAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 06-AL WAQIAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '15-NADZOM MAQSHUD', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 15-NADZOM MAQSHUD' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 15-NADZOM MAQSHUD', 'X', '15-NADZOM MAQSHUD', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 15-NADZOM MAQSHUD' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '18-AL BAROKAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 18-AL BAROKAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 18-AL BAROKAH', 'X', '18-AL BAROKAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 18-AL BAROKAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '11-AL QODR', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 11-AL QODR' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 11-AL QODR', 'X', '11-AL QODR', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 11-AL QODR' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '02-IMAM GHOJALI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 02-IMAM GHOJALI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 02-IMAM GHOJALI', 'X', '02-IMAM GHOJALI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 02-IMAM GHOJALI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '01-IBNU MALIK', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 01-IBNU MALIK' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 01-IBNU MALIK', 'X', '01-IBNU MALIK', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 01-IBNU MALIK' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '03-IBNU SINA', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 03-IBNU SINA' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 03-IBNU SINA', 'X', '03-IBNU SINA', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 03-IBNU SINA' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '01-IBNU HAJAR', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 01-IBNU HAJAR' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 01-IBNU HAJAR', 'X', '01-IBNU HAJAR', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 01-IBNU HAJAR' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '23-AL FADHILAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 23-AL FADHILAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 23-AL FADHILAH', 'X', '23-AL FADHILAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 23-AL FADHILAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '20-AL MAGFIROH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 20-AL MAGFIROH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 20-AL MAGFIROH', 'X', '20-AL MAGFIROH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 20-AL MAGFIROH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'X', kelompok = '21-AS SAKINAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'X 21-AS SAKINAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'X 21-AS SAKINAH', 'X', '21-AS SAKINAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'X 21-AS SAKINAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '20-AL-BUKHORI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 20-AL-BUKHORI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 20-AL-BUKHORI', 'IX', '20-AL-BUKHORI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 20-AL-BUKHORI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '01-IBNU HAJAR', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 01-IBNU HAJAR' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 01-IBNU HAJAR', 'IX', '01-IBNU HAJAR', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 01-IBNU HAJAR' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '26-AL-KINDI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 26-AL-KINDI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 26-AL-KINDI', 'IX', '26-AL-KINDI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 26-AL-KINDI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '02-IMAM GHOJALI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 02-IMAM GHOJALI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 02-IMAM GHOJALI', 'IX', '02-IMAM GHOJALI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 02-IMAM GHOJALI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '23-AL-KAILANI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 23-AL-KAILANI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 23-AL-KAILANI', 'IX', '23-AL-KAILANI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 23-AL-KAILANI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '19-AL-MUSLIM', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 19-AL-MUSLIM' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 19-AL-MUSLIM', 'IX', '19-AL-MUSLIM', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 19-AL-MUSLIM' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '22-AL-ASFIHANI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 22-AL-ASFIHANI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 22-AL-ASFIHANI', 'IX', '22-AL-ASFIHANI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 22-AL-ASFIHANI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '19-AR RAHMAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 19-AR RAHMAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 19-AR RAHMAH', 'IX', '19-AR RAHMAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 19-AR RAHMAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '21-AS SAKINAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 21-AS SAKINAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 21-AS SAKINAH', 'IX', '21-AS SAKINAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 21-AS SAKINAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '20-AL MAGFIROH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 20-AL MAGFIROH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 20-AL MAGFIROH', 'IX', '20-AL MAGFIROH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 20-AL MAGFIROH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '25-ABU YAZID', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 25-ABU YAZID' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 25-ABU YAZID', 'IX', '25-ABU YAZID', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 25-ABU YAZID' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '23-AL FADHILAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 23-AL FADHILAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 23-AL FADHILAH', 'IX', '23-AL FADHILAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 23-AL FADHILAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '18-AL BAROKAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 18-AL BAROKAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 18-AL BAROKAH', 'IX', '18-AL BAROKAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 18-AL BAROKAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '24-ABU ZAHRO', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 24-ABU ZAHRO' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 24-ABU ZAHRO', 'IX', '24-ABU ZAHRO', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 24-ABU ZAHRO' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '21-AN-NAWAWI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 21-AN-NAWAWI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 21-AN-NAWAWI', 'IX', '21-AN-NAWAWI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 21-AN-NAWAWI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '13-SITI AISYAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 13-SITI AISYAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 13-SITI AISYAH', 'XII', '13-SITI AISYAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 13-SITI AISYAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '22-AL KAROMAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 22-AL KAROMAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 22-AL KAROMAH', 'IX', '22-AL KAROMAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 22-AL KAROMAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '09-UMAR BIN KHATTAB', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 09-UMAR BIN KHATTAB' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 09-UMAR BIN KHATTAB', 'XII', '09-UMAR BIN KHATTAB', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 09-UMAR BIN KHATTAB' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '13-SITI AISYAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 13-SITI AISYAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 13-SITI AISYAH', 'IX', '13-SITI AISYAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 13-SITI AISYAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '12-SITI FATIMAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 12-SITI FATIMAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 12-SITI FATIMAH', 'XII', '12-SITI FATIMAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 12-SITI FATIMAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '03-IBNU SINA', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 03-IBNU SINA' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 03-IBNU SINA', 'IX', '03-IBNU SINA', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 03-IBNU SINA' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '05-IMAM HAMBALI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 05-IMAM HAMBALI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 05-IMAM HAMBALI', 'XII', '05-IMAM HAMBALI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 05-IMAM HAMBALI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = '11-AL QODR', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII 11-AL QODR' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII 11-AL QODR', 'XII', '11-AL QODR', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII 11-AL QODR' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '11- AL QODR', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 11- AL QODR' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 11- AL QODR', 'XI', '11- AL QODR', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 11- AL QODR' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '06-IMAM MALIKI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 06-IMAM MALIKI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 06-IMAM MALIKI', 'VIII', '06-IMAM MALIKI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 06-IMAM MALIKI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '14-SITI KHODIJAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 14-SITI KHODIJAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 14-SITI KHODIJAH', 'VIII', '14-SITI KHODIJAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 14-SITI KHODIJAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '03-ABU NAWAS', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 03-ABU NAWAS' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 03-ABU NAWAS', 'VIII', '03-ABU NAWAS', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 03-ABU NAWAS' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '05-IMAM HAMBALI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 05-IMAM HAMBALI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 05-IMAM HAMBALI', 'VIII', '05-IMAM HAMBALI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 05-IMAM HAMBALI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '17-HAPSAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 17-HAPSAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 17-HAPSAH', 'VIII', '17-HAPSAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 17-HAPSAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '15-UMMU KULSUM', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 15-UMMU KULSUM' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 15-UMMU KULSUM', 'VIII', '15-UMMU KULSUM', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 15-UMMU KULSUM' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '04-IMAM HANAFI', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 04-IMAM HANAFI' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 04-IMAM HANAFI', 'VIII', '04-IMAM HANAFI', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 04-IMAM HANAFI' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '16-RUQOYAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 16-RUQOYAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 16-RUQOYAH', 'VIII', '16-RUQOYAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 16-RUQOYAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '12-SITI FATIMAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 12-SITI FATIMAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 12-SITI FATIMAH', 'VIII', '12-SITI FATIMAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 12-SITI FATIMAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '07-IMAM SYAFE''I', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 07-IMAM SYAFE''I' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 07-IMAM SYAFE''I', 'VIII', '07-IMAM SYAFE''I', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 07-IMAM SYAFE''I' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '13-SITI AISYAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 13-SITI AISYAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 13-SITI AISYAH', 'VIII', '13-SITI AISYAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 13-SITI AISYAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'IX', kelompok = '17-HAPSAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'IX 17-HAPSAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'IX 17-HAPSAH', 'IX', '17-HAPSAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'IX 17-HAPSAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '10-AL-ANFAL', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 10-AL-ANFAL' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 10-AL-ANFAL', 'XI', '10-AL-ANFAL', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 10-AL-ANFAL' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XI', kelompok = '09-ANNISA', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XI 09-ANNISA' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XI 09-ANNISA', 'XI', '09-ANNISA', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XI 09-ANNISA' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'VIII', kelompok = '19-AR RAHMAH', unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII 19-AR RAHMAH' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII 19-AR RAHMAH', 'VIII', '19-AR RAHMAH', 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII 19-AR RAHMAH' AND sekolah_id = 4);

UPDATE kelas SET kelas = 'XII', kelompok = NULL, unit = 'TAKHASUS', jenjang = NULL, wali_kelas = NULL, is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'XII' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'XII', 'XII', NULL, 'TAKHASUS', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'XII' AND sekolah_id = 4);

UPDATE kelas SET kelas = NULL, kelompok = NULL, unit = 'Mts', jenjang = NULL, wali_kelas = 'Nazmi Agis', is_active = 1, deleted_at = NULL, updated_at = NOW() WHERE name = 'VIII_A' AND sekolah_id = 4;
INSERT INTO kelas (sekolah_id, name, kelas, kelompok, unit, jenjang, wali_kelas, is_active, created_at, updated_at)
SELECT 4, 'VIII_A', NULL, NULL, 'Mts', NULL, 'Nazmi Agis', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM kelas WHERE name = 'VIII_A' AND sekolah_id = 4);
