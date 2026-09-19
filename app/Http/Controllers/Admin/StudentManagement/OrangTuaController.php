<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreOrangTuaRequest;
use App\Http\Requests\Student\UpdateOrangTuaRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\OrangTua;
use App\Models\PortalAccessToken;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PortalAccessTokenService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\PortalTokenActive;
use App\Support\SiswaOrangTuaLink;
use App\Support\SoftDeleteRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrangTuaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        return view('admin.manajemen-siswa.orang-tua', [
            'title' => 'Data Orang Tua',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function show(OrangTua $orangTua): View
    {
        $this->authorize('students.view');

        $orangTua->load(['sekolah', 'siswa.kelas']);

        $ortuUser = User::query()->where('orang_tua_id', $orangTua->id)->first();
        $portalAccess = app(PortalAccessTokenService::class);

        return view('admin.manajemen-siswa.show-orang-tua', [
            'title' => 'Detail Orang Tua',
            'orangTua' => $orangTua,
            'portalTokenActive' => $ortuUser && $portalAccess->activeToken($ortuUser, 'orang_tua') !== null,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('students.view');

        $query = OrangTua::query()
            ->with(['sekolah', 'siswa.kelas'])
            ->withCount('siswa')
            ->selectSub(
                PortalAccessToken::activeExpiresSubquery('orang_tua_id', 'orang_tua', 'orang_tua'),
                '_portal_token_expires'
            );

        $this->applyOrangTuaFilters($query, $request);

        return $this->datatableResponse($request, $query, [
            'searchable' => [
                'nama_ayah',
                'telepon_ayah',
                'email_ayah',
                'nama_ibu',
                'telepon_ibu',
                'email_ibu',
                'nama_wali',
                'telepon_wali',
                'email_wali',
                'status',
                'siswa.name',
                'siswa.nis',
            ],
            // Keyed by raw column index — one entry per column (0–10).
            // Non-sortable columns (Nama Anak, Token Login, Aksi) are placeholders.
            'orderable' => [
                'nama_ayah',       // 0 Nama Ayah
                'telepon_ayah',    // 1 Telepon Ayah
                'nama_ibu',        // 2 Nama Ibu
                'telepon_ibu',     // 3 Telepon Ibu
                'nama_wali',       // 4 Nama Wali
                'telepon_wali',    // 5 Telepon Wali
                'siswa_count',     // 6 Jumlah Anak
                'nama_ayah',       // 7 Nama Anak (client-side orderable: false)
                'status',          // 8 Status
                'nama_ayah',       // 9 Token Login (client-side orderable: false)
                'nama_ayah',       // 10 Aksi (client-side orderable: false)
            ],
        ], function (OrangTua $orangTua) {
            $fields = $orangTua->only([
                'sekolah_id', 'nama_ayah', 'telepon_ayah', 'email_ayah',
                'pekerjaan_ayah', 'nama_ibu', 'telepon_ibu', 'email_ibu',
                'pekerjaan_ibu', 'nama_wali', 'telepon_wali', 'email_wali',
                'pekerjaan_wali', 'alamat', 'status',
            ]);

            $actionsHtml = view('components.orang-tua-row-actions', [
                'orangTua' => $orangTua,
                'fields' => $fields,
                'showUrl' => route('admin.manajemen-siswa.orang-tua.show', $orangTua),
                'editUrl' => route('admin.manajemen-siswa.orang-tua.update', $orangTua),
                'deleteUrl' => route('admin.manajemen-siswa.orang-tua.destroy', $orangTua),
                'portalTokenActive' => PortalTokenActive::fromExpiresAt($orangTua->_portal_token_expires),
            ])->render();

            $studentData = $orangTua->siswa
                ->take(5)
                ->map(fn ($s) => ['name' => $s->name, 'kelas' => $s->kelas?->name])
                ->values();

            $studentNamesRaw = $studentData
                ->map(fn ($s) => $s['name'].($s['kelas'] ? ' ('.$s['kelas'].')' : ''))
                ->implode("\n");

            if ($orangTua->siswa_count > 5) {
                $studentNamesRaw .= "\n+ ".($orangTua->siswa_count - 5).' lainnya';
            }

            $studentNamesRaw = $studentNamesRaw ?: '-';

            return [
                $orangTua->nama_ayah ?? '-',
                $orangTua->telepon_ayah ?? '-',
                $orangTua->nama_ibu ?? '-',
                $orangTua->telepon_ibu ?? '-',
                $orangTua->nama_wali ?? '-',
                $orangTua->telepon_wali ?? '-',
                $orangTua->siswa_count,
                $this->cell(json_encode([
                    'students' => $studentData,
                    'more' => max(0, $orangTua->siswa_count - 5),
                ]), $studentNamesRaw, 'student-list'),
                ucfirst($orangTua->status),
                $this->cell(view('admin.manajemen-siswa.partials.portal-token-cell', [
                    'expiresAt' => $orangTua->_portal_token_expires,
                ])->render(), $orangTua->_portal_token_expires ? 'Aktif' : 'Tidak aktif', 'text'),
                $this->cell($actionsHtml, null, 'action'),
            ];
        });
    }

    /**
     * Apply granular filter-form search: father, mother, guardian names
     * and linked student name / NIS. Each filled field narrows the result.
     */
    private function applyOrangTuaFilters(Builder $query, Request $request): Builder
    {
        $namaAyah = trim($request->string('nama_ayah')->toString());
        $namaIbu = trim($request->string('nama_ibu')->toString());
        $namaWali = trim($request->string('nama_wali')->toString());
        $namaSiswa = trim($request->string('nama_siswa')->toString());
        $nisSiswa = trim($request->string('nis_siswa')->toString());

        if ($namaAyah === '' && $namaIbu === '' && $namaWali === '' && $namaSiswa === '' && $nisSiswa === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($namaAyah, $namaIbu, $namaWali, $namaSiswa, $nisSiswa) {
            if ($namaAyah !== '') {
                $q->where('nama_ayah', 'like', "%{$namaAyah}%");
            }

            if ($namaIbu !== '') {
                $q->where('nama_ibu', 'like', "%{$namaIbu}%");
            }

            if ($namaWali !== '') {
                $q->where('nama_wali', 'like', "%{$namaWali}%");
            }

            if ($namaSiswa !== '' || $nisSiswa !== '') {
                $q->whereHas('siswa', function (Builder $sq) use ($namaSiswa, $nisSiswa) {
                    $sq->where(function (Builder $ssq) use ($namaSiswa, $nisSiswa) {
                        if ($namaSiswa !== '') {
                            $ssq->where('name', 'like', "%{$namaSiswa}%");
                        }

                        if ($nisSiswa !== '') {
                            if ($namaSiswa !== '') {
                                $ssq->orWhere('nis', 'like', "%{$nisSiswa}%");
                            } else {
                                $ssq->where('nis', 'like', "%{$nisSiswa}%");
                            }
                        }
                    });
                });
            }
        });
    }

    public function store(StoreOrangTuaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);

        $orangTua = OrangTua::create($data);

        return $this->jsonSuccess(ActionMessage::withSubject(
            'Orang tua berhasil ditambahkan',
            ActionMessage::orangTua($orangTua)
        ), null, 201);
    }

    public function update(UpdateOrangTuaRequest $request, OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.update');

        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);

        $orangTua->update($data);

        return $this->jsonSuccess(ActionMessage::withSubject(
            'Orang tua berhasil diperbarui',
            ActionMessage::orangTua($orangTua)
        ));
    }

    public function destroy(OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.delete');

        $orangTua->delete();

        return $this->jsonSuccess('Orang tua berhasil dihapus.');
    }

    public function assignSiswa(Request $request, OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.create');

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
        ]);

        $siswa = Siswa::findOrFail($data['siswa_id']);

        $result = SiswaOrangTuaLink::attach($siswa, $orangTua);

        if (! $result['ok']) {
            return $this->jsonError($result['message']);
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject('Siswa berhasil ditambahkan', ActionMessage::siswa($siswa))
        );
    }

    public function removeSiswa(OrangTua $orangTua, Siswa $siswa): JsonResponse
    {
        $this->authorize('students.delete');

        if (! $orangTua->siswa()->where('siswa_id', $siswa->id)->exists()) {
            return $this->jsonError('Siswa tidak terdaftar sebagai anak dari orang tua ini.');
        }

        $orangTua->siswa()->detach($siswa->id);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Siswa berhasil dihapus dari orang tua', ActionMessage::siswa($siswa))
        );
    }
}
