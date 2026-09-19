<?php

namespace Database\Seeders;

use App\Models\Booklet;
use App\Models\Sekolah;
use Illuminate\Database\Seeder;

class BookletDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sekolah = Sekolah::query()->where('is_active', true)->orderBy('id')->first()
            ?? Sekolah::query()->orderBy('id')->first();

        $booklet = Booklet::query()->updateOrCreate(
            ['slug' => 'profil-sekolah-demo'],
            [
                'sekolah_id' => $sekolah?->id,
                'title' => 'Profil Sekolah Demo',
                'summary' => 'Booklet digital berisi profil singkat, program unggulan, dan fasilitas sekolah.',
                'cover_path' => null,
                'is_published' => true,
                'published_at' => now(),
            ],
        );

        if ($booklet->pages()->doesntExist()) {
            $booklet->pages()->create([
                'sort_order' => 1,
                'title' => 'Sambutan',
                'body' => "Assalamu'alaikum warahmatullahi wabarakatuh.\n\nSelamat datang di booklet digital Yayasan Ittihad Pekanbaru. Semoga informasi ini bermanfaat bagi guru, orang tua, dan siswa.",
                'file_path' => null,
            ]);

            $booklet->pages()->create([
                'sort_order' => 2,
                'title' => 'Program Unggulan',
                'body' => "1. Tahfidz dan pendidikan karakter\n2. Pengembangan akademik terpadu\n3. Kegiatan ekstrakurikuler",
                'file_path' => null,
            ]);
        }
    }
}
