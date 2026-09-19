<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\Rapor;
use App\Support\AkademikSemester;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaporController extends Controller
{
    use PortalAccess;

    public function index(Request $request): View
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id')->all();

        $query = Rapor::query()
            ->with(['siswa.kelas', 'tahunAkademik', 'mapel.mataPelajaran'])
            ->where('status', Rapor::STATUS_FINAL)
            ->whereIn('siswa_id', $childIds)
            ->orderByDesc('finalized_at');

        $selected = $this->selectedOrtuChildId($request);
        if ($selected) {
            $query->where('siswa_id', $selected);
        }

        return view('portal.ortu.rapor', [
            'title' => 'Rapor Anak',
            'children' => $children,
            'raporList' => $query->get(),
            'semesters' => AkademikSemester::labels(),
        ]);
    }
}
