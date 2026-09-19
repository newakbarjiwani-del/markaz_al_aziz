<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesFaceCapture;
use App\Http\Traits\HandlesProfilePhotoUpload;
use App\Http\Traits\PortalAccess;
use App\Models\ProfilSiswa;
use App\Support\ActionMessage;
use App\Support\ProfilePhotoRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProfilController extends Controller
{
    use DataTableTrait;
    use HandlesFaceCapture;
    use HandlesProfilePhotoUpload;
    use PortalAccess;

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        return view('portal.siswa.profil', [
            'title' => 'Profil Saya',
            'siswa' => $siswa,
            'profil' => $siswa->profil,
        ]);
    }

    public function showFacePhoto(): Response
    {
        return $this->respondFacePhoto($this->linkedSiswa());
    }

    public function storeFaceCapture(Request $request): JsonResponse
    {
        return $this->saveFacePhoto($request, $this->linkedSiswa());
    }

    public function deleteFaceCapture(): JsonResponse
    {
        return $this->removeFacePhoto($this->linkedSiswa());
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate(
            ProfilePhotoRules::forField('photo', required: true),
            ProfilePhotoRules::messages('photo')
        );

        $siswa = $this->linkedSiswa();
        $profil = ProfilSiswa::firstOrCreate(['siswa_id' => $siswa->id]);

        $profil->update([
            'photo_path' => $this->replaceProfilePhoto(
                $request->file('photo'),
                'photos/siswa',
                $profil->photo_path
            ),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Foto profil berhasil diunggah', ActionMessage::siswa($siswa)),
            [
                'photo_url' => $profil->fresh()->photoUrl(),
            ]
        );
    }
}
