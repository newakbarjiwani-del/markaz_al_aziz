<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Http\Traits\DataTableTrait;
use App\Services\PortalAccessTokenService;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

trait ManagesOwnPortalAccessToken
{
    use DataTableTrait;

    abstract protected function portalAccessRole(): string;

    abstract protected function portalAccessTitle(): string;

    abstract protected function portalAccessView(): string;

    abstract protected function portalAccessRefreshRoute(): string;

    public function index(): View
    {
        $user = auth()->user();
        $service = app(PortalAccessTokenService::class);

        return view($this->portalAccessView(), [
            'title' => $this->portalAccessTitle(),
            'token' => $service->tokenStatus($user, $this->portalAccessRole()),
            'ttlDays' => (int) config('portal.access_token_ttl_days', 7),
            'refreshUrl' => $this->portalAccessRefreshRoute(),
        ]);
    }

    public function refresh(): JsonResponse
    {
        $user = auth()->user();
        $result = app(PortalAccessTokenService::class)->refreshForUser(
            $user,
            $this->portalAccessRole(),
            $user->id
        );

        return $this->jsonSuccess(
            ActionMessage::withSubject('Link login berhasil diperbarui', ActionMessage::user($user)),
            [
                'access_url' => $result['access_url'],
                'expires_at' => $result['expires_at'],
            ]
        );
    }
}
