<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\MenuKantin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuKantinController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.dompet-digital.menu-kantin', ['title' => 'Menu Kantin']);
    }

    public function data(Request $request): JsonResponse
    {
        $query = MenuKantin::query();

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'category'],
            'orderable' => ['name', 'price', 'category', 'created_at'],
        ], function (MenuKantin $row) {
            return [
                $row->name,
                $row->category ?? '-',
                'Rp '.number_format($row->price, 0, ',', '.'),
                $row->is_active ? 'Aktif' : 'Nonaktif',
            ];
        });
    }
}
