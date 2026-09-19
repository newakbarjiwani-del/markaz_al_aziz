<?php

use App\Support\KelasLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('kelas label builds short and long forms', function () {
    expect(KelasLabel::short('1', 'B'))->toBe('1B')
        ->and(KelasLabel::displayName('IX', '01-IBNU HAJAR'))->toBe('IX 01-IBNU HAJAR')
        ->and(KelasLabel::displayName('VIII', 'B'))->toBe('VIII B')
        ->and(KelasLabel::displayName('Tsaniyah', 'I'))->toBe('Tsaniyah I')
        ->and(KelasLabel::long('IX', '01-IBNU HAJAR'))->toBe('Kelas IX, Kelompok 01-IBNU HAJAR')
        ->and(KelasLabel::long('Kelompok', 'A'))->toBe('Kelompok A');
});

test('kelas label parses common class names', function () {
    expect(KelasLabel::parseClassName('1B'))->toBe(['kelas' => '1', 'kelompok' => 'B'])
        ->and(KelasLabel::parseClassName('VII A'))->toBe(['kelas' => 'VII', 'kelompok' => 'A'])
        ->and(KelasLabel::parseClassName('X IPA 1'))->toBe(['kelas' => 'X', 'kelompok' => 'IPA 1'])
        ->and(KelasLabel::parseClassName('Kelompok B'))->toBe(['kelas' => 'Kelompok', 'kelompok' => 'B'])
        ->and(KelasLabel::parseClassName('Kelompok C'))->toBe(['kelas' => 'Kelompok', 'kelompok' => 'C'])
        ->and(KelasLabel::parseClassName('Tsaniyah I'))->toBe(['kelas' => 'Tsaniyah', 'kelompok' => 'I'])
        ->and(KelasLabel::parseClassName('Aliyah II'))->toBe(['kelas' => 'Aliyah', 'kelompok' => 'II'])
        ->and(KelasLabel::parseClassName('Takhossus'))->toBe(['kelas' => 'Takhossus', 'kelompok' => null]);
});

test('kelas model syncs name from kelas and kelompok', function () {
    $sekolah = \App\Models\Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'is_active' => true,
    ]);

    $kelas = \App\Models\Kelas::create([
        'sekolah_id' => $sekolah->id,
        'kelas' => 'IX',
        'kelompok' => '01-IBNU HAJAR',
        'name' => 'placeholder',
        'is_active' => true,
    ]);

    expect($kelas->name)->toBe('IX 01-IBNU HAJAR')
        ->and($kelas->displayLabel())->toBe('Kelas IX, Kelompok 01-IBNU HAJAR');
});
