<?php

namespace Database\Seeders;

use App\Models\TahfidzAyat;
use App\Models\TahfidzSurah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TahfidzQuranSeeder extends Seeder
{
    public function run(): void
    {
        $base = database_path('seeders/data/quran');
        $surahPath = $base.'/surahs.json';
        $ayahPath = $base.'/ayahs.json';

        if (! File::exists($surahPath) || ! File::exists($ayahPath)) {
            $this->command?->warn('Tahfidz Quran fixture missing under database/seeders/data/quran.');

            return;
        }

        /** @var list<array{number: int, name_ar: string, name_id: string, ayah_count: int, revelation_type?: string|null}> $surahRows */
        $surahRows = json_decode(File::get($surahPath), true, 512, JSON_THROW_ON_ERROR);
        /** @var list<array{surah_number: int, ayah_number: int, text_ar: string, text_id?: string|null, juz: int, page: int}> $ayahRows */
        $ayahRows = json_decode(File::get($ayahPath), true, 512, JSON_THROW_ON_ERROR);

        $surahIdByNumber = [];

        foreach ($surahRows as $row) {
            $surah = TahfidzSurah::query()->updateOrCreate(
                ['number' => $row['number']],
                [
                    'name_ar' => $row['name_ar'],
                    'name_id' => $row['name_id'],
                    'ayah_count' => $row['ayah_count'],
                    'revelation_type' => $row['revelation_type'] ?? null,
                ],
            );
            $surahIdByNumber[$row['number']] = $surah->id;
        }

        foreach (array_chunk($ayahRows, 200) as $chunk) {
            foreach ($chunk as $row) {
                $surahId = $surahIdByNumber[$row['surah_number']] ?? null;
                if ($surahId === null) {
                    continue;
                }

                TahfidzAyat::query()->updateOrCreate(
                    [
                        'surah_id' => $surahId,
                        'ayah_number' => $row['ayah_number'],
                    ],
                    [
                        'text_ar' => $row['text_ar'],
                        'text_id' => $row['text_id'] ?? null,
                        'juz' => $row['juz'],
                        'page' => $row['page'],
                    ],
                );
            }
        }
    }
}
