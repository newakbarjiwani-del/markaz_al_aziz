<?php

namespace App\Services;

use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\TahfidzRekapSiswa;
use App\Support\WhatsAppLink;
use Illuminate\Validation\ValidationException;

class TahfidzRekapWhatsAppService
{
    public function __construct(private readonly TahfidzRekapComposer $composer) {}

    /**
     * @return array{siswa_name: string, phone: string, phone_display: string, message: string, whatsapp_url: string}
     */
    public function build(TahfidzRekapSiswa $row): array
    {
        $row->loadMissing(['siswa.orangTua', 'rekap.program', 'halaqoh.guru']);

        $siswa = $row->siswa;
        $phone = $this->resolvePhone($siswa);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'rekap_siswa_id' => 'Nomor WhatsApp wali tidak tersedia.',
            ]);
        }

        $message = $this->composer->childMessage($row);
        $url = WhatsAppLink::url($phone, $message);

        if ($url === null) {
            throw ValidationException::withMessages([
                'rekap_siswa_id' => 'Nomor WhatsApp wali tidak valid.',
            ]);
        }

        return [
            'siswa_name' => $siswa?->name ?? '-',
            'phone' => $phone,
            'phone_display' => $phone,
            'message' => $message,
            'whatsapp_url' => $url,
        ];
    }

    public function resolvePhone(?Siswa $siswa): ?string
    {
        if ($siswa === null) {
            return null;
        }

        $siswa->loadMissing('orangTua');

        foreach ($siswa->orangTua as $orangTua) {
            /** @var OrangTua $orangTua */
            $phone = WhatsAppLink::normalizePhone($orangTua->primaryPhone());
            if ($phone !== null) {
                return $phone;
            }
        }

        return null;
    }
}
