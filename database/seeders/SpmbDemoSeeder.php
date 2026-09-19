<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use App\Models\SpmbBerita;
use App\Models\SpmbGaleriItem;
use App\Models\SpmbPengumuman;
use App\Models\SpmbPeriode;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpmbDemoSeeder extends Seeder
{
    public const TAHUN_AKADEMIK = '2027/2028';

    public function run(): void
    {
        $tahun = TahunAkademik::query()->firstOrCreate(
            ['name' => self::TAHUN_AKADEMIK],
            ['is_active' => false, 'sekolah_id' => null],
        );

        $sekolah = Sekolah::query()->where('is_active', true)->orderBy('id')->first()
            ?? Sekolah::query()->orderBy('id')->first()
            ?? Sekolah::create([
                'code' => 'ITT',
                'name' => 'Yayasan Ittihad Pekanbaru',
                'address' => 'Pekanbaru',
                'is_active' => true,
            ]);

        SpmbPeriode::query()
            ->where('is_active', true)
            ->where('name', '!=', 'SPMB '.self::TAHUN_AKADEMIK)
            ->update(['is_active' => false]);

        SpmbPeriode::query()->updateOrCreate(
            ['name' => 'SPMB '.self::TAHUN_AKADEMIK],
            [
                'sekolah_id' => $sekolah->id,
                'tahun_akademik_id' => $tahun->id,
                'opens_at' => now()->subDay(),
                'closes_at' => now()->addMonths(3),
                'is_active' => true,
                'description' => 'Pendaftaran murid baru tahun ajaran '.self::TAHUN_AKADEMIK.'. Formulir online dibuka untuk calon siswa Yayasan Ittihad Pekanbaru.',
            ],
        );

        $this->seedPengumuman();
        $this->seedBerita();
        $this->seedGaleri();
    }

    private function seedPengumuman(): void
    {
        $items = [
            [
                'title' => 'Pembukaan SPMB '.self::TAHUN_AKADEMIK,
                'body' => "Assalamu'alaikum warahmatullahi wabarakatuh.\n\nDengan ini kami umumkan pembukaan Seleksi Penerimaan Murid Baru (SPMB) Yayasan Ittihad Pekanbaru untuk tahun ajaran ".self::TAHUN_AKADEMIK.".\n\nSilakan daftar melalui formulir online di halaman Daftar. Pastikan data orang tua/wali dan alamat diisi dengan benar.",
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Dokumen yang perlu disiapkan',
                'body' => "Siapkan salinan digital (PDF/JPG) dokumen berikut sebelum proses verifikasi:\n\n1. Akta kelahiran\n2. Kartu keluarga\n3. Pas foto 3×4 terbaru\n4. Ijazah/rapor terakhir (jika ada)\n\nUpload dokumen akan dilakukan setelah pendaftaran diverifikasi admin.",
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Jadwal singkat alur SPMB',
                'body' => "1. Isi formulir online → dapat nomor pendaftaran\n2. Verifikasi berkas oleh panitia\n3. Pengumuman hasil seleksi\n4. Daftar ulang bagi yang diterima\n\nPantau halaman Pengumuman dan Berita untuk update resmi.",
                'published_at' => now(),
            ],
        ];

        foreach ($items as $item) {
            SpmbPengumuman::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'body' => $item['body'],
                    'published_at' => $item['published_at'],
                    'is_published' => true,
                ],
            );
        }
    }

    private function seedBerita(): void
    {
        $items = [
            [
                'title' => 'Sambut tahun ajaran '.self::TAHUN_AKADEMIK,
                'body' => 'Yayasan Ittihad Pekanbaru membuka kesempatan bagi calon murid baru. Program akademik, asrama, dan pembinaan karakter menjadi fokus utama penerimaan tahun ini.',
            ],
            [
                'title' => 'Fasilitas kampus untuk calon siswa',
                'body' => 'Calon orang tua dapat melihat galeri fasilitas sekolah di halaman Galeri. Kunjungi juga pengumuman resmi untuk syarat dan jadwal lengkap SPMB.',
            ],
            [
                'title' => 'Tips mengisi formulir pendaftaran',
                'body' => 'Gunakan nama lengkap sesuai akta, nomor HP yang aktif untuk WhatsApp, dan pastikan data wali dapat dihubungi panitia. Setelah submit, simpan nomor pendaftaran Anda.',
            ],
        ];

        foreach ($items as $item) {
            $slug = Str::slug($item['title']);

            SpmbBerita::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $item['title'],
                    'body' => $item['body'],
                    'cover_path' => null,
                    'published_at' => now()->subHours(6),
                    'is_published' => true,
                ],
            );
        }
    }

    private function seedGaleri(): void
    {
        $source = public_path('logo.png');
        if (! File::isFile($source)) {
            return;
        }

        Storage::disk('public')->makeDirectory('spmb/galeri');

        $demos = [
            ['file' => 'demo-kampus.png', 'title' => 'Kampus Ittihad', 'caption' => 'Suasana lingkungan sekolah', 'sort' => 1],
            ['file' => 'demo-kegiatan.png', 'title' => 'Kegiatan siswa', 'caption' => 'Pembelajaran dan ekstrakurikuler', 'sort' => 2],
            ['file' => 'demo-asrama.png', 'title' => 'Fasilitas asrama', 'caption' => 'Area hunian santri', 'sort' => 3],
        ];

        foreach ($demos as $demo) {
            $relative = 'spmb/galeri/'.$demo['file'];
            $target = Storage::disk('public')->path($relative);

            if (! File::exists($target)) {
                File::copy($source, $target);
            }

            SpmbGaleriItem::query()->updateOrCreate(
                ['title' => $demo['title']],
                [
                    'caption' => $demo['caption'],
                    'image_path' => $relative,
                    'sort_order' => $demo['sort'],
                    'is_published' => true,
                ],
            );
        }
    }
}
