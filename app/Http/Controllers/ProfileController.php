<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Traits\DataTableTrait;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use DataTableTrait;

    public function show(): View
    {
        $user = auth()->user()->load([
            'sekolah',
            'roles',
            'siswa.kelas',
            'guru',
            'orangTua',
        ]);

        return view('profile.show', [
            'title' => 'Profil Akun',
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->jsonSuccess(
            ActionMessage::withSubject('Profil akun berhasil diperbarui', ActionMessage::user($user->fresh()))
        );
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('password'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Password berhasil diperbarui', ActionMessage::user($user))
        );
    }
}
