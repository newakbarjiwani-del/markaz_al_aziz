<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\CreateGuruAccountRequest;
use App\Http\Requests\Teacher\ResetGuruAccountPasswordRequest;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesProfilePhotoUpload;
use App\Models\Guru;
use App\Models\KartuGuru;
use App\Models\ProfilGuru;
use App\Models\User;
use App\Services\PortalUserProvisioner;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\GuruSekolahFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DataGuruController extends Controller
{
    use DataTableTrait;
    use HandlesProfilePhotoUpload;

    public function index(): View
    {
        return view('admin.manajemen-guru.data-guru', [
            'title' => 'Data Guru',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function show(Guru $guru): View
    {
        $this->authorize('teachers.view');

        $guru->load(['sekolah', 'profil', 'portalUser', 'kartuAktif', 'riwayatMengajar']);

        return view('admin.manajemen-guru.show-guru', [
            'title' => 'Detail Guru',
            'guru' => $guru,
            'hasAccount' => $guru->portalUser !== null,
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Guru::query()
            ->leftJoin('rfid', 'rfid.guru_id', '=', 'guru.id')
            ->select('guru.*')
            ->with(['profil', 'portalUser', 'sekolah', 'rfid'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        GuruSekolahFilter::applyListFilter(
            $query,
            $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null
        );

        return $this->datatableResponse($request, $query, [
            'searchable' => ['guru.nip', 'guru.name', 'guru.jabatan', 'guru.status', 'rfid.uid'],
            'orderable' => ['guru.nip', 'guru.name', 'guru.jabatan', 'guru.status', 'rfid.uid', 'guru.created_at'],
        ], function (Guru $guru) {
            $user = $guru->portalUser;

            return [
                $guru->nip,
                $guru->name,
                $guru->sekolah?->name ?? 'Lintas sekolah',
                $guru->jabatan ?? '-',
                $guru->jenis_guru ?? '-',
                $this->cell(view('admin.manajemen-guru.partials.rfid-cell', ['guru' => $guru])->render(), $guru->rfidUid() ?? '-', 'text'),
                ucfirst($guru->status),
                $this->cell(view('admin.manajemen-guru.partials.account-cell', ['guru' => $guru])->render(), null, 'text'),
                $this->cell(view('components.teacher-row-actions', [
                    'guru' => $guru,
                    'hasAccount' => $user !== null,
                    'fields' => array_merge(
                        $guru->only(['nip', 'name', 'jabatan', 'jenis_guru', 'golongan', 'phone', 'status', 'sekolah_id']),
                        [
                            'rfid_uid' => $guru->rfidUid(),
                            'photo_url' => $guru->profil?->photoUrl(),
                        ]
                    ),
                    'editUrl' => route('admin.manajemen-guru.data-guru.update', $guru),
                    'deleteUrl' => route('admin.manajemen-guru.data-guru.destroy', $guru),
                    'accountUrl' => route('admin.manajemen-guru.data-guru.create-account', $guru),
                    'accountShowUrl' => route('admin.manajemen-guru.data-guru.account.show', $guru),
                    'accountResetUrl' => route('admin.manajemen-guru.data-guru.account.reset-password', $guru),
                    'formTarget' => 'teacher-form',
                    'modalTarget' => 'teacher-modal',
                ])->render(), null, 'action'),
            ];
        });
    }

    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $data = $request->safe()->except('photo');
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $rfidUid = $data['rfid_uid'] ?? null;
        unset($data['rfid_uid']);

        $guru = DB::transaction(function () use ($request, $data, $rfidUid) {
            $guru = Guru::create($data);
            $guru->assignRfid($rfidUid);

            if ($request->hasFile('photo')) {
                $profil = ProfilGuru::firstOrCreate(['guru_id' => $guru->id]);
                $profil->update([
                    'photo_path' => $this->storeProfilePhoto($request->file('photo'), 'photos/guru'),
                ]);
            }

            ProfilGuru::firstOrCreate(['guru_id' => $guru->id]);
            KartuGuru::provisionFor($guru);

            return $guru;
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Guru berhasil ditambahkan', ActionMessage::guru($guru)),
            $guru,
            201
        );
    }

    public function update(UpdateTeacherRequest $request, Guru $guru): JsonResponse
    {
        $data = $request->safe()->except('photo');
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $shouldSyncRfid = array_key_exists('rfid_uid', $data);
        $rfidUid = $shouldSyncRfid ? $data['rfid_uid'] : $guru->rfidUid();
        unset($data['rfid_uid']);

        DB::transaction(function () use ($guru, $data, $rfidUid, $shouldSyncRfid): void {
            $guru->update($data);
            if ($shouldSyncRfid) {
                $guru->assignRfid($rfidUid);
            }
        });

        if ($request->hasFile('photo')) {
            $profil = ProfilGuru::firstOrCreate(['guru_id' => $guru->id]);
            $profil->update([
                'photo_path' => $this->replaceProfilePhoto(
                    $request->file('photo'),
                    'photos/guru',
                    $profil->photo_path
                ),
            ]);
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject('Guru berhasil diperbarui', ActionMessage::guru($guru)),
            $guru
        );
    }

    public function createAccount(CreateGuruAccountRequest $request, Guru $guru): JsonResponse
    {
        $result = PortalUserProvisioner::forGuru($guru, $request->validated('password'));
        $user = $result['user'];

        if (! $result['created']) {
            return $this->jsonSuccess(
                ActionMessage::withSubject('Akun login guru sudah tersedia', ActionMessage::guru($guru)),
                $this->accountPayload($user)
            );
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject('Akun login guru berhasil dibuat', ActionMessage::guru($guru)),
            array_merge(
                $this->accountPayload($user),
                ['created' => true]
            ),
            201
        );
    }

    public function showAccount(Guru $guru): JsonResponse
    {
        $this->authorize('teachers.view');

        $user = $guru->portalUser;
        if (! $user) {
            return $this->jsonError('Guru ini belum memiliki akun login.', null, 404);
        }

        return $this->jsonSuccess('OK', $this->accountPayload($user));
    }

    public function resetAccountPassword(ResetGuruAccountPasswordRequest $request, Guru $guru): JsonResponse
    {
        if (! $guru->portalUser) {
            return $this->jsonError('Guru ini belum memiliki akun login.', null, 404);
        }

        $result = PortalUserProvisioner::resetGuruPassword($guru, $request->validated('password'));

        return $this->jsonSuccess(
            ActionMessage::withSubject('Password guru berhasil diperbarui', ActionMessage::guru($guru)),
            array_merge(
                $this->accountPayload($result['user']),
                ['reset' => true]
            )
        );
    }

    /** @return array<string, mixed> */
    private function accountPayload(User $user): array
    {
        return [
            'created' => false,
            'username' => $user->username,
            'email' => $user->email,
            'status' => $user->statusLabel(),
        ];
    }

    public function destroy(Guru $guru): JsonResponse
    {
        $detail = ActionMessage::guru($guru);
        $this->authorize('teachers.delete');
        $guru->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Guru berhasil dihapus', $detail));
    }
}
