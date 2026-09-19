<?php

namespace App\Support;

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;

final class ActionMessage
{
    public static function siswa(?Siswa $siswa, bool $withKelas = true): string
    {
        if ($siswa === null) {
            return 'Siswa tidak ditemukan';
        }

        if ($withKelas) {
            $siswa->loadMissing('kelas');
        }

        return implode(' · ', array_filter([
            $siswa->name,
            filled($siswa->nis) ? 'NIS '.$siswa->nis : null,
            $withKelas ? $siswa->kelas?->name : null,
        ], fn (?string $value) => filled($value)));
    }

    public static function guru(Guru $guru): string
    {
        return implode(' · ', array_filter([
            $guru->name,
            filled($guru->nip) ? 'NIP '.$guru->nip : null,
            $guru->jabatan,
        ], fn (?string $value) => filled($value)));
    }

    public static function orangTua(OrangTua $orangTua): string
    {
        return $orangTua->displayName();
    }

    public static function user(User $user): string
    {
        return implode(' · ', array_filter([
            $user->name,
            $user->username,
        ], fn (?string $value) => filled($value)));
    }

    public static function tagihan(Tagihan $tagihan): string
    {
        $tagihan->loadMissing('siswa.kelas');

        $parts = array_filter([
            $tagihan->siswa ? self::siswa($tagihan->siswa) : null,
            $tagihan->jenis,
            $tagihan->displayPeriode() !== '-' ? $tagihan->displayPeriode() : null,
        ], fn (?string $value) => filled($value));

        return implode(' · ', $parts);
    }

    public static function withSubject(string $action, string $subject): string
    {
        $action = rtrim($action, '. ');
        $actionHtml = self::highlightVerbs(e($action));

        return filled($subject)
            ? "{$actionHtml}: ".e($subject).'.'
            : "{$actionHtml}.";
    }

    public static function highlightVerbs(string $text): string
    {
        return preg_replace(
            '/\b(berhasil\s+)?(di\w+)\b/iu',
            '$1<span class="toast-message__verb">$2</span>',
            $text,
        ) ?? $text;
    }

    public static function rupiah(float|int $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    public static function wallet(string $wallet): string
    {
        return match ($wallet) {
            'kantin' => 'dompet kantin',
            'tabungan' => 'tabungan',
            default => 'uang saku',
        };
    }
}
