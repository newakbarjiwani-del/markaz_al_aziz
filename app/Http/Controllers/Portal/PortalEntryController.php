<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\HomeRedirect;
use Illuminate\Http\RedirectResponse;

class PortalEntryController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect(HomeRedirect::for(auth()->user()));
    }
}
