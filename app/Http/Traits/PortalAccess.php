<?php

namespace App\Http\Traits;

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Services\GuruTeachingScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait PortalAccess
{
    protected function linkedOrangTua(): OrangTua
    {
        $ortu = auth()->user()?->orangTua;
        abort_unless($ortu, 403, 'Akun orang tua tidak terhubung ke data wali.');

        return $ortu;
    }

    protected function ortuChildren(): Collection
    {
        return $this->linkedOrangTua()
            ->siswa()
            ->with(['kelas', 'dompet', 'profil'])
            ->where('status', \App\Models\Siswa::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
    }

    protected function ortuChildIds(): array
    {
        return $this->ortuChildren()->pluck('id')->all();
    }

    protected function selectedOrtuChildId(Request $request): ?int
    {
        $siswaId = $request->integer('siswa_id') ?: null;

        if ($siswaId && in_array($siswaId, $this->ortuChildIds(), true)) {
            return $siswaId;
        }

        return null;
    }

    protected function applyOrtuSiswaScope(Builder $query, Request $request, string $column = 'siswa_id'): void
    {
        $ids = $this->ortuChildIds();
        abort_if($ids === [], 403, 'Tidak ada data anak yang terhubung.');

        $selected = $this->selectedOrtuChildId($request);

        if ($selected) {
            $query->where($column, $selected);

            return;
        }

        $query->whereIn($column, $ids);
    }

    protected function applyOrtuNestedSiswaScope(Builder $query, Request $request, string $relation): void
    {
        $ids = $this->ortuChildIds();
        abort_if($ids === [], 403, 'Tidak ada data anak yang terhubung.');

        $selected = $this->selectedOrtuChildId($request);

        $query->whereHas($relation, function (Builder $q) use ($ids, $selected) {
            if ($selected) {
                $q->where('id', $selected);

                return;
            }

            $q->whereIn('id', $ids);
        });
    }

    protected function linkedSiswa(): Siswa
    {
        $siswa = auth()->user()?->siswa;
        abort_unless($siswa, 403, 'Akun siswa tidak terhubung ke data pelajar.');

        return $siswa->loadMissing(['kelas', 'dompet', 'profil']);
    }

    protected function linkedGuru(): Guru
    {
        $guru = auth()->user()?->guru;
        abort_unless($guru, 403, 'Akun guru tidak terhubung ke data pegawai.');

        return $guru;
    }

    protected function guruTeachingScope(): GuruTeachingScope
    {
        return GuruTeachingScope::for($this->linkedGuru());
    }

    /**
     * Labels of kelas / assignment the guru teaches via jadwal absen (multi-kelas aware).
     *
     * @return list<string>
     */
    protected function guruClassNames(): array
    {
        return $this->guruTeachingScope()->classLabels();
    }

    protected function applyGuruKelasScope(Builder $query, string $relation = 'siswa'): void
    {
        // Attendance is scoped by jadwal slot ownership (supports multi-kelas schedules).
        // $relation kept for call-site compatibility; student filter is via jadwalSlot.
        unset($relation);
        $this->guruTeachingScope()->applyAttendanceScope($query);
    }
}
