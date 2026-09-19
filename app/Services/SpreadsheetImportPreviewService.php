<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Support\ImportSpreadsheetPreviewCache;
use App\Support\ImportStoreMethod;
use InvalidArgumentException;

class SpreadsheetImportPreviewService
{
    public function __construct(
        private StudentSpreadsheetImporter $studentImporter,
        private TeacherSpreadsheetImporter $teacherImporter,
        private TagihanSpreadsheetImporter $tagihanImporter,
        private BukuSpreadsheetImporter $bukuImporter,
        private PrestasiSpreadsheetImporter $prestasiImporter,
        private PelanggaranSpreadsheetImporter $pelanggaranImporter,
    ) {}

    /** @return array<string, mixed> */
    public function buildPreview(int $userId, string $type, ?Sekolah $sekolah, array $rows, string $fileName): array
    {
        $preview = $this->importer($type)->preview($sekolah, $rows);

        $payload = array_merge($preview, [
            'type' => $type,
            'sekolah_id' => $sekolah?->id,
            'sekolah_name' => $sekolah?->name ?? 'Katalog Global',
            'file_name' => $fileName,
            'cached_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds(ImportSpreadsheetPreviewCache::TTL_SECONDS)->toIso8601String(),
        ]);

        ImportSpreadsheetPreviewCache::put($userId, $type, $payload);

        return $payload;
    }

    /** @return array<string, mixed>|null */
    public function cachedPreview(int $userId, string $type): ?array
    {
        return ImportSpreadsheetPreviewCache::get($userId, $type);
    }

    /** @return array{created: int, updated: int, skipped: int, errors: list<string>} */
    public function confirm(int $userId, string $type, ?Sekolah $sekolah, string $method): array
    {
        if (! in_array($method, ImportStoreMethod::all(), true)) {
            throw new InvalidArgumentException('Metode import tidak valid.');
        }

        $cached = ImportSpreadsheetPreviewCache::get($userId, $type);

        if ($cached === null) {
            throw new InvalidArgumentException('Pratinjau import tidak ditemukan atau sudah kedaluwarsa. Unggah ulang file Excel.');
        }

        $cachedSekolahId = $cached['sekolah_id'] ?? null;
        $currentSekolahId = $sekolah?->id;

        if ($cachedSekolahId !== $currentSekolahId) {
            throw new InvalidArgumentException('Sekolah tujuan import tidak sesuai dengan pratinjau yang tersimpan.');
        }

        $rows = $cached['rows'] ?? [];

        if (! is_array($rows) || $rows === []) {
            throw new InvalidArgumentException('Data pratinjau kosong.');
        }

        $result = $this->importer($type)->importFromPreview($sekolah, $rows, $method);

        ImportSpreadsheetPreviewCache::forget($userId, $type);

        return $result;
    }

    public function clear(int $userId, string $type): bool
    {
        return ImportSpreadsheetPreviewCache::forget($userId, $type);
    }

    private function importer(string $type): StudentSpreadsheetImporter|TeacherSpreadsheetImporter|TagihanSpreadsheetImporter|BukuSpreadsheetImporter|SiswaCatatanSpreadsheetImporter
    {
        return match ($type) {
            'siswa' => $this->studentImporter,
            'guru' => $this->teacherImporter,
            'tagihan' => $this->tagihanImporter,
            'buku' => $this->bukuImporter,
            'prestasi' => $this->prestasiImporter,
            'pelanggaran' => $this->pelanggaranImporter,
            default => throw new InvalidArgumentException('Tipe import tidak dikenali.'),
        };
    }
}
