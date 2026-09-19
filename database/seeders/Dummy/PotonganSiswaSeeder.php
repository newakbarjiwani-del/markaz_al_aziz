<?php

namespace Database\Seeders\Dummy;

use App\Models\JenisPotongan;
use App\Models\JenisTagihan;
use App\Models\PotonganSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\Finance\PotonganTagihanService;
use App\Support\PotonganSiswaStatus;
use App\Support\PotonganTipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PotonganSiswaSeeder extends Seeder
{
    private const DEMO_KETERANGAN_PREFIX = 'Demo seed:';

    /** Example assignments per sekolah (first N finance students). */
    private const DEMO_STUDENTS_PER_SCHOOL = 4;

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $beasiswa = JenisPotongan::query()->where('nama', 'Beasiswa')->first();
        $kurangMampu = JenisPotongan::query()->where('nama', 'Kurang Mampu')->first();

        if ($beasiswa === null || $kurangMampu === null) {
            return;
        }

        $jenisTagihan = JenisTagihan::query()->active()->orderBy('sort_order')->get()->keyBy('name');
        $sppJenis = $jenisTagihan->get('SPP');
        $seragamJenis = $jenisTagihan->get('Seragam');
        $service = app(PotonganTagihanService::class);

        foreach (Sekolah::query()->orderBy('id')->cursor() as $sekolah) {
            $students = Siswa::query()
                ->where('sekolah_id', $sekolah->id)
                ->orderBy('id')
                ->limit(min(
                    self::DEMO_STUDENTS_PER_SCHOOL,
                    DummySchoolCatalog::FINANCE_STUDENTS_PER_SCHOOL ?: self::DEMO_STUDENTS_PER_SCHOOL,
                ))
                ->get();

            foreach ($students as $index => $siswa) {
                $this->seedDemoAssignments(
                    $siswa,
                    (int) $index,
                    $beasiswa,
                    $kurangMampu,
                    $sppJenis,
                    $seragamJenis,
                    $jenisTagihan,
                );
            }

            DB::transaction(function () use ($students, $service): void {
                Tagihan::query()
                    ->whereIn('siswa_id', $students->pluck('id'))
                    ->where('status', Tagihan::STATUS_UNPAID)
                    ->where('paid', 0)
                    ->whereNull('parent_id')
                    ->whereDoesntHave('potonganPemakaian')
                    ->orderBy('id')
                    ->eachById(function (Tagihan $tagihan) use ($service): void {
                        $service->autoApply($tagihan->fresh(['siswa']));
                    }, 50);
            });

            unset($students);
            gc_collect_cycles();
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, JenisTagihan>  $jenisTagihan
     */
    private function seedDemoAssignments(
        Siswa $siswa,
        int $index,
        JenisPotongan $beasiswa,
        JenisPotongan $kurangMampu,
        ?JenisTagihan $sppJenis,
        ?JenisTagihan $seragamJenis,
        $jenisTagihan,
    ): void {
        match ($index % self::DEMO_STUDENTS_PER_SCHOOL) {
            0 => $this->ensureAssignment(
                $siswa,
                $beasiswa,
                PotonganTipe::PERCENT,
                50,
                'beasiswa 50% semua jenis',
                $this->sameCutForAll($jenisTagihan, PotonganTipe::PERCENT, 50),
            ),
            1 => $this->ensureAssignment(
                $siswa,
                $kurangMampu,
                PotonganTipe::FIXED,
                100_000,
                'kurang mampu Rp 100.000 semua jenis',
                $this->sameCutForAll($jenisTagihan, PotonganTipe::FIXED, 100_000),
            ),
            2 => $this->seedStackedAssignments($siswa, $beasiswa, $kurangMampu, $jenisTagihan),
            default => $this->ensureAssignment(
                $siswa,
                $beasiswa,
                PotonganTipe::PERCENT,
                50,
                'beasiswa variasi per jenis tagihan',
                $this->perBillCuts($sppJenis, $seragamJenis),
            ),
        };
    }

    /**
     * Empty sync = berlaku semua jenis tagihan (nilai default dari baris potongan_siswa).
     *
     * @return array<int, array{tipe: string|null, nilai: int|null, max_pemakaian: int|null}>
     */
    private function sameCutForAll($jenisTagihan, string $tipe, int $nilai): array
    {
        return [];
    }

    /**
     * @return array<int, array{tipe: string, nilai: int, max_pemakaian: int|null}>
     */
    private function perBillCuts(?JenisTagihan $sppJenis, ?JenisTagihan $seragamJenis): array
    {
        $sync = [];

        if ($sppJenis !== null) {
            $sync[$sppJenis->id] = [
                'tipe' => PotonganTipe::PERCENT,
                'nilai' => 50,
                'max_pemakaian' => null,
            ];
        }

        if ($seragamJenis !== null) {
            $sync[$seragamJenis->id] = [
                'tipe' => PotonganTipe::FIXED,
                'nilai' => 75_000,
                'max_pemakaian' => 3,
            ];
        }

        return $sync;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, JenisTagihan>  $jenisTagihan
     */
    private function seedStackedAssignments(
        Siswa $siswa,
        JenisPotongan $beasiswa,
        JenisPotongan $kurangMampu,
        $jenisTagihan,
    ): void {
        $this->ensureAssignment(
            $siswa,
            $beasiswa,
            PotonganTipe::PERCENT,
            50,
            'stack beasiswa 50%',
            $this->sameCutForAll($jenisTagihan, PotonganTipe::PERCENT, 50),
        );
        $this->ensureAssignment(
            $siswa,
            $kurangMampu,
            PotonganTipe::FIXED,
            100_000,
            'stack kurang mampu Rp 100.000',
            $this->sameCutForAll($jenisTagihan, PotonganTipe::FIXED, 100_000),
        );
    }

    /**
     * @param  array<int, array{tipe: string, nilai: int}>  $billCuts
     */
    private function ensureAssignment(
        Siswa $siswa,
        JenisPotongan $jenis,
        string $tipe,
        int $nilai,
        string $label,
        array $billCuts,
    ): PotonganSiswa {
        $keterangan = self::DEMO_KETERANGAN_PREFIX.' '.$label;

        $existing = PotonganSiswa::query()
            ->where('siswa_id', $siswa->id)
            ->where('jenis_potongan_id', $jenis->id)
            ->where('keterangan', $keterangan)
            ->first();

        if ($existing !== null) {
            $existing->jenisTagihan()->sync($billCuts);

            return $existing;
        }

        $assignment = PotonganSiswa::create([
            'sekolah_id' => $siswa->sekolah_id,
            'siswa_id' => $siswa->id,
            'jenis_potongan_id' => $jenis->id,
            'tipe' => PotonganTipe::normalize($tipe),
            'nilai' => $nilai,
            'berlaku_mulai' => now()->subMonth()->toDateString(),
            'berlaku_sampai' => now()->addYear()->toDateString(),
            'max_pemakaian' => 12,
            'status' => PotonganSiswaStatus::ACTIVE,
            'keterangan' => $keterangan,
        ]);

        $assignment->jenisTagihan()->sync($billCuts);

        return $assignment;
    }
}
