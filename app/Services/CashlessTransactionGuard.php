<?php

namespace App\Services;

use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Support\CashlessPin;
use App\Support\CashlessSettings;
use Illuminate\Validation\ValidationException;

class CashlessTransactionGuard
{
    public function __construct(
        private readonly RfidResolver $rfidResolver,
    ) {}

    public function findActiveStudentByRfid(string $rfidUid): ?Siswa
    {
        return $this->rfidResolver->findSiswa($rfidUid);
    }

    /**
     * @throws ValidationException
     */
    public function assertCanSpend(Siswa $siswa, float $amount): void
    {
        if (! $siswa->hasRfid()) {
            throw ValidationException::withMessages([
                'rfid_uid' => 'Siswa belum memiliki RFID cashless.',
            ]);
        }

        if ($siswa->isRfidBlocked()) {
            throw ValidationException::withMessages([
                'rfid_uid' => 'Kartu RFID siswa diblokir. Hubungi admin sekolah.',
            ]);
        }

        if (! $siswa->canTransact()) {
            throw ValidationException::withMessages([
                'rfid_uid' => 'Siswa tidak aktif. Transaksi cashless tidak diizinkan.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal transaksi harus lebih dari nol.',
            ]);
        }

        if ($this->wouldExceedDailyLimit($siswa, $amount)) {
            $summary = $this->dailySummary($siswa);
            $spentToday = $summary['spent_today'];

            if ($summary['student_limit'] !== null && ($spentToday + $amount) > $summary['student_limit']) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Melebihi limit harian siswa (Rp %s). Tersisa Rp %s hari ini.',
                        number_format($summary['student_limit'], 0, ',', '.'),
                        number_format($summary['remaining_student'] ?? 0, 0, ',', '.')
                    ),
                ]);
            }

            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Melebihi limit harian sekolah (Rp %s). Tersisa Rp %s hari ini.',
                    number_format((float) ($summary['global_limit'] ?? 0), 0, ',', '.'),
                    number_format($summary['remaining_global'] ?? 0, 0, ',', '.')
                ),
            ]);
        }
    }

    /**
     * Whether spending/withdrawing $amount today would exceed the student or school daily limit.
     */
    public function wouldExceedDailyLimit(Siswa $siswa, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $spentToday = $this->spentToday($siswa);
        $projected = $spentToday + $amount;

        if ($siswa->daily_transaction_limit !== null && $projected > (float) $siswa->daily_transaction_limit) {
            return true;
        }

        $globalLimit = CashlessSettings::dailyTransactionLimit((int) $siswa->sekolah_id);

        return $globalLimit !== null && $projected > $globalLimit;
    }

    /**
     * For admin Tarik Saldo: allow over-limit only with a valid 4-digit cashless PIN.
     *
     * @throws ValidationException
     */
    public function assertWithdrawPin(Siswa $siswa, float $amount, ?string $pin): void
    {
        if (! $this->wouldExceedDailyLimit($siswa, $amount)) {
            return;
        }

        if (! CashlessPin::isSet($siswa->cashless_pin)) {
            throw ValidationException::withMessages([
                'pin' => 'Tarik saldo melebihi limit harian. Set PIN cashless siswa terlebih dahulu.',
            ]);
        }

        $pin = $pin !== null ? trim($pin) : '';

        if ($pin === '') {
            throw ValidationException::withMessages([
                'pin' => 'PIN wajib diisi karena nominal melebihi limit harian.',
            ]);
        }

        if (! preg_match('/^\d{4}$/', $pin)) {
            throw ValidationException::withMessages([
                'pin' => 'PIN harus 4 digit angka.',
            ]);
        }

        if (! CashlessPin::verify($pin, $siswa->cashless_pin)) {
            throw ValidationException::withMessages([
                'pin' => 'PIN salah.',
            ]);
        }
    }

    public function spentToday(Siswa $siswa): float
    {
        return (float) SccttranCashless::query()
            ->where('CUSTID', $siswa->id)
            ->where('DEBET', '>', 0)
            ->whereDate('TRXDATE', today())
            ->sum('DEBET');
    }

    /**
     * @return array{
     *     spent_today: float,
     *     student_limit: float|null,
     *     global_limit: float|null,
     *     remaining_student: float|null,
     *     remaining_global: float|null
     * }
     */
    public function dailySummary(Siswa $siswa): array
    {
        $spentToday = $this->spentToday($siswa);
        $studentLimit = $siswa->daily_transaction_limit !== null
            ? (float) $siswa->daily_transaction_limit
            : null;
        $globalLimit = CashlessSettings::dailyTransactionLimit((int) $siswa->sekolah_id);

        return [
            'spent_today' => $spentToday,
            'student_limit' => $studentLimit,
            'global_limit' => $globalLimit,
            'remaining_student' => $studentLimit !== null ? max(0, $studentLimit - $spentToday) : null,
            'remaining_global' => $globalLimit !== null ? max(0, $globalLimit - $spentToday) : null,
        ];
    }
}
