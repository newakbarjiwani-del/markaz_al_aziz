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

class IzinPulangLiburController extends IzinKeluarMasukController
{
    protected string $jenisPerizinan = Perizinan::JENIS_PULANG_LIBUR;
    protected string $title = 'Izin Pulang Libur Santri';

    public function index(): View
    {
        return view('admin.perizinan.pulang-libur.index', [
            'title' => '',
            'jenis' => $this->jenisPerizinan,
            'schools' => AdminSchoolScope::schools(),
            'ajaxUrl' => route("{$this->routeGroup}.pulang-libur.data"),
            'storeUrl' => route("{$this->routeGroup}.pulang-libur.store"),
            'katalogPelanggaran' => JenisPelanggaran::query()->active()->ordered()->get(),
        ]);
    }

    public function update(UpdatePerizinanRequest $request, Perizinan $pulang_libur): JsonResponse
    {
        return parent::update($request, $pulang_libur);
    }

    public function show(Perizinan $pulang_libur): JsonResponse
    {
        return parent::show($pulang_libur);
    }

    public function destroy(Perizinan $pulang_libur): JsonResponse
    {
        return parent::destroy($pulang_libur);
    }

    public function checkin(CheckinPerizinanRequest $request, Perizinan $pulang_libur): JsonResponse
    {
        return parent::checkin($request, $pulang_libur);
    }

    public function checkinPreview(Request $request, Perizinan $pulang_libur): JsonResponse
    {
        return parent::checkinPreview($request, $pulang_libur);
    }
}
