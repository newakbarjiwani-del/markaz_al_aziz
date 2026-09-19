<?php

namespace App\Support;

use App\Models\LogLogin;

class UserAgentInfo
{
    public function __construct(
        public readonly ?string $browser,
        public readonly ?string $platform,
        public readonly ?string $device,
    ) {}

    public static function forLog(LogLogin $log): self
    {
        if ($log->browser || $log->platform || $log->device) {
            return new self($log->browser, $log->platform, $log->device);
        }

        return self::parse($log->user_agent);
    }

    public static function parse(?string $userAgent): self
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return new self(null, null, null);
        }

        return new self(
            browser: self::detectBrowser($userAgent),
            platform: self::detectPlatform($userAgent),
            device: self::detectDevice($userAgent),
        );
    }

    public function summary(): string
    {
        $parts = array_values(array_filter([$this->browser, $this->platform, $this->device]));

        return $parts !== [] ? implode(' · ', $parts) : '-';
    }

    private static function detectBrowser(string $userAgent): ?string
    {
        if (preg_match('/Edg\/(\d+)/', $userAgent, $matches)) {
            return 'Edge '.$matches[1];
        }

        if (preg_match('/OPR\/(\d+)/', $userAgent, $matches)) {
            return 'Opera '.$matches[1];
        }

        if (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) {
            return 'Firefox '.$matches[1];
        }

        if (preg_match('/Chrome\/(\d+)/', $userAgent, $matches)) {
            return 'Chrome '.$matches[1];
        }

        if (preg_match('/Version\/(\d+).*Safari/', $userAgent, $matches)) {
            return 'Safari '.$matches[1];
        }

        if (preg_match('/Safari\/(\d+)/', $userAgent, $matches)) {
            return 'Safari '.$matches[1];
        }

        if (preg_match('/MSIE (\d+)/', $userAgent, $matches) || preg_match('/Trident\/.*rv:(\d+)/', $userAgent, $matches)) {
            return 'Internet Explorer '.$matches[1];
        }

        return null;
    }

    private static function detectPlatform(string $userAgent): ?string
    {
        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') || str_contains($userAgent, 'iPod')) {
            return 'iOS';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (str_contains($userAgent, 'Windows NT')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh')) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'CrOS')) {
            return 'ChromeOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        return null;
    }

    private static function detectDevice(string $userAgent): ?string
    {
        if (preg_match('/bot|crawl|spider|slurp|facebookexternalhit/i', $userAgent)) {
            return 'Bot';
        }

        if (str_contains($userAgent, 'iPad') || str_contains($userAgent, 'Tablet')) {
            return 'Tablet';
        }

        if (str_contains($userAgent, 'Mobile')
            || str_contains($userAgent, 'iPhone')
            || str_contains($userAgent, 'iPod')
            || (str_contains($userAgent, 'Android') && str_contains($userAgent, 'Mobile'))) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}
