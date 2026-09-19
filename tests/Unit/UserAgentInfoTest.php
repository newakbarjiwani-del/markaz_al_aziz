<?php

use App\Support\UserAgentInfo;

test('user agent info parses common desktop browser', function () {
    $info = UserAgentInfo::parse(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
    );

    expect($info->browser)->toBe('Chrome 125')
        ->and($info->platform)->toBe('Windows')
        ->and($info->device)->toBe('Desktop')
        ->and($info->summary())->toBe('Chrome 125 · Windows · Desktop');
});

test('user agent info parses mobile safari', function () {
    $info = UserAgentInfo::parse(
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
    );

    expect($info->browser)->toBe('Safari 17')
        ->and($info->platform)->toBe('iOS')
        ->and($info->device)->toBe('Mobile');
});

test('user agent info returns empty values for missing user agent', function () {
    $info = UserAgentInfo::parse(null);

    expect($info->browser)->toBeNull()
        ->and($info->platform)->toBeNull()
        ->and($info->device)->toBeNull()
        ->and($info->summary())->toBe('-');
});
