<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Rfid;
use App\Models\Scopes\OperatorSekolahScope;
use App\Models\Siswa;
use App\Support\RfidUid;

class RfidResolver
{
    public function findByUid(string $uid): ?Rfid
    {
        $uid = RfidUid::sanitize($uid);

        return $uid === null
            ? null
            : Rfid::query()
                ->with([
                    'siswa' => fn ($query) => $query->withoutGlobalScope(OperatorSekolahScope::class),
                    'guru' => fn ($query) => $query->withoutGlobalScope(OperatorSekolahScope::class),
                ])
                ->where('uid', $uid)
                ->first();
    }

    public function findSiswa(string $uid, bool $activeOnly = true): ?Siswa
    {
        $uid = RfidUid::sanitize($uid);
        if ($uid === null) {
            return null;
        }

        return Siswa::query()
            ->withoutGlobalScope(OperatorSekolahScope::class)
            ->with('rfid')
            ->whereHas('rfid', fn ($query) => $query->where('uid', $uid))
            ->when($activeOnly, fn ($query) => $query->where('status', Siswa::STATUS_ACTIVE))
            ->first();
    }

    public function findGuru(string $uid, bool $activeOnly = true): ?Guru
    {
        $uid = RfidUid::sanitize($uid);
        if ($uid === null) {
            return null;
        }

        return Guru::query()
            ->withoutGlobalScope(OperatorSekolahScope::class)
            ->with('rfid')
            ->whereHas('rfid', fn ($query) => $query->where('uid', $uid))
            ->when($activeOnly, fn ($query) => $query->where('status', 'aktif'))
            ->first();
    }

    /**
     * @return array{type: 'siswa'|'guru', rfid: Rfid, model: Siswa|Guru}|null
     */
    public function resolveHolder(string $uid): ?array
    {
        $rfid = $this->findByUid($uid);
        if (! $rfid) {
            return null;
        }

        if ($rfid->siswa) {
            return ['type' => 'siswa', 'rfid' => $rfid, 'model' => $rfid->siswa];
        }

        if ($rfid->guru) {
            return ['type' => 'guru', 'rfid' => $rfid, 'model' => $rfid->guru];
        }

        return null;
    }
}
