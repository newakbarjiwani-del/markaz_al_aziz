<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UploadTeacherPhotoRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\HandlesProfilePhotoUpload;
use App\Models\Guru;
use App\Models\ProfilGuru;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\GuruSekolahFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilGuruController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use HandlesProfilePhotoUpload;

    public function index(): View
    {
        return view('admin.manajemen-guru.profil-guru', [
            'title' => 'Profil Guru',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Guru::query()->with(['profil', 'sekolah']);

        GuruSekolahFilter::applyListFilter(
            $query,
            $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null
        );

        $query->when(
            $request->filled('status'),
            fn ($q) => $q->where('status', $request->string('status')->toString())
        );

        $this->applyGuruSearchFilter($query, $request, '_self');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nip', 'name', 'jabatan', 'profil.address'],
            'orderable' => ['nip', 'name', 'jabatan', 'created_at'],
        ], function (Guru $row) {
            $profil = $row->profil;
            $extra = $profil?->extra_fields ?? [];

            return [
                $row->nip,
                $row->name,
                $row->jabatan ?? '-',
                $profil?->photo_path ? 'Ada' : '-',
                $profil?->address ?? '-',
                $extra['pendidikan'] ?? '-',
            ];
        });
    }

    public function uploadPhoto(UploadTeacherPhotoRequest $request, Guru $guru): JsonResponse
    {
        $profil = ProfilGuru::firstOrCreate(['guru_id' => $guru->id]);
        $profil->update([
            'photo_path' => $this->replaceProfilePhoto(
                $request->file('photo'),
                'photos/guru',
                $profil->photo_path
            ),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Foto profil guru berhasil diunggah', ActionMessage::guru($guru)),
            [
                'photo_url' => $profil->fresh()->photoUrl(),
            ]
        );
    }
}
