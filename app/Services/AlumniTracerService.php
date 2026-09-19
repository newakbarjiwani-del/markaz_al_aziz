<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AlumniTracer;
use Illuminate\Support\Facades\DB;

class AlumniTracerService
{
    /**
     * @param  array{
     *     alumni_id?: int|null,
     *     sekolah_id?: int|null,
     *     name: string,
     *     nis?: string|null,
     *     angkatan?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     address?: string|null,
     *     tahun_tracer: string,
     *     status_lulusan: string,
     *     institusi?: string|null,
     *     jabatan?: string|null,
     *     bidang?: string|null,
     *     kota?: string|null,
     *     catatan?: string|null,
     *     source?: string,
     * }  $payload
     */
    public function submit(array $payload): AlumniTracer
    {
        return DB::transaction(function () use ($payload) {
            $alumni = $this->resolveAlumni($payload);

            $alumni->fill([
                'name' => $payload['name'],
                'phone' => $payload['phone'] ?? $alumni->phone,
                'email' => $payload['email'] ?? $alumni->email,
                'address' => $payload['address'] ?? $alumni->address,
                'angkatan' => $payload['angkatan'] ?? $alumni->angkatan,
                'nis' => $payload['nis'] ?? $alumni->nis,
                'sekolah_id' => $payload['sekolah_id'] ?? $alumni->sekolah_id,
                'is_active' => true,
            ])->save();

            return AlumniTracer::query()->updateOrCreate(
                [
                    'alumni_id' => $alumni->id,
                    'tahun_tracer' => $payload['tahun_tracer'],
                ],
                [
                    'status_lulusan' => $payload['status_lulusan'],
                    'institusi' => $payload['institusi'] ?? null,
                    'jabatan' => $payload['jabatan'] ?? null,
                    'bidang' => $payload['bidang'] ?? null,
                    'kota' => $payload['kota'] ?? null,
                    'catatan' => $payload['catatan'] ?? null,
                    'submitted_at' => now(),
                    'source' => $payload['source'] ?? AlumniTracer::SOURCE_ADMIN,
                ],
            )->load('alumni');
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveAlumni(array $payload): Alumni
    {
        if (! empty($payload['alumni_id'])) {
            return Alumni::query()->findOrFail((int) $payload['alumni_id']);
        }

        $query = Alumni::query();

        if (! empty($payload['nis'])) {
            $match = (clone $query)
                ->when(
                    ! empty($payload['sekolah_id']),
                    fn ($q) => $q->where('sekolah_id', $payload['sekolah_id']),
                )
                ->where('nis', $payload['nis'])
                ->first();

            if ($match) {
                return $match;
            }
        }

        if (! empty($payload['email'])) {
            $match = (clone $query)
                ->when(
                    ! empty($payload['sekolah_id']),
                    fn ($q) => $q->where('sekolah_id', $payload['sekolah_id']),
                )
                ->where('email', $payload['email'])
                ->first();

            if ($match) {
                return $match;
            }
        }

        return new Alumni;
    }
}
