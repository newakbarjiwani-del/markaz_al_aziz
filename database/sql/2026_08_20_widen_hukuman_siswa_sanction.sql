-- Applied punishment on hukuman_siswa is free text (catalog recommendation stored separately).
ALTER TABLE `hukuman_siswa`
    MODIFY COLUMN `sanction` VARCHAR(255) NULL;
