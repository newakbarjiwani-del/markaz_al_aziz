<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ManagesOwnPortalAccessToken;

class AksesTokenController extends Controller
{
    use ManagesOwnPortalAccessToken;

    protected function portalAccessRole(): string
    {
        return 'siswa';
    }

    protected function portalAccessTitle(): string
    {
        return 'Link Login Portal';
    }

    protected function portalAccessView(): string
    {
        return 'portal.akses-token';
    }

    protected function portalAccessRefreshRoute(): string
    {
        return route('portal.siswa.akses-token.refresh');
    }
}
