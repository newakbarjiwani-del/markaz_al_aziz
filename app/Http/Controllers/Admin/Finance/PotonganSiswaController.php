<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePotonganSiswaRequest;
use App\Http\Requests\Finance\UpdatePotonganSiswaRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\JenisPotongan;
use App\Models\JenisTagihan;
use App\Models\PotonganSiswa;
use App\Models\Siswa;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\DisplayDate;
use App\Support\PotonganSiswaStatus;
use App\Support\PotonganTipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PotonganSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $this->authorize('potongan-tagihan.view');

        return view('admin.keuangan.potongan-siswa', [
            'title' => 'Potongan Siswa',
            'schools' => AdminSchoolScope::schools(),
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
            'jenisPotonganList' => JenisPotongan::query()->active()->ordered()->get(),
            'jenisTagihanList' => JenisTagihan::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'default_amount', 'is_spp']),
            'operatorSchoolId' => AdminSchoolScope::operatorSekolahId(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('potongan-tagihan.view');

        $allJenisTagihan = JenisTagihan::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'default_amount', 'is_spp']);

        $query = PotonganSiswa::query()
            ->with(['siswa.kelas', 'jenisPotongan', 'jenisTagihan'])
            ->withCount('pemakaian')
            ->when($request->filled('status'), fn ($q) => $q->where('status', PotonganSiswaStatus::normalize($request->status)))
            ->when($request->filled('jenis_potongan_id'), fn ($q) => $q->where('jenis_potongan_id', $request->integer('jenis_potongan_id')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')));

        $this->applyKelasFilter($query, $request, 'siswa');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['keterangan', 'siswa.name', 'siswa.nis', 'jenisPotongan.nama'],
            'orderable' => ['created_at', 'created_at', 'created_at', 'created_at', 'nilai', 'berlaku_mulai', 'max_pemakaian', null, null],
        ], function (PotonganSiswa $row) use ($allJenisTagihan) {
            $quota = $this->quotaSummaryLabel($row);

            $fields = [
                'siswa_id' => $row->siswa_id,
                'jenis_potongan_id' => $row->jenis_potongan_id,
                'tipe' => $row->tipe,
                'nilai' => $row->nilai,
                'berlaku_mulai' => $row->berlaku_mulai?->format('Y-m-d'),
                'berlaku_sampai' => $row->berlaku_sampai?->format('Y-m-d'),
                'max_pemakaian' => $row->max_pemakaian,
                'status' => $row->status,
                'keterangan' => $row->keterangan,
                'bill_cuts' => $this->billCutsForForm($row, $allJenisTagihan),
                '_siswa_label' => ($row->siswa?->name ?? '-').' ('.($row->siswa?->nis ?? '-').')',
            ];

            $actions = [
                'edit' => [
                    'update_url' => route('admin.keuangan.potongan-siswa.update', $row),
                    'form_target' => 'potongan-siswa-form',
                    'modal_target' => 'potongan-siswa-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.keuangan.potongan-siswa.destroy', $row),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Hapus penugasan potongan siswa ini?',
                    'confirm_detail' => [
                        ['label' => 'Siswa', 'value' => $row->siswa?->name ?? '-'],
                        ['label' => 'Jenis', 'value' => $row->jenisPotongan?->nama ?? '-'],
                    ],
                ],
            ];

            $statusClass = match ($row->status) {
                PotonganSiswaStatus::ACTIVE => 'badge-green',
                PotonganSiswaStatus::EXHAUSTED => 'badge-neutral',
                default => 'badge-red',
            };

            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $row->jenisPotongan?->nama ?? '-',
                $this->nilaiSummaryLabel($row),
                DisplayDate::date($row->berlaku_mulai).' – '.DisplayDate::date($row->berlaku_sampai),
                $quota,
                $this->badgeCell($row->statusLabel(), 'badge '.$statusClass),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StorePotonganSiswaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $siswa = Siswa::query()->findOrFail($data['siswa_id']);
        $billCuts = $request->enabledBillCuts();

        $potongan = DB::transaction(function () use ($request, $data, $siswa, $billCuts) {
            $potongan = PotonganSiswa::create([
                'sekolah_id' => $siswa->sekolah_id,
                'siswa_id' => $siswa->id,
                'jenis_potongan_id' => $data['jenis_potongan_id'],
                'tipe' => PotonganTipe::normalize($data['tipe']),
                'nilai' => (int) $data['nilai'],
                'berlaku_mulai' => $data['berlaku_mulai'],
                'berlaku_sampai' => $data['berlaku_sampai'],
                'max_pemakaian' => (int) $data['max_pemakaian'],
                'status' => PotonganSiswaStatus::normalize($data['status'] ?? PotonganSiswaStatus::ACTIVE),
                'keterangan' => $data['keterangan'] ?? null,
                'processed_by_user_id' => $request->user()?->id,
            ]);

            $this->syncBillCuts($potongan, $billCuts);

            return $potongan;
        });

        $potongan->load(['siswa.kelas', 'jenisPotongan']);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Potongan siswa berhasil ditambahkan', ActionMessage::siswa($potongan->siswa)),
            $potongan,
            201
        );
    }

    public function update(UpdatePotonganSiswaRequest $request, PotonganSiswa $potonganSiswa): JsonResponse
    {
        DB::transaction(function () use ($request, $potonganSiswa) {
            $data = $request->validated();
            $billCuts = $request->enabledBillCuts();

            $potonganSiswa->update([
                'jenis_potongan_id' => $data['jenis_potongan_id'],
                'tipe' => PotonganTipe::normalize($data['tipe']),
                'nilai' => (int) $data['nilai'],
                'berlaku_mulai' => $data['berlaku_mulai'],
                'berlaku_sampai' => $data['berlaku_sampai'],
                'max_pemakaian' => (int) $data['max_pemakaian'],
                'status' => PotonganSiswaStatus::normalize($data['status']),
                'keterangan' => $data['keterangan'] ?? null,
                'processed_by_user_id' => $request->user()?->id,
            ]);

            $this->syncBillCuts($potonganSiswa, $billCuts);
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Potongan siswa berhasil diperbarui', ActionMessage::siswa($potonganSiswa->siswa)),
            $potonganSiswa->fresh(['siswa.kelas', 'jenisPotongan'])
        );
    }

    public function destroy(PotonganSiswa $potonganSiswa): JsonResponse
    {
        $this->authorize('potongan-tagihan.delete');

        if ($potonganSiswa->pemakaian()->exists()) {
            return $this->jsonError('Potongan siswa tidak dapat dihapus karena sudah memiliki riwayat pemakaian.');
        }

        $detail = ActionMessage::siswa($potonganSiswa->siswa);
        $potonganSiswa->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Potongan siswa berhasil dihapus', $detail));
    }

    /**
     * @param  list<array{jenis_tagihan_id: int, use_default: bool, tipe: string|null, nilai: int|null, use_default_max: bool, max_pemakaian: int|null}>  $billCuts
     */
    private function syncBillCuts(PotonganSiswa $potongan, array $billCuts): void
    {
        $sync = [];

        foreach ($billCuts as $cut) {
            $sync[(int) $cut['jenis_tagihan_id']] = [
                'tipe' => $cut['use_default'] ? null : $cut['tipe'],
                'nilai' => $cut['use_default'] ? null : $cut['nilai'],
                'max_pemakaian' => $cut['use_default_max'] ? null : $cut['max_pemakaian'],
            ];
        }

        $potongan->jenisTagihan()->sync($sync);
    }

    /**
     * @param  Collection<int, JenisTagihan>  $allJenisTagihan
     * @return list<array<string, mixed>>
     */
    private function billCutsForForm(PotonganSiswa $row, Collection $allJenisTagihan): array
    {
        $scoped = $row->jenisTagihan->keyBy('id');
        $appliesAll = $row->appliesToAllJenisTagihan();

        return $allJenisTagihan->map(function (JenisTagihan $jenis) use ($row, $scoped, $appliesAll) {
            $pivot = $scoped->get($jenis->id);
            $enabled = $appliesAll || $pivot !== null;
            $useDefault = $appliesAll
                || ($pivot !== null && $pivot->pivot->tipe === null && $pivot->pivot->nilai === null);
            $useDefaultMax = $appliesAll
                || ($pivot !== null && $pivot->pivot->max_pemakaian === null);
            $effectiveMax = (int) ($useDefaultMax ? $row->max_pemakaian : ($pivot?->pivot?->max_pemakaian ?? $row->max_pemakaian));

            return [
                'jenis_tagihan_id' => $jenis->id,
                'enabled' => $enabled,
                'use_default' => $useDefault,
                'tipe' => PotonganTipe::normalize($pivot?->pivot?->tipe ?? $row->tipe),
                'nilai' => $enabled
                    ? (int) ($useDefault ? $row->nilai : ($pivot?->pivot?->nilai ?? $row->nilai))
                    : null,
                'use_default_max' => $useDefaultMax,
                'max_pemakaian' => $enabled ? $effectiveMax : null,
                'default_amount' => (int) $jenis->default_amount,
                'name' => $jenis->name,
                'is_spp' => (bool) $jenis->is_spp,
            ];
        })->values()->all();
    }

    private function quotaSummaryLabel(PotonganSiswa $row): string
    {
        if ($row->appliesToAllJenisTagihan()) {
            $used = $row->usageCountForJenisTagihan(null);

            return $used.' / '.$row->max_pemakaian.' · semua jenis';
        }

        $labels = $row->jenisTagihan->map(function (JenisTagihan $jenis) use ($row) {
            $used = $row->usageCountForJenisTagihan($jenis->id);
            $max = $row->effectiveMaxForJenisTagihan($jenis->id);
            $suffix = $jenis->pivot->max_pemakaian === null ? ' (default)' : '';

            return $jenis->name.': '.$used.'/'.$max.$suffix;
        })->values();

        if ($labels->count() === 1) {
            return $labels->first();
        }

        if ($labels->count() <= 3) {
            return $labels->join('; ');
        }

        return 'Variasi ('.$labels->count().' jenis)';
    }

    private function nilaiSummaryLabel(PotonganSiswa $row): string
    {
        if ($row->appliesToAllJenisTagihan()) {
            return $this->formatCutLabel($row->tipe, (int) $row->nilai).' · semua jenis (default)';
        }

        $labels = $row->jenisTagihan->map(function (JenisTagihan $jenis) use ($row) {
            $usesDefault = $jenis->pivot->tipe === null && $jenis->pivot->nilai === null;
            $suffix = $usesDefault ? ' (default)' : '';

            return $jenis->name.': '.$this->formatCutLabel(
                $jenis->pivot->tipe ?? $row->tipe,
                (int) ($jenis->pivot->nilai ?? $row->nilai),
            ).$suffix;
        })->values();

        if ($labels->count() === 1) {
            return $labels->first();
        }

        if ($labels->count() <= 3) {
            return $labels->join('; ');
        }

        return 'Variasi ('.$labels->count().' jenis)';
    }

    private function formatCutLabel(string $tipe, int $nilai): string
    {
        return PotonganTipe::normalize($tipe) === PotonganTipe::PERCENT
            ? $nilai.'%'
            : 'Rp '.number_format($nilai, 0, ',', '.');
    }
}
