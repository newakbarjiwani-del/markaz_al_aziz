<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\RatingReviewController as BaseController;
use Illuminate\View\View;

class RatingReviewController extends BaseController
{
    public function index(): View
    {
        return view('admin.perpustakaan.rating-ulasan', [
            'title' => 'Rating & Review Buku',
            'ajaxUrl' => route('portal.perpustakaan.rating-ulasan.data'),
        ]);
    }
}
