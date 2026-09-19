<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\PortalPwa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        return response()
            ->json(PortalPwa::manifest())
            ->header('Content-Type', 'application/manifest+json');
    }

    public function offline(): View
    {
        return view('portal.offline', [
            'title' => 'Offline',
        ]);
    }

    public function serviceWorker(): Response
    {
        return response()
            ->view('portal.service-worker', [
                'version' => config('pwa.cache_version'),
            ])
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
