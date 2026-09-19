<?php

use App\Support\PaymentMethod;

it('formats known kasir and online payment methods', function () {
    expect(PaymentMethod::label('1140000'))->toBe('Tunai')
        ->and(PaymentMethod::label('1140001'))->toBe('Manual BMI')
        ->and(PaymentMethod::label('1140002'))->toBe('Saldo Keuangan')
        ->and(PaymentMethod::label('1140003'))->toBe('Transfer Bank Lain')
        ->and(PaymentMethod::label('va'))->toBe('Virtual Account')
        ->and(PaymentMethod::label(null))->toBe('-')
        ->and(PaymentMethod::label(''))->toBe('-')
        ->and(PaymentMethod::label('bmi'))->toBe('BMI');
});
