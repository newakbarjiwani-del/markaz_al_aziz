<?php

namespace App\Support;

use App\Models\OrangTua;
use App\Models\Siswa;

final class SiswaOrangTuaLink
{
    /**
     * Attach orang tua to siswa when not yet linked.
     * Each siswa may have only one orang tua record.
     *
     * @return array{ok: true}|array{ok: false, message: string}
     */
    public static function attach(Siswa $siswa, OrangTua $orangTua): array
    {
        $existing = $siswa->orangTua()->first();

        if ($existing && (int) $existing->id === (int) $orangTua->id) {
            return [
                'ok' => false,
                'message' => ActionMessage::withSubject(
                    'Orang tua sudah terhubung dengan siswa ini',
                    ActionMessage::orangTua($orangTua)
                ),
            ];
        }

        if ($existing) {
            return [
                'ok' => false,
                'message' => ActionMessage::withSubject(
                    'Siswa sudah terhubung dengan orang tua lain. Hapus keterkaitan yang ada terlebih dahulu sebelum menautkan ke orang tua berbeda',
                    ActionMessage::orangTua($existing).' · '.ActionMessage::siswa($siswa)
                ),
            ];
        }

        $siswa->orangTua()->attach($orangTua->id);

        return ['ok' => true];
    }
}
