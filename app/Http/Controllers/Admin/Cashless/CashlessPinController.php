<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Siswa;
use App\Services\Cashless\CashlessPinService;
use App\Support\ActionMessage;
use App\Support\CashlessPin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashlessPinController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly CashlessPinService $cashlessPin,
    ) {}

    public function update(Request $request, Siswa $siswa): JsonResponse
    {
        $this->authorizeCashlessPinManage();

        $data = $request->validate([
            'current_pin' => CashlessPin::rules(required: false),
            'pin' => array_merge(CashlessPin::rules(required: true), ['confirmed']),
        ]);

        try {
            $this->cashlessPin->change(
                $siswa,
                $data['pin'],
                $data['current_pin'] ?? null,
            );
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Gagal menyimpan PIN.',
                $e->errors(),
                422
            );
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                $this->cashlessPin->isSet($siswa->fresh()) ? 'PIN cashless berhasil disimpan' : 'PIN cashless diperbarui',
                ActionMessage::siswa($siswa)
            ),
            ['pin_set' => true]
        );
    }

    public function reset(Siswa $siswa): JsonResponse
    {
        $this->authorizeCashlessPinManage();

        $this->cashlessPin->reset($siswa);

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'PIN cashless berhasil direset',
                ActionMessage::siswa($siswa)
            ),
            ['pin_set' => false]
        );
    }

    public function status(Siswa $siswa): JsonResponse
    {
        $this->authorizeCashlessPinManage();

        return $this->jsonSuccess('OK', [
            'pin_set' => $this->cashlessPin->isSet($siswa),
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
            ],
        ]);
    }

    private function authorizeCashlessPinManage(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->hasAnyRole(['admin', 'super_admin', 'cashless']) || $user->can('cashless.update')),
            403
        );
    }
}
