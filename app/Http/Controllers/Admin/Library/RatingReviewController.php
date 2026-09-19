<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\UlasanBuku;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RatingReviewController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.perpustakaan.rating-ulasan', ['title' => 'Rating & Review Buku']);
    }

    public function data(Request $request): JsonResponse
    {
        $query = UlasanBuku::query()->with(['buku', 'siswa']);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['review'],
            'orderable' => ['rating', 'created_at'],
        ], function (UlasanBuku $row) {
            return [
                $row->buku?->judul ?? '-',
                $row->siswa?->name ?? '-',
                str_repeat('★', $row->rating).str_repeat('☆', 5 - $row->rating),
                \Illuminate\Support\Str::limit($row->review ?? '-', 60),
                $row->created_at,
            ];
        });
    }
}
