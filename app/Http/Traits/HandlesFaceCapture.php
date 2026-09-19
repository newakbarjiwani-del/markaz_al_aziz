<?php

namespace App\Http\Traits;

use App\Models\Siswa;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

trait HandlesFaceCapture
{
    protected function respondFacePhoto(Siswa $siswa): Response
    {
        if (! $siswa->hasFotoWajah()) {
            abort(404);
        }

        $dataUrl = $siswa->fotoWajahDataUrl();
        $commaPos = strpos($dataUrl, ',');
        if ($commaPos === false) {
            abort(404);
        }

        $binary = base64_decode(substr($dataUrl, $commaPos + 1), true);
        if ($binary === false) {
            abort(404);
        }

        $mime = 'image/jpeg';
        if (preg_match('/^data:(image\/[a-z0-9.+-]+);base64,/i', $dataUrl, $matches)) {
            $mime = strtolower($matches[1]);
        }

        return response($binary, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    protected function saveFacePhoto(Request $request, Siswa $siswa): JsonResponse
    {
        $validated = $request->validate([
            'foto_wajah' => ['required', 'string', 'max:1400000'],
        ]);

        $fotoWajah = trim((string) $validated['foto_wajah']);
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $fotoWajah)) {
            return $this->jsonError('Format foto wajah tidak valid.');
        }

        $base64 = substr($fotoWajah, strpos($fotoWajah, ',') + 1);
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            return $this->jsonError('Data foto wajah tidak valid.');
        }

        if (strlen($decoded) > (750 * 1024)) {
            return $this->jsonError('Ukuran foto terlalu besar. Maksimal sekitar 750 KB.');
        }

        DB::transaction(function () use ($siswa, $fotoWajah): void {
            $siswa->wajah()->updateOrCreate([], ['foto_wajah' => $fotoWajah]);
            $siswa->update(['has_foto_wajah' => 1]);
            $this->logFaceCapture($siswa, true);
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Foto wajah berhasil disimpan', ActionMessage::siswa($siswa->load('kelas')))
        );
    }

    protected function removeFacePhoto(Siswa $siswa): JsonResponse
    {
        DB::transaction(function () use ($siswa): void {
            $siswa->wajah()->delete();
            $siswa->update(['has_foto_wajah' => 0]);
            $this->logFaceCapture($siswa, false);
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Foto wajah berhasil dihapus', ActionMessage::siswa($siswa->load('kelas')))
        );
    }

    protected function logFaceCapture(Siswa $siswa, bool $hasFoto): void
    {
        DB::table('siswa_rekam_log')->insert([
            'siswa_id' => $siswa->id,
            'nis' => (string) $siswa->nis,
            'jenis' => 'foto',
            'rfid_uid' => null,
            'punya_foto' => $hasFoto,
            'kode_suara' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
