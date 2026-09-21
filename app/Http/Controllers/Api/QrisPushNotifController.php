<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finance\Qris\QrisPushNotifService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrisPushNotifController extends Controller
{
    public function __invoke(Request $request, QrisPushNotifService $service): JsonResponse
    {
        $token = $request->input('token') ?? $request->query('token');

        $result = $service->handle(is_string($token) ? $token : null);

        return response()->json($result['body'], $result['http']);
    }
}
