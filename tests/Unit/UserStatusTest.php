<?php

use App\Support\UserStatus;

test('user status labels and normalize', function () {
    expect(UserStatus::label(UserStatus::ACTIVE))->toBe('Aktif')
        ->and(UserStatus::label(UserStatus::DISABLED))->toBe('Nonaktif')
        ->and(UserStatus::normalize('aktif'))->toBe(UserStatus::ACTIVE)
        ->and(UserStatus::normalize('nonaktif'))->toBe(UserStatus::DISABLED)
        ->and(UserStatus::normalize('disabled'))->toBe(UserStatus::DISABLED)
        ->and(UserStatus::normalize('blocked'))->toBe(UserStatus::DISABLED)
        ->and(UserStatus::normalize('1'))->toBe(UserStatus::ACTIVE)
        ->and(UserStatus::normalize(0))->toBe(UserStatus::DISABLED)
        ->and(UserStatus::canLogin(UserStatus::ACTIVE))->toBeTrue()
        ->and(UserStatus::canLogin(UserStatus::DISABLED))->toBeFalse();
});
