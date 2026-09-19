<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancePaymentService;
use Illuminate\Http\Request;

class FinancePaymentController extends Controller
{
    public function __invoke(Request $request, FinancePaymentService $service): \Illuminate\Http\Response
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        return response($service->process($data['token']), 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
