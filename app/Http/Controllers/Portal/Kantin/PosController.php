<?php

namespace App\Http\Controllers\Portal\Kantin;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesFaceCapture;
use App\Http\Traits\KantinPortal;
use App\Models\Dompet;
use App\Models\Kelas;
use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Services\CashlessTransactionGuard;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    use DataTableTrait;
    use HandlesFaceCapture;
    use KantinPortal;

    public function __construct(
        private readonly CashlessTransactionGuard $cashlessGuard,
    ) {}

    public function index(): View
    {
        $sekolahId = $this->kantinSekolahId();

        return view('portal.kantin.pos', [
            'title' => 'Belanja Kantin',
            'lookupUrl' => route('portal.kantin.pos.lookup'),
            'chargeUrl' => route('portal.kantin.pos.charge'),
            'faceReferencesUrl' => route('portal.kantin.pos.face-references'),
            'faceModelUrl' => 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights',
            'classes' => Kelas::query()
                ->when($sekolahId, fn ($q, $id) => $q->where('sekolah_id', $id))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rfid_uid' => ['nullable', 'string', 'max:64'],
            'siswa_id' => ['nullable', 'integer'],
        ]);

        $viaRfid = filled($data['rfid_uid'] ?? null);
        $siswa = $this->resolvePosStudent($data);

        if (! $siswa) {
            return $this->posError(
                $viaRfid
                    ? 'Kartu RFID tidak dikenali atau siswa tidak aktif.'
                    : 'Siswa hasil deteksi wajah tidak ditemukan atau tidak aktif.',
                $viaRfid
                    ? 'Pastikan kartu sudah terdaftar di Data Siswa. Scan ulang kartu, atau hubungi admin jika masalah berlanjut.'
                    : 'Deteksi ulang wajah siswa, atau pastikan siswa berstatus aktif di Data Siswa.',
                status: 404
            );
        }

        if (! $siswa->canUseCashlessRfid()) {
            return $this->posError(
                $siswa->isRfidBlocked()
                    ? 'Kartu RFID siswa diblokir.'
                    : 'Siswa belum siap transaksi cashless (RFID belum terpasang).',
                $siswa->isRfidBlocked()
                    ? 'Hubungi admin sekolah untuk membuka blokir kartu RFID siswa ini.'
                    : 'Pasang kartu RFID cashless siswa di Data Siswa terlebih dahulu.',
                status: 403
            );
        }

        if ($this->siswaOutsideKantinScope($siswa)) {
            return $this->posError(
                'Siswa tidak terdaftar di sekolah operator kantin.',
                'Operator kantin hanya dapat melayani siswa dari sekolah yang sama. Pastikan kartu siswa sesuai sekolah Anda.',
                status: 403
            );
        }

        $siswa->loadMissing('kelas');
        $dompet = Dompet::firstOrCreate(['siswa_id' => $siswa->id]);
        $saldoCashless = $this->totalCashlessBalance($dompet);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Siswa ditemukan', ActionMessage::siswa($siswa)),
            [
                'siswa' => [
                    'id' => $siswa->id,
                    'nis' => $siswa->nis,
                    'name' => $siswa->name,
                    'kelas' => $siswa->kelas?->name,
                ],
                'saldo_cashless' => $saldoCashless,
                'limits' => $this->cashlessGuard->dailySummary($siswa),
            ]
        );
    }

    public function charge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rfid_uid' => ['nullable', 'string', 'max:64'],
            'siswa_id' => ['nullable', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $viaRfid = filled($data['rfid_uid'] ?? null);
        $siswa = $this->resolvePosStudent($data);

        if (! $siswa) {
            return $this->posError(
                $viaRfid
                    ? 'Kartu RFID tidak dikenali atau siswa tidak aktif.'
                    : 'Siswa hasil deteksi wajah tidak ditemukan atau tidak aktif.',
                $viaRfid
                    ? 'Scan ulang kartu siswa. Jika kartu sudah diganti, minta admin memperbarui RFID di Data Siswa.'
                    : 'Deteksi ulang wajah siswa, atau pastikan siswa berstatus aktif di Data Siswa.',
                status: 404
            );
        }

        if ($this->siswaOutsideKantinScope($siswa)) {
            return $this->posError(
                'Siswa tidak terdaftar di sekolah operator kantin.',
                'Pastikan siswa bersekolah di unit yang sama dengan operator kantin yang sedang login.',
                status: 403
            );
        }

        $amount = (int) $data['amount'];

        try {
            $this->cashlessGuard->assertCanSpend($siswa, (float) $amount);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Transaksi ditolak.';

            return $this->posError(
                $message,
                $this->spendLimitHint($message),
                $e->errors(),
            );
        }

        $dompet = Dompet::firstOrCreate(['siswa_id' => $siswa->id]);
        $saldoCashless = $this->totalCashlessBalance($dompet);
        if ($saldoCashless < $amount) {
            return $this->posError(
                sprintf(
                    'Saldo cashless tidak mencukupi. Tersedia %s, total belanja %s.',
                    ActionMessage::rupiah($saldoCashless),
                    ActionMessage::rupiah($amount),
                ),
                'Kurangi nominal total belanja, atau minta siswa/ortu melakukan top-up saldo cashless terlebih dahulu.',
                data: [
                    'saldo_cashless' => $saldoCashless,
                    'amount' => $amount,
                ],
            );
        }

        $description = trim((string) ($data['description'] ?? ''));
        $description = $description !== '' ? $description : null;

        $transaction = DB::transaction(function () use ($siswa, $dompet, $amount, $description, $request) {
            $allocations = $this->allocateCashlessSpend($dompet, $amount);
            $dompet->refresh();

            $lastTransaction = null;
            foreach ($allocations as $allocation) {
                $lastTransaction = SccttranCashless::create([
                    'CUSTID' => $siswa->id,
                    'user_id' => $request->user()?->id,
                    'METODE' => 'BELANJA',
                    'TRXDATE' => now(),
                    'DEBET' => $allocation['amount'],
                    'wallet' => $allocation['wallet'],
                    'description' => $description,
                ]);
            }

            return $lastTransaction;
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Pembayaran kantin '.ActionMessage::rupiah($amount).' berhasil',
                ActionMessage::siswa($siswa)
            ),
            [
                'transaction_id' => $transaction->id,
                'saldo_cashless' => $this->totalCashlessBalance($dompet->fresh()),
                'limits' => $this->cashlessGuard->dailySummary($siswa),
            ],
            201
        );
    }

    /**
     * Resolve POS student from RFID (scan) or siswa_id (face detection). Active only.
     *
     * @param  array{rfid_uid?: mixed, siswa_id?: mixed}  $data
     */
    private function resolvePosStudent(array $data): ?Siswa
    {
        if (filled($data['rfid_uid'] ?? null)) {
            return $this->cashlessGuard->findActiveStudentByRfid((string) $data['rfid_uid']);
        }

        if (filled($data['siswa_id'] ?? null)) {
            return Siswa::query()
                ->where('status', Siswa::STATUS_ACTIVE)
                ->find((int) $data['siswa_id']);
        }

        return null;
    }

    /**
     * Face references (siswa berfoto wajah, punya RFID cashless aktif) untuk deteksi wajah POS.
     * Deskriptor dibangun di browser; scope dibatasi sekolah operator kantin.
     */
    public function faceReferences(Request $request): JsonResponse
    {
        $query = Siswa::query()
            ->select(['id', 'nis', 'name', 'kelas_id', 'updated_at'])
            ->where('has_foto_wajah', 1)
            ->where('status', Siswa::STATUS_ACTIVE)
            ->whereHas('rfid', fn ($query) => $query->where('blocked', false))
            ->with(['kelas:id,name'])
            ->orderBy('name');

        if ($sekolahId = $this->kantinSekolahId()) {
            $query->where('sekolah_id', $sekolahId);
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', (int) $request->query('kelas_id'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            if ($term !== '') {
                $like = '%'.$term.'%';
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', $like)
                    ->orWhere('nis', 'like', $like));
            }
        }

        $students = $query->limit(500)->get();

        return $this->jsonSuccess('OK', [
            'total' => $students->count(),
            'students' => $students->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name ?? '-',
                'photo_url' => route('portal.kantin.pos.face-photo', $siswa).'?v='.($siswa->updated_at?->timestamp ?? $siswa->id),
            ])->values(),
        ]);
    }

    public function facePhoto(Siswa $siswa): Response
    {
        abort_if($this->siswaOutsideKantinScope($siswa), 404);
        abort_if(! $siswa->hasFotoWajah(), 404);

        return $this->respondFacePhoto($siswa);
    }

    private function totalCashlessBalance(Dompet $dompet): int
    {
        return max(0, (int) round(
            (float) $dompet->saldo_us
            + (float) $dompet->saldo_kantin
            + (float) $dompet->saldo_tabungan
        ));
    }

    /**
     * @return list<array{wallet: string, amount: int}>
     */
    private function allocateCashlessSpend(Dompet $dompet, int $amount): array
    {
        $remaining = $amount;
        $allocations = [];

        foreach ([
            ['wallet' => 'us', 'field' => 'saldo_us'],
            ['wallet' => 'kantin', 'field' => 'saldo_kantin'],
            ['wallet' => 'tabungan', 'field' => 'saldo_tabungan'],
        ] as $wallet) {
            if ($remaining <= 0) {
                break;
            }

            $available = max(0, (int) round((float) $dompet->{$wallet['field']}));
            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);
            $dompet->decrement($wallet['field'], $take);
            $allocations[] = [
                'wallet' => $wallet['wallet'],
                'amount' => $take,
            ];
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Saldo cashless tidak mencukupi.');
        }

        return $allocations;
    }

    private function posError(
        string $message,
        string $hint,
        mixed $errors = null,
        int $status = 422,
        ?array $data = null,
    ): JsonResponse {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'hint' => $hint,
            'errors' => $errors,
            'data' => $data,
        ], fn ($value) => $value !== null), $status);
    }

    private function spendLimitHint(string $message): string
    {
        if (str_contains($message, 'limit harian siswa')) {
            return 'Kurangi total belanja sesuai sisa limit siswa hari ini, atau lanjutkan besok setelah limit direset.';
        }

        if (str_contains($message, 'limit harian sekolah')) {
            return 'Kurangi total belanja sesuai sisa limit sekolah hari ini, atau hubungi admin jika limit perlu disesuaikan.';
        }

        if (str_contains($message, 'RFID')) {
            return 'Hubungi admin sekolah untuk memeriksa status kartu RFID siswa.';
        }

        return 'Periksa kembali data transaksi, lalu coba simpan ulang.';
    }
}
