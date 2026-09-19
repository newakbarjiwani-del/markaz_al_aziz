<?php

namespace App\Http\Controllers\Admin\Perizinan;

use App\Http\Requests\Perizinan\CheckinPerizinanRequest;
use App\Http\Requests\Perizinan\UpdatePerizinanRequest;
use App\Models\JenisPelanggaran;
use App\Models\Perizinan;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IzinKeluarMasukPondokController extends IzinKeluarMasukController
{
    protected string $jenisPerizinan = Perizinan::JENIS_KELUAR_MASUK_PONDOK;
    protected string $title = 'Izin Keluar Masuk Pondok';

    public function index(): View
    {
        return view('admin.perizinan.keluar-masuk-pondok.index', [
            'title' => '',
            'jenis' => $this->jenisPerizinan,
            'schools' => AdminSchoolScope::schools(),
            'ajaxUrl' => route("{$this->routeGroup}.keluar-masuk-pondok.data"),
            'storeUrl' => route("{$this->routeGroup}.keluar-masuk-pondok.store"),
            'katalogPelanggaran' => JenisPelanggaran::query()->active()->ordered()->get(),
        ]);
    }

    public function update(UpdatePerizinanRequest $request, Perizinan $keluar_masuk_pondok): JsonResponse
    {
        return parent::update($request, $keluar_masuk_pondok);
    }

    public function show(Perizinan $keluar_masuk_pondok): JsonResponse
    {
        return parent::show($keluar_masuk_pondok);
    }

    public function destroy(Perizinan $keluar_masuk_pondok): JsonResponse
    {
        return parent::destroy($keluar_masuk_pondok);
    }

    public function checkin(CheckinPerizinanRequest $request, Perizinan $keluar_masuk_pondok): JsonResponse
    {
        return parent::checkin($request, $keluar_masuk_pondok);
    }

    public function checkinPreview(Request $request, Perizinan $keluar_masuk_pondok): JsonResponse
    {
        return parent::checkinPreview($request, $keluar_masuk_pondok);
    }
}
