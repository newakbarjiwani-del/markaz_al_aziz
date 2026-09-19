<?php

use App\Models\Siswa;
use App\Services\Finance\FinanceVaResolver;
use App\Support\VirtualAccountNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['school.va_prefix' => '797877']);
});

test('va resolver finds siswa by short nis', function () {
    $siswa = FinanceFixtures::schoolWithStudent([
        'siswa' => ['nis' => '12345', 'name' => 'Siswa Pendek'],
    ])->siswa;

    $vano = VirtualAccountNumber::fromNis('12345');

    expect(app(FinanceVaResolver::class)->resolveSiswa($vano)?->id)->toBe($siswa->id);
});

test('va resolver finds siswa when nis is longer than ten digits', function () {
    $fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => ['nis' => '512336041084210041', 'name' => 'Bayu Alfaiz'],
    ]);

    $vano = VirtualAccountNumber::fromNis($fixture->siswa->nis);

    expect($vano)->toBe('7978771084210041')
        ->and(VirtualAccountNumber::nisFromVano($vano))->toBe('1084210041')
        ->and(app(FinanceVaResolver::class)->resolveSiswa($vano)?->id)->toBe($fixture->siswa->id);
});

test('va resolver returns null when no siswa matches suffix', function () {
    FinanceFixtures::schoolWithStudent();

    expect(app(FinanceVaResolver::class)->resolveSiswa('7978771084210041'))->toBeNull();
});

test('va resolver returns null when multiple siswa share the same ten digit suffix', function () {
    $fixture = FinanceFixtures::schoolWithStudent();

    Siswa::create([
        'sekolah_id' => $fixture->sekolah->id,
        'kelas_id' => $fixture->kelas->id,
        'nis' => '90000001084210041',
        'name' => 'Collision A',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    Siswa::create([
        'sekolah_id' => $fixture->sekolah->id,
        'kelas_id' => $fixture->kelas->id,
        'nis' => '80000001084210041',
        'name' => 'Collision B',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    expect(app(FinanceVaResolver::class)->resolveSiswa('7978771084210041'))->toBeNull();
});

test('va suffix collision warning lists other siswa sharing the same suffix', function () {
    $fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => ['nis' => '90000001084210041', 'name' => 'Collision A'],
    ]);

    Siswa::create([
        'sekolah_id' => $fixture->sekolah->id,
        'kelas_id' => $fixture->kelas->id,
        'nis' => '80000001084210041',
        'name' => 'Collision B',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $resolver = app(FinanceVaResolver::class);
    $warning = $resolver->vaSuffixCollisionWarning('90000001084210041', $fixture->siswa->id);

    expect($warning)->toContain('1084210041')
        ->and($warning)->toContain('Collision B')
        ->and($warning)->toContain('80000001084210041')
        ->and($resolver->studentsSharingVaSuffix('90000001084210041', $fixture->siswa->id))->toHaveCount(1);
});

test('va suffix collision warning is null when suffix is unique', function () {
    FinanceFixtures::schoolWithStudent([
        'siswa' => ['nis' => '12345', 'name' => 'Unique'],
    ]);

    expect(app(FinanceVaResolver::class)->vaSuffixCollisionWarning('99999'))->toBeNull();
});
