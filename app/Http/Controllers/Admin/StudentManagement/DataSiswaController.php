<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesFaceCapture;
use App\Http\Traits\HandlesProfilePhotoUpload;
use App\Models\Dompet;
use App\Models\Kamar;
use App\Models\KartuSiswa;
use App\Models\OrangTua;
use App\Models\PortalAccessToken;
use App\Models\ProfilSiswa;
use App\Models\SaldoKeuangan;
use App\Models\Siswa;
use App\Models\StatusSantri;
use App\Models\User;
use App\Services\Finance\FinanceVaResolver;
use App\Services\PortalAccessTokenService;
use App\Services\StudentEditLogger;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\PortalTokenActive;
use App\Support\SiswaOrangTuaLink;
use App\Support\SiswaStatus;
use App\Support\SoftDeleteRules;
use App\Support\VirtualAccountNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DataSiswaController extends Controller
{
    use DataTableTrait;
    use HandlesFaceCapture;
    use HandlesProfilePhotoUpload;

    public function index(): View
    {
        return view('admin.manajemen-siswa.data-siswa', [
            'title' => 'Data Siswa',
            'schools' => AdminSchoolScope::schools(),
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
            'kamarList' => Kamar::query()->active()->ordered()->get(['id', 'kode', 'nama', 'blok']),
            'statusSantriList' => StatusSantri::query()->active()->ordered()->get(['id', 'nama']),
        ]);
    }

    public function show(Siswa $siswa, FinanceVaResolver $vaResolver): View
    {
        $this->authorize('students.view');

        $siswa->load(['kelas', 'sekolah', 'kamar', 'statusSantri', 'profil', 'orangTua', 'dompet', 'saldoKeuangan', 'kartuAktif']);

        $portalAccess = app(PortalAccessTokenService::class);
        $siswaUser = User::query()->where('siswa_id', $siswa->id)->first();
        $ortu = $siswa->orangTua->first();
        $ortuUser = $ortu ? User::query()->where('orang_tua_id', $ortu->id)->first() : null;

        return view('admin.manajemen-siswa.show-siswa', [
            'title' => 'Detail Siswa',
            'siswa' => $siswa,
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
            'kamarList' => Kamar::query()->active()->ordered()->get(['id', 'kode', 'nama', 'blok']),
            'statusSantriList' => StatusSantri::query()->active()->ordered()->get(['id', 'nama']),
            'siswaPortalTokenActive' => $siswaUser && $portalAccess->activeToken($siswaUser, 'siswa') !== null,
            'ortuPortalTokenActive' => $ortuUser && $portalAccess->activeToken($ortuUser, 'orang_tua') !== null,
            'hasOrangTua' => $siswa->orangTua->isNotEmpty(),
            'vaSuffixWarning' => $vaResolver->vaSuffixCollisionWarning($siswa->nis, $siswa->id),
        ]);
    }

    public function checkVaSuffix(Request $request, FinanceVaResolver $vaResolver): JsonResponse
    {
        $this->authorize('students.view');

        $nis = VirtualAccountNumber::normalizeNis($request->query('nis'));
        $ignoreId = $request->filled('ignore_id') ? (int) $request->query('ignore_id') : null;

        if ($nis === null || ! VirtualAccountNumber::isValidNis($nis)) {
            return $this->jsonSuccess('OK', [
                'has_collision' => false,
                'warning' => null,
                'vano' => null,
                'collisions' => [],
            ]);
        }

        $others = $vaResolver->studentsSharingVaSuffix($nis, $ignoreId);

        return $this->jsonSuccess('OK', [
            'has_collision' => $others->isNotEmpty(),
            'warning' => $vaResolver->vaSuffixCollisionWarning($nis, $ignoreId),
            'vano' => VirtualAccountNumber::fromNis($nis),
            'collisions' => $others->take(10)->map(fn (Siswa $s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
            ])->values()->all(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Siswa::query()
            ->leftJoin('rfid', 'rfid.siswa_id', '=', 'siswa.id')
            ->select([
                'siswa.id', 'siswa.sekolah_id', 'siswa.kelas_id', 'siswa.nis', 'siswa.name',
                'siswa.gender', 'siswa.birth_date', 'siswa.address', 'siswa.status',
                'siswa.has_foto_wajah', 'siswa.daily_transaction_limit',
                'siswa.created_at', 'siswa.updated_at',
            ])
            ->selectSub(
                PortalAccessToken::activeExpiresSubquery('siswa_id', 'siswa', 'siswa'),
                '_portal_token_expires'
            )
            ->selectSub(
                PortalAccessToken::activeExpiresSubqueryForLinkedOrangTua(),
                '_portal_ortu_token_expires'
            )
            ->withExists(['orangTua as _has_orang_tua'])
            ->withExists(['pelanggaranSiswa as _has_pelanggaran' => fn ($q) => $q->activePoints()->where('point', '>', 0)])
            ->withExists(['prestasiSiswa as _has_prestasi'])
            ->with(['kelas', 'profil', 'rfid'])
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->sekolah_id))
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = SiswaStatus::normalize($request->input('status'));
                if ($status !== null) {
                    $q->where('status', $status);
                }
            })
            ->when($request->filled('kelas_id'), fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->gender))
            ->when($request->filled('has_foto_wajah'), function ($q) use ($request) {
                if ($request->has_foto_wajah === '1') {
                    $q->where('siswa.has_foto_wajah', 1);
                } elseif ($request->has_foto_wajah === '0') {
                    $q->where('siswa.has_foto_wajah', 0);
                }
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['siswa.nis', 'siswa.name', 'siswa.status', 'rfid.uid'],
            'orderable' => ['siswa.nis', 'siswa.name', 'siswa.status', 'rfid.uid', 'siswa.created_at'],
        ], function (Siswa $siswa) {
            $hasFotoWajah = $siswa->hasFotoWajah();
            $rfidHtml = view('admin.manajemen-siswa.partials.rfid-cell', ['siswa' => $siswa])->render();
            $faceHtml = view('admin.manajemen-siswa.partials.face-capture-cell', [
                'siswa' => $siswa,
                'hasFotoWajah' => $hasFotoWajah,
            ])->render();
            $tokenHtml = view('admin.manajemen-siswa.partials.portal-token-cell', [
                'expiresAt' => $siswa->_portal_token_expires,
            ])->render();
            $nameHtml = view('admin.manajemen-siswa.partials.student-name-cell', [
                'name' => $siswa->name,
                'hasPelanggaran' => (bool) $siswa->_has_pelanggaran,
                'hasPrestasi' => (bool) $siswa->_has_prestasi,
            ])->render();
            $actionsHtml = view('components.student-row-actions', [
                'siswa' => $siswa,
                'hasFotoWajah' => $hasFotoWajah,
                'siswaPortalTokenActive' => PortalTokenActive::fromExpiresAt($siswa->_portal_token_expires),
                'ortuPortalTokenActive' => PortalTokenActive::fromExpiresAt($siswa->_portal_ortu_token_expires),
                'hasOrangTua' => (bool) $siswa->_has_orang_tua,
                'fields' => array_merge(
                    $siswa->only([
                        'nis', 'name', 'kelas_id', 'kamar_id', 'status_santri_id', 'gender', 'address', 'status',
                        'daily_transaction_limit',
                    ]),
                    [
                        'birth_date' => $siswa->birth_date?->format('Y-m-d'),
                        'rfid_uid' => $siswa->rfidUid(),
                        'rfid_blocked' => $siswa->isRfidBlocked(),
                        'photo_url' => $siswa->profil?->photoUrl(),
                    ]
                ),
                'editUrl' => route('admin.manajemen-siswa.data-siswa.update', $siswa),
                'deleteUrl' => route('admin.manajemen-siswa.data-siswa.destroy', $siswa),
                'formTarget' => 'student-form',
                'modalTarget' => 'student-modal',
            ])->render();

            return [
                $siswa->nis,
                $siswa->virtualAccountNumber() ?? '-',
                $this->cell($nameHtml, $siswa->name, 'text'),
                $siswa->kelas?->name ?? '-',
                $siswa->gender ?? '-',
                $siswa->statusLabel(),
                $this->cell($rfidHtml, $siswa->rfidUid() ?? '-', 'text'),
                $this->cell($faceHtml, $hasFotoWajah ? 'Sudah' : 'Belum', 'text'),
                $this->cell($tokenHtml, $siswa->_portal_token_expires ? 'Aktif' : 'Tidak aktif', 'text'),
                $this->cell($actionsHtml, null, 'action'),
            ];
        });
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $data = $request->safe()->except('photo');
        $sekolahId = AdminSchoolScope::resolveForStore($request);
        $rfidUid = $data['rfid_uid'] ?? null;
        $rfidBlocked = (bool) ($data['rfid_blocked'] ?? false);
        unset($data['rfid_uid'], $data['rfid_blocked']);

        $siswa = DB::transaction(function () use ($request, $data, $sekolahId, $rfidUid, $rfidBlocked) {
            $siswa = Siswa::create(array_merge(
                $data,
                ['sekolah_id' => $sekolahId]
            ));

            Dompet::create(['siswa_id' => $siswa->id]);
            SaldoKeuangan::create(['siswa_id' => $siswa->id, 'balance' => 0]);
            KartuSiswa::provisionFor($siswa);
            $siswa->assignRfid($rfidUid, $rfidBlocked);

            if ($request->hasFile('photo')) {
                $profil = ProfilSiswa::firstOrCreate(['siswa_id' => $siswa->id]);
                $profil->update([
                    'photo_path' => $this->storeProfilePhoto($request->file('photo'), 'photos/siswa'),
                ]);
            }

            return $siswa;
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Siswa berhasil ditambahkan', ActionMessage::siswa($siswa->load('kelas'))),
            $siswa,
            201
        );
    }

    public function update(UpdateStudentRequest $request, Siswa $siswa, StudentEditLogger $editLogger): JsonResponse
    {
        $data = $request->safe()->except('photo');
        $shouldSyncRfid = array_key_exists('rfid_uid', $data) || array_key_exists('rfid_blocked', $data);
        $rfidUid = array_key_exists('rfid_uid', $data) ? $data['rfid_uid'] : $siswa->rfidUid();
        $rfidBlocked = array_key_exists('rfid_blocked', $data)
            ? (bool) $data['rfid_blocked']
            : $siswa->isRfidBlocked();
        unset($data['rfid_uid'], $data['rfid_blocked']);

        $trackedFields = [
            'nis', 'name', 'kelas_id', 'kamar_id', 'status_santri_id', 'gender', 'birth_date', 'address', 'status',
            'rfid_uid', 'rfid_blocked', 'daily_transaction_limit',
        ];
        $before = array_merge($siswa->only(array_diff($trackedFields, ['rfid_uid', 'rfid_blocked'])), [
            'rfid_uid' => $siswa->rfidUid(),
            'rfid_blocked' => $siswa->isRfidBlocked(),
        ]);
        $after = [];
        foreach ($trackedFields as $field) {
            $after[$field] = match ($field) {
                'rfid_uid' => $rfidUid,
                'rfid_blocked' => $rfidBlocked,
                default => array_key_exists($field, $data) ? $data[$field] : $before[$field],
            };
        }

        DB::transaction(function () use ($siswa, $data, $rfidUid, $rfidBlocked, $shouldSyncRfid): void {
            $siswa->update($data);
            if ($shouldSyncRfid) {
                $siswa->assignRfid($rfidUid, $rfidBlocked);
            }
        });
        $editLogger->logChanges($request, $siswa, $before, $after);

        if ($request->hasFile('photo')) {
            $profil = ProfilSiswa::firstOrCreate(['siswa_id' => $siswa->id]);
            $oldPhoto = $profil->photo_path;
            $profil->update([
                'photo_path' => $this->replaceProfilePhoto(
                    $request->file('photo'),
                    'photos/siswa',
                    $profil->photo_path
                ),
            ]);
            $editLogger->logField($request, $siswa, 'photo', $oldPhoto, $profil->fresh()->photo_path);
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject('Siswa berhasil diperbarui', ActionMessage::siswa($siswa->load('kelas'))),
            $siswa
        );
    }

    public function destroy(Siswa $siswa): JsonResponse
    {
        $detail = ActionMessage::siswa($siswa->load('kelas'));
        $this->authorize('students.delete');
        $siswa->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Siswa berhasil dihapus', $detail));
    }

    public function stats(): JsonResponse
    {
        return $this->jsonSuccess('OK', [
            'total' => Siswa::count(),
            'aktif' => Siswa::where('status', Siswa::STATUS_ACTIVE)->count(),
            'pending' => Siswa::where('status', Siswa::STATUS_PENDING)->count(),
            'nonaktif' => Siswa::where('status', Siswa::STATUS_INACTIVE)->count(),
        ]);
    }

    public function showFacePhoto(Siswa $siswa): Response
    {
        $this->authorize('students.view');

        return $this->respondFacePhoto($siswa);
    }

    public function storeFaceCapture(Request $request, Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        return $this->saveFacePhoto($request, $siswa);
    }

    public function deleteFaceCapture(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        return $this->removeFacePhoto($siswa);
    }

    public function orangTua(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.view');

        $siswa->loadMissing('kelas');

        $canRemove = auth()->user()?->can('students.delete') ?? false;

        $items = $siswa->orangTua()
            ->orderBy('id')
            ->get()
            ->map(fn (OrangTua $orangTua) => [
                'id' => $orangTua->id,
                'name' => $orangTua->displayName(),
                'nama_ayah' => $orangTua->nama_ayah,
                'telepon_ayah' => $orangTua->telepon_ayah,
                'nama_ibu' => $orangTua->nama_ibu,
                'telepon_ibu' => $orangTua->telepon_ibu,
                'nama_wali' => $orangTua->nama_wali,
                'telepon_wali' => $orangTua->telepon_wali,
                'phone' => $orangTua->primaryPhone(),
                'show_url' => route('admin.manajemen-siswa.orang-tua.show', $orangTua),
                'remove_url' => $canRemove
                    ? route('admin.manajemen-siswa.data-siswa.orang-tua.remove', [$siswa, $orangTua])
                    : null,
            ])
            ->values()
            ->all();

        return $this->jsonSuccess('OK', [
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name,
            ],
            'items' => $items,
            'assign_url' => route('admin.manajemen-siswa.data-siswa.orang-tua.assign', $siswa),
        ]);
    }

    public function assignOrangTua(Request $request, Siswa $siswa): JsonResponse
    {
        $this->authorize('students.create');

        $data = $request->validate([
            'orang_tua_id' => ['required', SoftDeleteRules::exists('orang_tua')],
        ]);

        $orangTua = OrangTua::findOrFail($data['orang_tua_id']);

        $result = SiswaOrangTuaLink::attach($siswa, $orangTua);

        if (! $result['ok']) {
            return $this->jsonError($result['message']);
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject('Orang tua berhasil ditambahkan', ActionMessage::orangTua($orangTua)),
            [
                'orang_tua_id' => $orangTua->id,
                'siswa_id' => $siswa->id,
            ]
        );
    }

    public function removeOrangTua(Siswa $siswa, OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.delete');

        if (! $siswa->orangTua()->where('orang_tua_id', $orangTua->id)->exists()) {
            return $this->jsonError('Orang tua tidak terhubung dengan siswa ini.');
        }

        $siswa->orangTua()->detach($orangTua->id);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Orang tua berhasil dihapus dari siswa', ActionMessage::orangTua($orangTua))
        );
    }
}
