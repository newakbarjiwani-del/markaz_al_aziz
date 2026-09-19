<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchBukuController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $books = $q !== ''
            ? Buku::query()
                ->where(function ($query) use ($q) {
                    $query->where('judul', 'like', "%{$q}%")
                        ->orWhere('pengarang', 'like', "%{$q}%")
                        ->orWhere('penerbit', 'like', "%{$q}%")
                        ->orWhere('kode_buku', 'like', "%{$q}%")
                        ->orWhere('isbn', 'like', "%{$q}%")
                        ->orWhere('kategori', 'like', "%{$q}%");
                })
                ->orderBy('judul')
                ->limit(50)
                ->get()
            : collect();

        return view('admin.perpustakaan.cari-buku', [
            'title' => 'Search Buku',
            'q' => $q,
            'books' => $books,
        ]);
    }
}
