<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\HandlesFaceCapture;
use App\Models\Dompet;
use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Models\SmTopup;
use App\Models\TransaksiCashless;
use App\Services\Cashless\CashlessPinService;
use App\Services\CashlessTransactionGuard;
use App\Services\Finance\SccttranCashlessService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\CashlessPin;
use App\Support\InfaqTiers;
use App\Support\RfidUid;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TopupSaldoController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use HandlesFaceCapture;

    public function __construct(
        private readonly SccttranCashlessService $sccttranCashless,
        private readonly CashlessTransactionGuard $cashlessGuard,
        private readonly CashlessPinService $cashlessPin,
    ) {}

    public function index(): View
    {
        return view('admin.dompet-digital.topup-saldo', [
            'title' => 'Top-up Saldo Cashless',
            'manualSaldoEnabled' => (bool) config('school.manual_saldo_cashless_adjustment_enabled', false),
            'classes' => $this->classesList(),
            'infaqMode' => InfaqTiers::mode(),
            'infaqEnabled' => InfaqTiers::isEnabled(),
            'infaqTiers' => InfaqTiers::allTiers(),
            'infaqMax' => InfaqTiers::max(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! config('school.manual_saldo_cashless_adjustment_enabled', false)) {
            return $this->jsonError('Top-up saldo cashless manual sementara dinonaktifkan.', null, 403);
        }

        $rules = [
            'siswa_id' => ['nullable', SoftDeleteRules::exists('siswa')],
            'rfid_uid' => ['nullable', 'string', 'max:64'],
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
        ];

        if (InfaqTiers::isOptional()) {
            $rules['infaq_amount'] = 'nullable|integer|min:0';
        }

        $data = $request->validate($rules);

        try {
            $siswa = $this->resolveSiswaFromRequest($data);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak ditemukan.',
                $e->errors(),
                422
            );
        }

        $amount = (int) round($data['amount']);
        if ($amount <= 0) {
            return $this->jsonError('Nominal top-up harus lebih dari 0.', [
                'amount' => ['Nominal top-up harus lebih dari 0.'],
            ]);
        }

        try {
            $siswa->assertCanTransact();
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak aktif.',
                $e->errors(),
                422
            );
        }

        $infaqAmount = InfaqTiers::isOptional()
            ? (isset($data['infaq_amount']) ? (int) $data['infaq_amount'] : 0)
            : InfaqTiers::calculate($amount);

        $trxdate = now();
        // NOREFF is char(20): MT + YmdHis = 16 chars
        $noreff = 'MT'.$trxdate->format('YmdHis');
        $description = trim((string) ($data['description'] ?? ''));
        $description = $description !== '' ? $description : 'Top-up manual';
        $viaRfid = filled(RfidUid::sanitize($data['rfid_uid'] ?? null));
        $fidbank = $viaRfid ? 'RFID' : 'CASH';

        DB::transaction(function () use ($request, $siswa, $amount, $trxdate, $noreff, $description, $fidbank, $infaqAmount): void {
            SccttranCashless::create([
                'CUSTID' => $siswa->id,
                'user_id' => $request->user()?->id,
                'METODE' => 'TOP UP',
                'TRXDATE' => $trxdate,
                'KREDIT' => $amount,
                'DEBET' => 0,
                'FIDBANK' => $fidbank,
                'NOREFF' => $noreff,
                'description' => $description,
            ]);

            // Cache for POS spend (us → kantin → tabungan); ledger itself has no wallet.
            Dompet::firstOrCreate(['siswa_id' => $siswa->id])
                ->increment('saldo_us', $amount);

            SmTopup::create([
                'CUSTID' => $siswa->id,
                'user_id' => $request->user()?->id,
                'NOMINAL' => $amount,
                'TRXDATE' => $trxdate,
            ]);

            TransaksiCashless::create([
                'siswa_id' => $siswa->id,
                'type' => 'topup',
                'category' => $fidbank === 'RFID' ? 'rfid' : 'manual',
                'amount' => $amount,
                'description' => $description,
            ]);

            // Infaq donation — separate ledger row + dompet decrement
            if ($infaqAmount > 0) {
                $infaqNoreff = 'TIF'.$trxdate->format('YmdHis');

                SccttranCashless::create([
                    'CUSTID' => $siswa->id,
                    'user_id' => $request->user()?->id,
                    'METODE' => 'INFAQ',
                    'TRXDATE' => $trxdate,
                    'KREDIT' => 0,
                    'DEBET' => $infaqAmount,
                    'FIDBANK' => $fidbank,
                    'NOREFF' => $infaqNoreff,
                    'description' => 'Infaq top-up manual',
                ]);

                Dompet::where('siswa_id', $siswa->id)
                    ->decrement('saldo_us', $infaqAmount);
            }
        });

        $siswa = Siswa::with('kelas')->findOrFail($siswa->id);

        $message = 'Top-up cashless '.ActionMessage::rupiah($amount).' berhasil';
        if ($infaqAmount > 0) {
            $message .= ' (infaq Rp '.number_format($infaqAmount, 0, ',', '.').')';
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject($message, ActionMessage::siswa($siswa)),
            [
                'balance' => $this->sccttranCashless->balanceForSiswa($siswa->id),
                'siswa' => $siswa,
                'infaq' => $infaqAmount,
            ],
            201
        );
    }

    public function lookupRfid(Request $request): JsonResponse
    {
        if (! config('school.manual_saldo_cashless_adjustment_enabled', false)) {
            return $this->jsonError('Top-up / tarik saldo cashless manual sementara dinonaktifkan.', null, 403);
        }

        $data = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:64'],
        ]);

        try {
            $siswa = $this->resolveSiswaByRfid($data['rfid_uid']);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Kartu RFID tidak dikenali.',
                $e->errors(),
                422
            );
        }

        $siswa->loadMissing('kelas');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Siswa ditemukan', ActionMessage::siswa($siswa)),
            [
                'siswa' => [
                    'id' => $siswa->id,
                    'nis' => $siswa->nis,
                    'name' => $siswa->name,
                    'kelas' => $siswa->kelas?->name,
                    'rfid_uid' => $siswa->rfidUid(),
                ],
                'balance' => $this->sccttranCashless->balanceForSiswa($siswa->id),
            ]
        );
    }

    public function withdrawPreview(Request $request): JsonResponse
    {
        if (! config('school.manual_saldo_cashless_adjustment_enabled', false)) {
            return $this->jsonError('Tarik saldo cashless manual sementara dinonaktifkan.', null, 403);
        }

        $data = $request->validate([
            'siswa_id' => ['nullable', SoftDeleteRules::exists('siswa')],
            'rfid_uid' => ['nullable', 'string', 'max:64'],
            'amount' => 'nullable|numeric',
        ]);

        try {
            $siswa = $this->resolveSiswaFromRequest($data);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak ditemukan.',
                $e->errors(),
                422
            );
        }

        $amount = (int) round((float) ($data['amount'] ?? 0));
        $summary = $this->cashlessGuard->dailySummary($siswa);
        $wouldExceed = $amount > 0 && $this->cashlessGuard->wouldExceedDailyLimit($siswa, $amount);
        $pinSet = $this->cashlessPin->isSet($siswa);

        return $this->jsonSuccess('OK', [
            'balance' => $this->sccttranCashless->balanceForSiswa($siswa->id),
            'daily_summary' => $summary,
            'would_exceed' => $wouldExceed,
            'pin_required' => $wouldExceed,
            'pin_set' => $pinSet,
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name,
            ],
        ]);
    }

    /**
     * Face references (siswa dengan foto wajah) untuk deteksi wajah pada tarik saldo.
     * Deskriptor wajah dibangun di browser (face-api.js). Scope sekolah operator otomatis
     * via OperatorSekolahScope pada model Siswa.
     */
    public function faceReferences(Request $request): JsonResponse
    {
        if (! config('school.manual_saldo_cashless_adjustment_enabled', false)) {
            return $this->jsonError('Tarik saldo cashless manual sementara dinonaktifkan.', null, 403);
        }

        $query = Siswa::query()
            ->select(['id', 'nis', 'name', 'kelas_id', 'updated_at'])
            ->where('has_foto_wajah', 1)
            ->whereIn('status', [Siswa::STATUS_ACTIVE, Siswa::STATUS_PENDING])
            ->with(['kelas:id,name', 'rfid'])
            ->orderBy('name');

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

        // Cap references — matcher dibangun client-side; set besar akan memberatkan browser.
        $students = $query->limit(500)->get();

        return $this->jsonSuccess('OK', [
            'total' => $students->count(),
            'students' => $students->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name ?? '-',
                'rfid_uid' => $siswa->rfidUid(),
                'photo_url' => route('admin.dompet-digital.withdraw-saldo.face-photo', $siswa).'?v='.($siswa->updated_at?->timestamp ?? $siswa->id),
            ])->values(),
        ]);
    }

    public function facePhoto(Siswa $siswa): Response
    {
        $operatorSekolahId = AdminSchoolScope::operatorSekolahId();
        abort_if($operatorSekolahId !== null && (int) $siswa->sekolah_id !== $operatorSekolahId, 404);
        abort_if(! $siswa->hasFotoWajah(), 404);

        return $this->respondFacePhoto($siswa);
    }

    public function withdraw(Request $request): JsonResponse
    {
        if (! config('school.manual_saldo_cashless_adjustment_enabled', false)) {
            return $this->jsonError('Tarik saldo cashless manual sementara dinonaktifkan.', null, 403);
        }

        $data = $request->validate([
            'siswa_id' => ['nullable', SoftDeleteRules::exists('siswa')],
            'rfid_uid' => ['nullable', 'string', 'max:64'],
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'pin' => CashlessPin::rules(required: false),
        ]);

        try {
            $siswa = $this->resolveSiswaFromRequest($data);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak ditemukan.',
                $e->errors(),
                422
            );
        }

        $amount = (int) round($data['amount']);
        if ($amount <= 0) {
            return $this->jsonError('Nominal tarik saldo harus lebih dari 0.', [
                'amount' => ['Nominal tarik saldo harus lebih dari 0.'],
            ]);
        }

        try {
            $siswa->assertCanTransact();
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak aktif.',
                $e->errors(),
                422
            );
        }

        $available = $this->sccttranCashless->balanceForSiswa($siswa->id);
        if ($available < $amount) {
            return $this->jsonError(
                'Saldo cashless tidak mencukupi. Tersedia: Rp '.number_format($available, 0, ',', '.').'.',
                ['amount' => ['Saldo cashless tidak mencukupi.']],
                422
            );
        }

        try {
            $this->cashlessGuard->assertWithdrawPin($siswa, $amount, $data['pin'] ?? null);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'PIN tidak valid.',
                $e->errors(),
                422
            );
        }

        $trxdate = now();
        // NOREFF is char(20): WD + YmdHis = 16 chars
        $noreff = 'WD'.$trxdate->format('YmdHis');
        $description = trim((string) ($data['description'] ?? ''));
        $description = $description !== '' ? $description : 'Tarik saldo manual';
        $viaRfid = filled(RfidUid::sanitize($data['rfid_uid'] ?? null));
        $fidbank = $viaRfid ? 'RFID' : 'CASH';

        DB::transaction(function () use ($request, $siswa, $amount, $trxdate, $noreff, $description, $fidbank): void {
            SccttranCashless::create([
                'CUSTID' => $siswa->id,
                'user_id' => $request->user()?->id,
                'METODE' => 'TARIK SALDO',
                'TRXDATE' => $trxdate,
                'KREDIT' => 0,
                'DEBET' => $amount,
                'FIDBANK' => $fidbank,
                'NOREFF' => $noreff,
                'description' => $description,
            ]);

            // Keep POS cache in sync (ledger has no wallet split).
            $this->decrementDompetBalance((int) $siswa->id, $amount);
        });

        $siswa = Siswa::with('kelas')->findOrFail($siswa->id);

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Tarik saldo cashless '.ActionMessage::rupiah($amount).' berhasil',
                ActionMessage::siswa($siswa)
            ),
            [
                'balance' => $this->sccttranCashless->balanceForSiswa($siswa->id),
                'siswa' => $siswa,
            ],
            201
        );
    }

    public function data(Request $request): JsonResponse
    {
        $query = SccttranCashless::query()
            ->with(['siswa.kelas', 'user'])
            ->where('METODE', 'TOP UP')
            ->where('KREDIT', '>', 0);

        $this->applyKelasFilter($query, $request);
        $this->applyDateRange($query, $request, 'TRXDATE');
        $this->applySiswaSearchFilter($query, $request);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['NOREFF', 'description', 'METODE', 'siswa.name', 'siswa.nis', 'user.name'],
            'orderable' => ['TRXDATE', 'TRXDATE', 'TRXDATE', 'TRXDATE', 'KREDIT', 'TRXDATE', 'NOREFF', 'TRXDATE'],
        ], function (SccttranCashless $row) {
            return [
                $this->dateCell($row->TRXDATE),
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $this->cell(
                    'Rp '.number_format((int) $row->KREDIT, 0, ',', '.'),
                    (int) $row->KREDIT,
                    'number'
                ),
                $row->description ?: '-',
                $row->NOREFF ?? '-',
                $row->user?->name ?? '-',
            ];
        });
    }

    /**
     * @param  array{siswa_id?: mixed, rfid_uid?: mixed}  $data
     *
     * @throws ValidationException
     */
    private function resolveSiswaFromRequest(array $data): Siswa
    {
        $rfidUid = RfidUid::sanitize($data['rfid_uid'] ?? null);
        $siswaId = filled($data['siswa_id'] ?? null) ? (int) $data['siswa_id'] : null;

        if ($rfidUid !== null) {
            return $this->resolveSiswaByRfid($rfidUid);
        }

        if ($siswaId === null) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Pilih siswa atau scan RFID terlebih dahulu.',
            ]);
        }

        $siswa = Siswa::query()->with('kelas')->find($siswaId);
        if (! $siswa) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa tidak ditemukan.',
            ]);
        }

        $this->assertSiswaInOperatorScope($siswa);

        return $siswa;
    }

    /**
     * @throws ValidationException
     */
    private function resolveSiswaByRfid(string $rfidUid): Siswa
    {
        $siswa = $this->cashlessGuard->findActiveStudentByRfid($rfidUid);

        if (! $siswa) {
            throw ValidationException::withMessages([
                'rfid_uid' => 'Kartu RFID tidak dikenali atau siswa tidak aktif.',
            ]);
        }

        if ($siswa->isRfidBlocked()) {
            throw ValidationException::withMessages([
                'rfid_uid' => 'Kartu RFID siswa diblokir. Hubungi admin untuk membuka blokir.',
            ]);
        }

        $this->assertSiswaInOperatorScope($siswa);

        return $siswa->loadMissing('kelas');
    }

    /**
     * @throws ValidationException
     */
    private function assertSiswaInOperatorScope(Siswa $siswa): void
    {
        $operatorSekolahId = AdminSchoolScope::operatorSekolahId();
        if ($operatorSekolahId !== null && (int) $siswa->sekolah_id !== $operatorSekolahId) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa tidak termasuk sekolah Anda.',
            ]);
        }
    }

    private function decrementDompetBalance(int $siswaId, int $amount): void
    {
        $dompet = Dompet::firstOrCreate(['siswa_id' => $siswaId]);
        $remaining = $amount;

        foreach (['saldo_us', 'saldo_kantin', 'saldo_tabungan'] as $field) {
            if ($remaining <= 0) {
                break;
            }

            $available = max(0, (int) round((float) $dompet->{$field}));
            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);
            $dompet->decrement($field, $take);
            $remaining -= $take;
        }

        if ($remaining > 0) {
            // Ledger had enough but cache lagged — still force us down so POS stays honest.
            $dompet->decrement('saldo_us', $remaining);
        }
    }
}
