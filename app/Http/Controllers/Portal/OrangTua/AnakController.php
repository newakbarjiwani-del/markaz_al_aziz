<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\ProfilSiswa;
use App\Models\Siswa;
use App\Services\Finance\SccttranCashlessService;
use App\Services\Finance\SccttranSaldoService;
use App\Services\StudentEditLogger;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AnakController extends Controller
{
    use PortalAccess;

    public function __construct(
        private readonly SccttranCashlessService $cashlessService,
        private readonly SccttranSaldoService $saldoService,
        private readonly StudentEditLogger $editLogger,
    ) {}

    public function index(): View
    {
        $children = $this->ortuChildren()->load(['profil', 'kelas']);
        $childIds = $children->pluck('id')->all();
        $financeMap = $this->saldoService->balancesForSiswaIds($childIds);
        $cashlessMap = $this->cashlessService->balancesForSiswaIds($childIds);

        $children->each(function ($child) use ($financeMap, $cashlessMap): void {
            $child->setAttribute('saldo_cashless', $cashlessMap[$child->id] ?? 0);
            $child->setAttribute('saldo_keuangan', $financeMap[$child->id] ?? 0);
        });

        return view('portal.ortu.anak', [
            'title' => 'Data Anak',
            'children' => $children,
        ]);
    }

    public function update(Request $request, Siswa $siswa): JsonResponse
    {
        $this->ensureOrtuChild($siswa);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:L,P'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'golongan_darah' => ['nullable', 'string', 'max:10', 'in:A,B,AB,O,A+,B+,AB+,O+,A-,B-,AB-,O-'],
        ]);

        // Capture old values for logging
        $siswaOld = [
            'name' => $siswa->name,
            'gender' => $siswa->gender,
            'birth_date' => $siswa->birth_date,
            'birth_place' => $siswa->birth_place,
            'address' => $siswa->address,
        ];

        $profil = ProfilSiswa::firstOrCreate(['siswa_id' => $siswa->id]);
        $extra = Arr::wrap($profil->extra_fields ?? []);

        $extraOld = [
            'nama_panggilan' => $extra['nama_panggilan'] ?? null,
            'golongan_darah' => $extra['golongan_darah'] ?? null,
        ];

        // Update siswa fields
        $siswa->fill(Arr::only($data, ['name', 'gender', 'birth_date', 'birth_place', 'address']));
        $siswa->save();

        // Update extra fields
        foreach (['nama_panggilan', 'golongan_darah'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = trim((string) ($data[$key] ?? ''));
            if ($value === '') {
                unset($extra[$key]);
            } else {
                $extra[$key] = $value;
            }
        }

        $profil->fill(['extra_fields' => $extra])->save();

        // Log changes
        $siswaNew = [
            'name' => $siswa->name,
            'gender' => $siswa->gender,
            'birth_date' => $siswa->birth_date,
            'birth_place' => $siswa->birth_place,
            'address' => $siswa->address,
        ];

        $extraNew = [
            'nama_panggilan' => $extra['nama_panggilan'] ?? null,
            'golongan_darah' => $extra['golongan_darah'] ?? null,
        ];

        $this->editLogger->logChanges($request, $siswa, array_merge($siswaOld, $extraOld), array_merge($siswaNew, $extraNew));

        return $this->jsonSuccess(
            ActionMessage::withSubject('Profil anak berhasil diperbarui', ActionMessage::siswa($siswa)),
            [
                'siswa' => $siswa->fresh(['kelas', 'profil']),
            ]
        );
    }

    private function ensureOrtuChild(Siswa $siswa): void
    {
        abort_unless(in_array($siswa->id, $this->ortuChildIds(), true), 404);
    }

    private function jsonSuccess(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
