<?php

use App\Models\Kelas;
use App\Models\Siswa;
use App\Support\ActionMessage;
use Tests\TestCase;

uses(TestCase::class);

test('siswa detail includes name nis and class', function () {
    $siswa = new Siswa([
        'nis' => '1000001',
        'name' => 'Ahmad Siswa',
    ]);
    $siswa->setRelation('kelas', new Kelas(['name' => 'X IPA 1']));

    expect(ActionMessage::siswa($siswa))
        ->toBe('Ahmad Siswa · NIS 1000001 · X IPA 1')
        ->and(ActionMessage::withSubject('Siswa berhasil diperbarui', ActionMessage::siswa($siswa)))
        ->toBe('Siswa berhasil <span class="toast-message__verb">diperbarui</span>: Ahmad Siswa · NIS 1000001 · X IPA 1.');
});

test('withSubject highlights action verbs for toast messages', function () {
    $siswa = new Siswa([
        'nis' => '512336041084200004',
        'name' => 'ADILLA QUINSHA AZAHRA',
    ]);
    $siswa->setRelation('kelas', new Kelas(['name' => 'XI 20-AL MAGFIROH']));

    expect(ActionMessage::withSubject('RFID cashless berhasil diaktifkan kembali', ActionMessage::siswa($siswa)))
        ->toBe('RFID cashless berhasil <span class="toast-message__verb">diaktifkan</span> kembali: ADILLA QUINSHA AZAHRA · NIS 512336041084200004 · XI 20-AL MAGFIROH.');
});

test('guru detail includes name nip and jabatan', function () {
    $guru = new \App\Models\Guru([
        'nip' => '198501012010',
        'name' => 'Ustadz Ahmad',
        'jabatan' => 'Wali Kelas',
    ]);

    expect(ActionMessage::guru($guru))
        ->toBe('Ustadz Ahmad · NIP 198501012010 · Wali Kelas');
});

test('siswa detail handles missing siswa gracefully', function () {
    expect(ActionMessage::siswa(null))->toBe('Siswa tidak ditemukan');
});
