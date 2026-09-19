<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Services\PortalAccessTokenService;
use App\Support\ActionMessage;
use App\Support\WhatsAppLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalAccessController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly PortalAccessTokenService $portalAccess,
    ) {}

    public function sendSiswaWhatsApp(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        $result = $this->portalAccess->issueForSiswa($siswa, auth()->id());
        $whatsappUrl = WhatsAppLink::url($result['phone'], $result['message']);

        if (! $whatsappUrl) {
            return $this->jsonError('Nomor WhatsApp tidak tersedia. Pastikan siswa memiliki orang tua dengan nomor telepon.');
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject('Link login portal siswa siap dikirim via WhatsApp', ActionMessage::siswa($siswa)),
            [
                'access_url' => $result['access_url'],
                'phone' => $result['phone'],
                'message' => $result['message'],
                'whatsapp_url' => $whatsappUrl,
                'expires_at' => $result['expires_at'],
            ]
        );
    }

    public function sendOrangTuaWhatsApp(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        $orangTua = $siswa->orangTua()->first();
        if (! $orangTua) {
            return $this->jsonError('Siswa belum memiliki data orang tua.');
        }

        $result = $this->portalAccess->issueForOrangTua($orangTua, $siswa, auth()->id());
        $whatsappUrl = WhatsAppLink::url($result['phone'], $result['message']);

        if (! $whatsappUrl) {
            return $this->jsonError('Nomor WhatsApp orang tua tidak tersedia.');
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Link login portal orang tua siap dikirim via WhatsApp',
                ActionMessage::orangTua($orangTua).' · siswa '.ActionMessage::siswa($siswa)
            ),
            [
                'access_url' => $result['access_url'],
                'phone' => $result['phone'],
                'message' => $result['message'],
                'whatsapp_url' => $whatsappUrl,
                'expires_at' => $result['expires_at'],
            ]
        );
    }

    public function sendOrangTuaWhatsAppByParent(OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.update');

        $result = $this->portalAccess->issueForOrangTua($orangTua, null, auth()->id());
        $whatsappUrl = WhatsAppLink::url($result['phone'], $result['message']);

        if (! $whatsappUrl) {
            return $this->jsonError('Nomor WhatsApp orang tua tidak tersedia.');
        }

        $orangTua->loadMissing('siswa');
        $children = $orangTua->siswa->pluck('name')->filter()->implode(', ');
        $subject = ActionMessage::orangTua($orangTua).($children !== '' ? ' · anak: '.$children : '');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Link login portal orang tua siap dikirim via WhatsApp', $subject),
            [
                'access_url' => $result['access_url'],
                'phone' => $result['phone'],
                'message' => $result['message'],
                'whatsapp_url' => $whatsappUrl,
                'expires_at' => $result['expires_at'],
            ]
        );
    }

    public function revokeSiswaTokens(Request $request, Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        $validated = $request->validate([
            'role' => ['nullable', 'in:siswa,orang_tua,all'],
        ]);

        $role = match ($validated['role'] ?? 'all') {
            'siswa' => 'siswa',
            'orang_tua' => 'orang_tua',
            default => null,
        };

        $count = $this->portalAccess->revokeForSiswa($siswa, $role);

        if ($count <= 0) {
            return $this->jsonSuccess(
                ActionMessage::withSubject('Tidak ada token portal aktif', ActionMessage::siswa($siswa))
            );
        }

        $action = match ($role) {
            'siswa' => 'Token portal siswa berhasil dicabut',
            'orang_tua' => 'Token portal orang tua berhasil dicabut',
            default => "{$count} token portal berhasil dicabut",
        };

        return $this->jsonSuccess(
            ActionMessage::withSubject($action, ActionMessage::siswa($siswa))
        );
    }

    public function revokeOrangTuaTokens(OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.update');

        $count = $this->portalAccess->revokeForOrangTua($orangTua);
        $orangTua->loadMissing('siswa');
        $children = $orangTua->siswa->pluck('name')->filter()->implode(', ');
        $subject = ActionMessage::orangTua($orangTua).($children !== '' ? ' · anak: '.$children : '');

        if ($count <= 0) {
            return $this->jsonSuccess(
                ActionMessage::withSubject('Tidak ada token portal aktif', $subject)
            );
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject('Token portal orang tua berhasil dicabut', $subject)
        );
    }

    public function issueSiswaLink(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        $result = $this->portalAccess->issueForSiswa($siswa, auth()->id());

        return $this->jsonSuccess(
            ActionMessage::withSubject('Token login portal siswa berhasil dibuat', ActionMessage::siswa($siswa)),
            [
                'access_url' => $result['access_url'],
                'expires_at' => $result['expires_at'],
            ]
        );
    }

    public function copySiswaLink(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        $link = $this->portalAccess->activeLinkForSiswa($siswa);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Link login portal siswa berhasil disalin', ActionMessage::siswa($siswa)),
            $link
        );
    }

    public function issueOrangTuaLink(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        $orangTua = $siswa->orangTua()->first();
        if (! $orangTua) {
            return $this->jsonError('Siswa belum memiliki data orang tua.');
        }

        $result = $this->portalAccess->issueForOrangTua($orangTua, $siswa, auth()->id());

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Token login portal orang tua berhasil dibuat',
                ActionMessage::orangTua($orangTua).' · siswa '.ActionMessage::siswa($siswa)
            ),
            [
                'access_url' => $result['access_url'],
                'expires_at' => $result['expires_at'],
            ]
        );
    }

    public function copyOrangTuaLink(Siswa $siswa): JsonResponse
    {
        $this->authorize('students.update');

        $orangTua = $siswa->orangTua()->first();
        if (! $orangTua) {
            return $this->jsonError('Siswa belum memiliki data orang tua.');
        }

        $link = $this->portalAccess->activeLinkForOrangTua($orangTua);

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Link login portal orang tua berhasil disalin',
                ActionMessage::orangTua($orangTua).' · siswa '.ActionMessage::siswa($siswa)
            ),
            $link
        );
    }

    public function issueOrangTuaLinkByParent(OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.update');

        $result = $this->portalAccess->issueForOrangTua($orangTua, null, auth()->id());
        $orangTua->loadMissing('siswa');
        $children = $orangTua->siswa->pluck('name')->filter()->implode(', ');
        $subject = ActionMessage::orangTua($orangTua).($children !== '' ? ' · anak: '.$children : '');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Token login portal orang tua berhasil dibuat', $subject),
            [
                'access_url' => $result['access_url'],
                'expires_at' => $result['expires_at'],
            ]
        );
    }

    public function copyOrangTuaLinkByParent(OrangTua $orangTua): JsonResponse
    {
        $this->authorize('students.update');

        $link = $this->portalAccess->activeLinkForOrangTua($orangTua);
        $orangTua->loadMissing('siswa');
        $children = $orangTua->siswa->pluck('name')->filter()->implode(', ');
        $subject = ActionMessage::orangTua($orangTua).($children !== '' ? ' · anak: '.$children : '');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Link login portal orang tua berhasil disalin', $subject),
            $link
        );
    }
}
