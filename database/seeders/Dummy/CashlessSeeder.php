<?php

namespace Database\Seeders\Dummy;

use App\Models\AlokasiUangSaku;
use App\Models\Dompet;
use App\Models\LimitCashless;
use App\Models\MenuKantin;
use App\Models\PengaturanCashless;
use App\Models\PengajuanUangSaku;
use App\Models\SccttranCashless;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SmTopup;
use App\Models\TransaksiCashless;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashlessSeeder extends Seeder
{
    /** @var int[] */
    private const SOLID_AMOUNTS = [150000, 200000, 300000];

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $menus = [
            ['name' => 'Nasi Goreng', 'category' => 'makanan', 'price' => 15000],
            ['name' => 'Mie Ayam', 'category' => 'makanan', 'price' => 12000],
            ['name' => 'Bakso', 'category' => 'makanan', 'price' => 15000],
            ['name' => 'Es Teh', 'category' => 'minuman', 'price' => 5000],
            ['name' => 'Snack', 'category' => 'snack', 'price' => 8000],
            ['name' => 'Roti Bakar', 'category' => 'snack', 'price' => 10000],
        ];

        foreach (Sekolah::query()->orderBy('id')->cursor() as $sekolah) {
            PengaturanCashless::query()->firstOrCreate(
                ['sekolah_id' => $sekolah->id],
                [
                    'daily_transaction_limit' => 50000,
                    'min_topup' => 10000,
                    'allow_transfer' => true,
                ]
            );

            foreach ($menus as $menu) {
                MenuKantin::query()->firstOrCreate(
                    [
                        'sekolah_id' => $sekolah->id,
                        'name' => $menu['name'],
                    ],
                    [
                        'price' => $menu['price'],
                        'category' => $menu['category'],
                        'is_active' => true,
                    ]
                );
            }

            LimitCashless::query()->firstOrCreate(
                [
                    'sekolah_id' => $sekolah->id,
                    'type' => 'sekolah',
                    'target' => 'Semua Siswa',
                    'category' => 'makanan',
                ],
                [
                    'daily_limit' => 50000,
                    'monthly_limit' => 500000,
                ]
            );

            // Wallet sample only — kantin BELANJA with operator user_id is seeded in KantinOmzetSeeder.
            Siswa::query()
                ->where('sekolah_id', $sekolah->id)
                ->orderBy('id')
                ->limit(12)
                ->eachById(function (Siswa $siswa): void {
                    $amountTopup = self::SOLID_AMOUNTS[$siswa->id % 3];
                    $amountBelanja = min(self::SOLID_AMOUNTS[($siswa->id + 2) % 3] / 10, $amountTopup);

                    $this->seedCashlessLedger(
                        siswa: $siswa,
                        kredit: $amountTopup,
                        debet: 0,
                        metode: 'TOP UP',
                        deskripsi: 'Top up cashless',
                        type: 'topup',
                        category: 'manual',
                        trxDate: now()->subDays(9),
                    );

                    $this->seedCashlessLedger(
                        siswa: $siswa,
                        kredit: 0,
                        debet: (int) $amountBelanja,
                        metode: 'BELANJA',
                        deskripsi: 'Belanja kantin (sample)',
                        type: 'belanja',
                        category: 'kantin',
                        trxDate: now()->subDays(2),
                        wallet: 'us',
                    );

                    Dompet::query()->updateOrCreate(
                        ['siswa_id' => $siswa->id],
                        [
                            'saldo_us' => max(0, $amountTopup - (int) $amountBelanja),
                            'saldo_kantin' => 0,
                            'saldo_tabungan' => 0,
                        ]
                    );

                    AlokasiUangSaku::query()->firstOrCreate(
                        [
                            'siswa_id' => $siswa->id,
                            'period' => now()->format('Y-m'),
                        ],
                        [
                            'amount' => 200000,
                            'status' => 'aktif',
                        ]
                    );

                    if ($siswa->id % 2 === 0) {
                        PengajuanUangSaku::query()->firstOrCreate(
                            [
                                'siswa_id' => $siswa->id,
                                'reason' => 'Kegiatan ekstrakurikuler',
                            ],
                            [
                                'amount' => 150000,
                                'status' => 'pending',
                            ]
                        );
                    }

                    unset($siswa);
                }, 25);

            gc_collect_cycles();
        }
    }

    private function seedCashlessLedger(
        Siswa $siswa,
        int $kredit,
        int $debet,
        string $metode,
        string $deskripsi,
        string $type,
        string $category,
        Carbon $trxDate,
        ?string $wallet = null,
    ): void {
        $suffix = $kredit > 0 ? 'C' : 'D';
        $walletKey = $wallet ? strtoupper(substr($wallet, 0, 1)) : 'X';
        $reference = sprintf('CS%06d%s%s', $siswa->id, $walletKey, $suffix);
        $amount = $kredit > 0 ? $kredit : $debet;

        SccttranCashless::query()->create([
            'CUSTID' => $siswa->id,
            'METODE' => $metode,
            'wallet' => $wallet,
            'TRXDATE' => $trxDate,
            'NOREFF' => $reference,
            'FIDBANK' => null,
            'KDCHANNEL' => null,
            'KREDIT' => $kredit,
            'DEBET' => $debet,
            'description' => $deskripsi,
        ]);

        if ($metode === 'TOP UP' && $kredit > 0) {
            SmTopup::query()->create([
                'CUSTID' => $siswa->id,
                'NOMINAL' => $kredit,
                'TRXDATE' => $trxDate,
            ]);
        }

        TransaksiCashless::query()->create([
            'siswa_id' => $siswa->id,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'wallet' => $wallet ?? 'us',
            'description' => $deskripsi,
            'created_at' => $trxDate,
            'updated_at' => $trxDate,
        ]);
    }
}
