<?php

use App\Support\VirtualAccountNumber;
use Tests\TestCase;

uses(TestCase::class);

test('virtual account combines prefix and zero padded nis suffix', function () {
    config(['school.va_prefix' => '770000']);

    expect(VirtualAccountNumber::fromNis('12345'))->toBe('7700000000012345');
    expect(VirtualAccountNumber::fromNis('2025001234'))->toBe('7700002025001234');
    expect(VirtualAccountNumber::fromNis('512336041084210013'))->toBe('7700001084210013');
    expect(VirtualAccountNumber::fromNis('512336041084210044'))->toBe('7700001084210044');
    expect(strlen(VirtualAccountNumber::fromNis('1')))->toBe(16);
});

test('long nis up to max length is accepted', function () {
    $longest = '512336041084210044';

    expect(VirtualAccountNumber::isValidNis($longest))->toBeTrue()
        ->and(VirtualAccountNumber::requireValidNis($longest))->toBe($longest)
        ->and(VirtualAccountNumber::isValidNis(str_repeat('9', VirtualAccountNumber::NIS_MAX_LENGTH)))->toBeTrue()
        ->and(VirtualAccountNumber::isValidNis(str_repeat('9', VirtualAccountNumber::NIS_MAX_LENGTH + 1)))->toBeFalse();

    expect(fn () => VirtualAccountNumber::requireValidNis(str_repeat('1', 31)))
        ->toThrow(\InvalidArgumentException::class, 'NIS maksimal 30 digit');
});

test('nis can be decoded from virtual account number', function () {
    config(['school.va_prefix' => '770000']);

    expect(VirtualAccountNumber::nisFromVano('7700000000012345'))->toBe('12345');
    expect(VirtualAccountNumber::nisFromVano('7700002025001234'))->toBe('2025001234');
});

test('nis normalization strips non digits', function () {
    expect(VirtualAccountNumber::normalizeNis('12-34 5'))->toBe('12345');
});

test('nis suffix is zero padded last ten digits', function () {
    expect(VirtualAccountNumber::nisSuffix('12345'))->toBe('0000012345')
        ->and(VirtualAccountNumber::nisSuffix('512336041084210044'))->toBe('1084210044')
        ->and(VirtualAccountNumber::nisSuffix(null))->toBeNull();
});
