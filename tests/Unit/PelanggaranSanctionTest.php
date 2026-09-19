<?php

use App\Support\PelanggaranSanction;

test('pelanggaran sanction labels', function () {
    expect(PelanggaranSanction::labels())->toBe([
        'SP1' => 'Surat Peringatan 1',
        'SP2' => 'Surat Peringatan 2',
        'SP3' => 'Surat Peringatan 3',
        'DO' => 'Drop Out (dikeluarkan)',
        'ganti rugi 10 x lipat' => 'Ganti rugi 10x lipat',
    ])
        ->and(PelanggaranSanction::label(PelanggaranSanction::SP1))->toBe('Surat Peringatan 1')
        ->and(PelanggaranSanction::label(PelanggaranSanction::DO))->toBe('Drop Out (dikeluarkan)')
        ->and(PelanggaranSanction::label(PelanggaranSanction::GANTI_RUGI))->toBe('Ganti rugi 10x lipat')
        ->and(PelanggaranSanction::label(null))->toBe('-');
});

test('pelanggaran sanction normalize', function () {
    expect(PelanggaranSanction::normalize('SP1'))->toBe(PelanggaranSanction::SP1)
        ->and(PelanggaranSanction::normalize('sp2'))->toBe(PelanggaranSanction::SP2)
        ->and(PelanggaranSanction::normalize(' SP3 '))->toBe(PelanggaranSanction::SP3)
        ->and(PelanggaranSanction::normalize('DO'))->toBe(PelanggaranSanction::DO)
        ->and(PelanggaranSanction::normalize('do'))->toBe(PelanggaranSanction::DO)
        ->and(PelanggaranSanction::normalize('drop out'))->toBe(PelanggaranSanction::DO)
        ->and(PelanggaranSanction::normalize('Ganti Rugi 10 X Lipat'))->toBe(PelanggaranSanction::GANTI_RUGI)
        ->and(PelanggaranSanction::normalize('denda'))->toBeNull()
        ->and(PelanggaranSanction::normalize(null))->toBeNull()
        ->and(PelanggaranSanction::normalize(''))->toBeNull();
});

test('pelanggaran sanction highest picks most severe', function () {
    expect(PelanggaranSanction::highest(['SP1', 'SP3', 'SP2']))->toBe(PelanggaranSanction::SP3)
        ->and(PelanggaranSanction::highest(['SP1', 'DO']))->toBe(PelanggaranSanction::DO)
        ->and(PelanggaranSanction::highest(['SP1', 'ganti rugi 10 x lipat']))->toBe(PelanggaranSanction::GANTI_RUGI)
        ->and(PelanggaranSanction::highest([]))->toBeNull();
});
