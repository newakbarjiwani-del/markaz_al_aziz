<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\PortalAccess;
use App\Models\Siswa;
use App\Services\Cashless\CashlessPinService;
use App\Support\ActionMessage;
use App\Support\CashlessPin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashlessPinController extends Controller
{
    use DataTableTrait;
    use PortalAccess;

    public function __construct(
        private readonly CashlessPinService $cashlessPin,
    ) {}

    public function index(): View
    {
        $children = $this->ortuChildren();

        return view('portal.ortu.pin-cashless', [
            'title' => 'PIN Cashless',
            'children' => $children->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name ?? '-',
                'pin_set' => $this->cashlessPin->isSet($siswa),
            ]),
        ]);
    }

    public function update(Request $request, Siswa $siswa): JsonResponse
    {
        $this->ensureOrtuChild($siswa);

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
                'PIN cashless berhasil disimpan',
                ActionMessage::siswa($siswa)
            ),
            ['pin_set' => true]
        );
    }

    private function ensureOrtuChild(Siswa $siswa): void
    {
        abort_unless(in_array($siswa->id, $this->ortuChildIds(), true), 404);
    }
}
