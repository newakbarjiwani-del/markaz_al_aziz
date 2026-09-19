<?php

namespace Database\Seeders;

use App\Models\TemplatePesanTagihan;
use App\Support\TagihanPesanKategori;
use Illuminate\Database\Seeder;

class TemplatePesanTagihanSeeder extends Seeder
{
    public function run(): void
    {
        $instansi = config('app.nama_instansi', 'YAYASAN ITTIHAD PEKANBARU');
        $footer = "\n\n*_*pesan otomatis dari {$instansi}_*\n*_*Silahkan menghubungi bagian keuangan jika ada kesalahan tagihan atau nama siswa_*";

        $rows = [
            [
                'nama' => 'Formal Indonesia (belum jatuh tempo)',
                'kategori' => TagihanPesanKategori::BELUM_JATUH_TEMPO,
                'sort_order' => 1,
                'isi_pesan' => "Assalamualaikum Warahmatullahi wabarakatuh\n{sapaan}, kami dari keuangan {$instansi}\nMemberitahukan kepada wali santri atas nama {nama_anak}\nMasih ada kekurangan pembayaran sebesar {jumlah_tagihan}, dengan rincian :\n{rincian}Mohon untuk segera di tunaikan, atas perhatiannya kami sampaikan \nJazaakumullah khairan katsiran.{$footer}",
            ],
            [
                'nama' => 'Arabic opening (belum jatuh tempo)',
                'kategori' => TagihanPesanKategori::BELUM_JATUH_TEMPO,
                'sort_order' => 2,
                'isi_pesan' => "-السلام عليكم ورحمة الله وبركاته-\nKami dari {$instansi}, memberitahukan kepada wali santri atas nama {nama_anak}\nAnanda masih memiliki kekurangan pembayaran sebesar {jumlah_tagihan}, dengan rincian :\n{rincian}Mohon untuk segera di tunaikan, atas perhatiannya kami sampaikan\nJazaakumullah khairan katsiran.{$footer}",
            ],
            [
                'nama' => 'Hormat singkat (belum jatuh tempo)',
                'kategori' => TagihanPesanKategori::BELUM_JATUH_TEMPO,
                'sort_order' => 3,
                'isi_pesan' => "Assalamualaikum Warahmatullahi wabarakatuh\nDengan hormat, kami dari {$instansi} menyampaikan bahwa ananda {nama_anak} masih memiliki tagihan sebesar {jumlah_tagihan} dengan rincian:\n{rincian}Terima kasih atas perhatian dan kerjasamanya. Wassalam 🙏.{$footer}",
            ],
            [
                'nama' => 'Lewat jatuh tempo — pengingat',
                'kategori' => TagihanPesanKategori::LEWAT_JATUH_TEMPO,
                'sort_order' => 1,
                'isi_pesan' => "Assalamualaikum Wr Wb,\nBada tahmid, tahlil dan shalawat bagi kita semua. Kami ingin menginformasikan kepada Bapak/Ibu, orang tua ananda {nama_anak}, bahwa masih terdapat tagihan jatuh tempo sebesar {jumlah_tagihan}.\nDengan rincian:\n{rincian}Mohon segera diselesaikan. Demikian pesan dari kami. Wassalam 🙏.{$footer}",
            ],
            [
                'nama' => 'Lewat jatuh tempo — tegas sopan',
                'kategori' => TagihanPesanKategori::LEWAT_JATUH_TEMPO,
                'sort_order' => 2,
                'isi_pesan' => "Assalamualaikum Warahmatullahi wabarakatuh\nKami dari keuangan {$instansi} mengingatkan kembali wali santri atas nama {nama_anak}.\nTerdapat tunggakan pembayaran jatuh tempo total {jumlah_tagihan}:\n{rincian}Mohon segera melunasi. Jazaakumullah khairan.{$footer}",
            ],
        ];

        foreach ($rows as $row) {
            TemplatePesanTagihan::withTrashed()->updateOrCreate(
                ['nama' => $row['nama']],
                [
                    ...$row,
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
