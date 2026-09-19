<?php

namespace App\Http\Controllers\Portal\Kantin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ProfilController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('profile.show');
    }
}
