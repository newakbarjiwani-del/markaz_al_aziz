<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\RekeningBank;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekeningController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.keuangan.rekening', [
            'title' => 'Rekening Bank',
            'activeCount' => RekeningBank::where('is_active', true)->count(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = RekeningBank::query();

        return $this->datatableResponse($request, $query, [
            'searchable' => ['bank', 'account_number', 'account_name'],
            'orderable' => ['bank', 'account_number', 'created_at'],
        ], function (RekeningBank $row) {
            $fields = $row->only(['bank', 'account_number', 'account_name', 'is_active']);

            return [
                $row->bank,
                $row->account_number,
                $row->account_name,
                $this->badgeCell($row->is_active ? 'Aktif' : 'Nonaktif', $row->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.keuangan.rekening.update', $row),
                            'form_target' => 'rekening-form',
                            'modal_target' => 'rekening-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.keuangan.rekening.destroy', $row),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus rekening ini?',
                            'confirm_detail' => [
                                ['label' => 'Bank', 'value' => $row->bank],
                                ['label' => 'No. Rekening', 'value' => $row->account_number],
                                ['label' => 'Atas Nama', 'value' => $row->account_name],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $rekening = RekeningBank::create(array_merge($data, [
            'sekolah_id' => AdminSchoolScope::resolveForStore($request),
            'is_active' => $request->boolean('is_active', true),
        ]));

        return $this->jsonSuccess('Rekening berhasil ditambahkan.', $rekening, 201);
    }

    public function update(Request $request, RekeningBank $rekening): JsonResponse
    {
        $data = $request->validate([
            'bank' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $rekening->update(array_merge($data, [
            'is_active' => $request->boolean('is_active'),
        ]));

        return $this->jsonSuccess('Rekening berhasil diperbarui.', $rekening);
    }

    public function destroy(RekeningBank $rekening): JsonResponse
    {
        $rekening->delete();

        return $this->jsonSuccess('Rekening berhasil dihapus.');
    }
}
