<?php

namespace Database\Seeders;

use App\Models\JenisPelanggaran;
use App\Support\PelanggaranLevel;
use App\Support\PelanggaranSanction;
use Illuminate\Database\Seeder;

/**
 * Seeds the universal pelanggaran catalog from database/seeders/data/violations.json
 * (extracted from the Google Form). Idempotent — upserts by nama.
 *
 * JSON shape:
 *   [{ "heading": "Pelanggaran Ringan Bidang Kebersihan", "level": "ringan",
 *      "items": [{ "name": ..., "point": 5, "sanction": "SP3" }] },
 *    { "heading": "PELANGGARAN BERAT", "level": "berat",
 *      "groups": [{ "name": "Pelanggaran Berat Bidang Akidah dan Akhlak",
 *                   "items": [...] }] }]
 */
class PelanggaranCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/violations.json');

        if (! file_exists($path)) {
            $this->command?->warn('violations.json not found — skipping pelanggaran catalog.');

            return;
        }

        $sections = json_decode((string) file_get_contents($path), true);

        if (! is_array($sections)) {
            $this->command?->warn('violations.json is not valid JSON — skipping pelanggaran catalog.');

            return;
        }

        $rows = [];
        $seenNames = [];

        foreach ($sections as $section) {
            $level = PelanggaranLevel::normalize($section['level'] ?? null);

            if (isset($section['groups']) && is_array($section['groups'])) {
                // Berat: one catalog row per sub-bidang group.
                foreach ($section['groups'] as $group) {
                    $bidang = $this->bidangFromName($group['name'] ?? '');
                    foreach (($group['items'] ?? []) as $item) {
                        $row = $this->makeRow($level, $bidang, $item);
                        $this->disambiguateName($row, $seenNames);
                        $rows[] = $row;
                    }
                }

                continue;
            }

            $bidang = $this->bidangFromName($section['heading'] ?? '');
            foreach (($section['items'] ?? []) as $item) {
                $row = $this->makeRow($level, $bidang, $item);
                $this->disambiguateName($row, $seenNames);
                $rows[] = $row;
            }
        }

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $existing = JenisPelanggaran::withTrashed()->where('nama', $row['nama'])->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($row);
                $updated++;
            } else {
                JenisPelanggaran::create($row);
                $created++;
            }
        }

        $this->command?->info("Pelanggaran catalog: {$created} created, {$updated} updated (".count($rows).' total).');
    }

    /**
     * Two distinct source items can share a nama across different bidang/level
     * (e.g. "Tidak melapor setelah kembali dari izin" in Ringan/Administrasi and
     * Sedang/Disiplin). The master catalog keys on unique nama, so append a
     * parenthetical bidang to later duplicates to keep both rows.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, true>  $seenNames
     */
    private function disambiguateName(array &$row, array &$seenNames): void
    {
        $nama = $row['nama'];

        if (! isset($seenNames[$nama])) {
            $seenNames[$nama] = true;

            return;
        }

        $suffix = " ({$row['bidang']})";
        $candidate = $nama.$suffix;
        $i = 2;

        while (isset($seenNames[$candidate])) {
            $candidate = $nama." ({$row['bidang']} $i)";
            $i++;
        }

        $row['nama'] = $candidate;
        $seenNames[$candidate] = true;
    }

    /**
     * Extract the bidang (category) from a heading / group name.
     * "Pelanggaran Ringan Bidang Kebersihan"      -> "Kebersihan"
     * "Bagian Kesembilan (... Bidang Barang Terlarang)" -> "Barang Terlarang"
     * "Pelanggaran Berat Bidang Akidah dan Akhlak" -> "Akidah dan Akhlak"
     */
    private function bidangFromName(string $name): string
    {
        $name = trim($name);

        if (preg_match('/Bidang\s+(.+?)\s*\)?\s*$/u', $name, $m)) {
            return trim($m[1]);
        }

        return $name !== '' ? $name : 'Umum';
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function makeRow(?string $level, string $bidang, array $item): array
    {
        return [
            'sekolah_id' => null, // universal catalog
            'kode' => null,
            'level' => $level,
            'bidang' => $bidang,
            'nama' => trim((string) ($item['name'] ?? '')),
            'point' => (int) ($item['point'] ?? 0),
            'sanction' => PelanggaranSanction::normalize($item['sanction'] ?? null),
            'keterangan' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
