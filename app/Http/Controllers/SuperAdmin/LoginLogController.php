<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\LogLogin;
use App\Support\DisplayDate;
use App\Support\LoginLogMethod;
use App\Support\UserAgentInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginLogController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('super-admin.login-logs.index', [
            'title' => 'Log Login',
            'methods' => LoginLogMethod::options(),
        ]);
    }

    public function show(LogLogin $logLogin): JsonResponse
    {
        return $this->jsonSuccess('Detail log login.', $this->detailPayload($logLogin));
    }

    public function data(Request $request): JsonResponse
    {
        $query = LogLogin::query()
            ->with(['user.roles'])
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')))
            ->orderByDesc('created_at');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['identifier', 'ip_address', 'message', 'method', 'status', 'browser', 'platform', 'device', 'user_agent'],
            'orderable' => ['created_at', 'identifier', 'method', 'status', 'ip_address'],
        ], function (LogLogin $log) {
            $statusLabel = $log->status === 'success' ? 'Berhasil' : 'Gagal';
            $statusClass = $log->status === 'success' ? 'badge-success' : 'badge-danger';
            $userLabel = $log->user
                ? e($log->user->username).' · '.e($log->user->name)
                : '-';

            return [
                $this->dateCell($log->created_at),
                e($log->identifier ?? '-'),
                LoginLogMethod::label($log->method),
                $this->badgeCell($statusLabel, 'badge '.$statusClass),
                e($log->ip_address ?? '-'),
                $userLabel,
                $this->cell(
                    '<button type="button" class="btn-secondary px-2.5 py-1.5 text-xs" data-login-log-detail data-log-id="'.$log->id.'">Detail</button>',
                    null,
                    'action'
                ),
            ];
        });
    }

    /** @return array<string, mixed> */
    private function detailPayload(LogLogin $log): array
    {
        $log->loadMissing('user.roles');
        $user = $log->user;
        $client = UserAgentInfo::forLog($log);

        return [
            'id' => $log->id,
            'created_at' => DisplayDate::datetime($log->created_at),
            'status' => $log->status,
            'status_label' => $log->status === 'success' ? 'Berhasil' : 'Gagal',
            'method' => $log->method,
            'method_label' => LoginLogMethod::label($log->method),
            'identifier' => $log->identifier,
            'username' => $user?->username,
            'name' => $user?->name,
            'role' => $user?->getRoleNames()->first(),
            'ip_address' => $log->ip_address,
            'browser' => $client->browser,
            'platform' => $client->platform,
            'device' => $client->device,
            'user_agent' => $log->user_agent,
            'message' => $log->message,
            'portal_access_token_id' => $log->portal_access_token_id,
        ];
    }
}
