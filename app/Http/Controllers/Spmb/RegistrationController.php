<?php

namespace App\Http\Controllers\Spmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spmb\StoreSpmbRegistrationRequest;
use App\Models\SpmbPendaftar;
use App\Models\SpmbPeriode;
use App\Services\SpmbAcceptanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        $periode = SpmbPeriode::currentOpen();

        return view('spmb.daftar', [
            'title' => 'Pendaftaran SPMB',
            'periode' => $periode,
            'isOpen' => $periode?->isOpen() ?? false,
        ]);
    }

    public function store(StoreSpmbRegistrationRequest $request, SpmbAcceptanceService $service): RedirectResponse
    {
        $periode = SpmbPeriode::currentOpen();

        if ($periode === null || ! $periode->isOpen()) {
            return redirect()
                ->route('spmb.daftar')
                ->withErrors(['periode' => 'Pendaftaran sedang ditutup. Silakan cek pengumuman terbaru.']);
        }

        $data = $request->validated();
        $nomor = $service->generateNomorPendaftaran();

        $pendaftar = SpmbPendaftar::create([
            ...$data,
            'spmb_periode_id' => $periode->id,
            'sekolah_id' => $periode->sekolah_id,
            'nomor_pendaftaran' => $nomor,
            'status' => SpmbPendaftar::STATUS_SUBMITTED,
        ]);

        return redirect()
            ->route('spmb.daftar')
            ->with('success', 'Pendaftaran berhasil. Nomor Anda: '.$pendaftar->nomor_pendaftaran)
            ->with('nomor_pendaftaran', $pendaftar->nomor_pendaftaran);
    }
}
