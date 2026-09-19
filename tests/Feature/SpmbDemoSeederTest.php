<?php

use App\Models\SpmbBerita;
use App\Models\SpmbPengumuman;
use App\Models\SpmbPeriode;
use App\Models\TahunAkademik;
use Database\Seeders\SpmbDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('spmb demo seeder opens registration for 2027/2028', function () {
    $this->seed(SpmbDemoSeeder::class);

    $tahun = TahunAkademik::query()->where('name', SpmbDemoSeeder::TAHUN_AKADEMIK)->first();
    expect($tahun)->not->toBeNull();

    $periode = SpmbPeriode::query()->where('name', 'SPMB '.SpmbDemoSeeder::TAHUN_AKADEMIK)->first();
    expect($periode)->not->toBeNull()
        ->and($periode->is_active)->toBeTrue()
        ->and($periode->tahun_akademik_id)->toBe($tahun->id)
        ->and($periode->isOpen())->toBeTrue();

    expect(SpmbPengumuman::query()->published()->count())->toBeGreaterThanOrEqual(3)
        ->and(SpmbBerita::query()->published()->count())->toBeGreaterThanOrEqual(3);

    expect(SpmbPeriode::currentOpen()?->id)->toBe($periode->id);
});
