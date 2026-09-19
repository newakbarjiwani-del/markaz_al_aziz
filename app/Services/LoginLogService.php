<?php

namespace App\Services;

use App\Models\LogLogin;
use App\Models\PortalAccessToken;
use App\Models\User;
use App\Support\UserAgentInfo;
use Illuminate\Http\Request;

class LoginLogService
{
    public function recordSuccess(
        User $user,
        string $method,
        Request $request,
        ?PortalAccessToken $portalAccessToken = null,
        ?string $identifier = null,
    ): LogLogin {
        return $this->create(
            method: $method,
            status: 'success',
            request: $request,
            user: $user,
            identifier: $identifier ?? $user->username,
            portalAccessToken: $portalAccessToken,
        );
    }

    public function recordFailure(
        string $method,
        Request $request,
        string $message,
        ?string $identifier = null,
        ?User $user = null,
        ?PortalAccessToken $portalAccessToken = null,
    ): LogLogin {
        return $this->create(
            method: $method,
            status: 'failed',
            request: $request,
            user: $user,
            identifier: $identifier,
            portalAccessToken: $portalAccessToken,
            message: $message,
        );
    }

    private function create(
        string $method,
        string $status,
        Request $request,
        ?User $user = null,
        ?string $identifier = null,
        ?PortalAccessToken $portalAccessToken = null,
        ?string $message = null,
    ): LogLogin {
        $client = UserAgentInfo::parse($request->userAgent());

        return LogLogin::create([
            'user_id' => $user?->id,
            'method' => $method,
            'status' => $status,
            'identifier' => $identifier,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'browser' => $client->browser,
            'platform' => $client->platform,
            'device' => $client->device,
            'portal_access_token_id' => $portalAccessToken?->id,
            'message' => $message,
        ]);
    }
}
