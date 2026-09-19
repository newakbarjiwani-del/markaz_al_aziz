<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentProfilRequest;
use App\Http\Requests\Student\UploadStudentPhotoRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\HandlesProfilePhotoUpload;
use App\Models\ProfilSiswa;
use App\Models\Siswa;
use App\Services\StudentEditLogger;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use HandlesProfilePhotoUpload;

    public function index(): View
    {
        $this->authorize('students.view');

        return view('admin.manajemen-siswa.profil-siswa', [
            'title' => 'Profil Siswa',
            'classes' => $this->classesList(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('students.view');
        $query = Siswa::query()->with(['kelas', 'kamar', 'statusSantri', 'profil']);

        $this->applyKelasFilter($query, $request, '_self');
        $this->applySiswaSearchFilter($query, $request, '_self');

        if ($request->filled('status')) {
            $status = \App\Support\SiswaStatus::normalize($request->input('status'));
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nis', 'name', 'kelas.name'],
            'orderable' => ['nis', 'name', 'created_at'],
        ], function (Siswa $row) {
            $profil = $row->profil;
            $extra = $profil?->extra_fields ?? [];

            $cells = [
                $row->nis,
                $row->name,
                $row->kelas?->name ?? '-',
                $row->kamar?->displayLabel() ?? '-',
                $row->statusSantri?->nama ?? '-',
                $profil?->photo_path ? 'Ada' : '-',
                $extra['nama_panggilan'] ?? '-',
                $extra['golongan_darah'] ?? '-',
            ];

            if (auth()->user()?->can('students.update')) {
                $cells[] = $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.manajemen-siswa.profil-siswa.update', $row),
                            'form_target' => 'profil-siswa-form',
                            'modal_target' => 'profil-siswa-modal',
                            'record' => [
                                'nis' => $row->nis,
                                'name' => $row->name,
                                'nama_panggilan' => $extra['nama_panggilan'] ?? '',
                                'golongan_darah' => $extra['golongan_darah'] ?? '',
                            ],
                        ],
                    ],
                ], 'action');
            }

            return $cells;
        });
    }

    public function update(UpdateStudentProfilRequest $request, Siswa $siswa, StudentEditLogger $editLogger): JsonResponse
    {
        $this->authorize('students.update');

        $profil = ProfilSiswa::firstOrCreate(['siswa_id' => $siswa->id]);
        $extra = $profil->extra_fields ?? [];
        $before = [
            'nama_panggilan' => $extra['nama_panggilan'] ?? null,
            'golongan_darah' => $extra['golongan_darah'] ?? null,
        ];
        $after = $before;

        foreach (['nama_panggilan', 'golongan_darah'] as $key) {
            if (! $request->has($key)) {
                continue;
            }

            $value = trim((string) $request->input($key, ''));

            if ($value === '') {
                unset($extra[$key]);
                $after[$key] = null;
            } else {
                $extra[$key] = $value;
                $after[$key] = $value;
            }
        }

        $profil->update(['extra_fields' => $extra]);
        $editLogger->logChanges($request, $siswa, $before, $after);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Profil siswa berhasil diperbarui', ActionMessage::siswa($siswa)),
            [
                'extra_fields' => $profil->fresh()->extra_fields,
            ]
        );
    }

    public function uploadPhoto(UploadStudentPhotoRequest $request, Siswa $siswa, StudentEditLogger $editLogger): JsonResponse
    {
        $this->authorize('students.update');
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

        return $this->jsonSuccess(
            ActionMessage::withSubject('Foto profil siswa berhasil diunggah', ActionMessage::siswa($siswa)),
            [
                'photo_url' => $profil->fresh()->photoUrl(),
            ]
        );
    }
}
