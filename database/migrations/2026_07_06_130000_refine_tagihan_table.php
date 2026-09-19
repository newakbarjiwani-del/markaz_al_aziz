<?php

use App\Support\TagihanPeriode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tagihan', 'periode')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->unsignedInteger('periode')->nullable()->after('due_date');
                $table->foreignId('tahun_akademik_id')->nullable()->after('siswa_id')->constrained('tahun_akademik')->nullOnDelete();
                $table->unsignedTinyInteger('status_int')->default(0)->after('paid');
            });
        }

        if (Schema::hasColumn('tagihan', 'status') && Schema::hasColumn('tagihan', 'period')) {
            DB::table('tagihan')->orderBy('id')->each(function ($row) {
                $paid = (float) $row->paid;
                $amount = (float) $row->amount;
                $statusInt = ($row->status === 'lunas' || ($amount > 0 && $paid >= $amount)) ? 1 : 0;

                $tahunAkademikId = DB::table('tahun_akademik')
                    ->where('sekolah_id', $row->sekolah_id)
                    ->where('is_active', true)
                    ->value('id');

                DB::table('tagihan')->where('id', $row->id)->update([
                    'status_int' => $statusInt,
                    'periode' => $this->migratePeriode($row->period ?? null, $row->created_at),
                    'tahun_akademik_id' => $tahunAkademikId,
                    'paid' => $statusInt === 1 ? $amount : 0,
                ]);
            });

            Schema::table('tagihan', function (Blueprint $table) {
                $table->dropColumn(['period', 'status']);
            });
        }

        if (Schema::hasColumn('tagihan', 'status_int')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->renameColumn('status_int', 'status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tagihan', 'status_int') && Schema::hasColumn('tagihan', 'status')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->renameColumn('status', 'status_int');
            });
        }

        Schema::table('tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan', 'status')) {
                $table->string('status')->default('belum_lunas')->after('paid');
            }
            if (! Schema::hasColumn('tagihan', 'period')) {
                $table->string('period')->nullable()->after('due_date');
            }
        });

        DB::table('tagihan')->orderBy('id')->each(function ($row) {
            $status = ((int) ($row->status_int ?? $row->status ?? 0)) === 1 ? 'lunas' : 'belum_lunas';

            DB::table('tagihan')->where('id', $row->id)->update([
                'status' => $status,
                'period' => $row->periode ? TagihanPeriode::display((int) $row->periode) : null,
            ]);
        });

        Schema::table('tagihan', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan', 'status_int')) {
                $table->dropColumn('status_int');
            }
            if (Schema::hasColumn('tagihan', 'periode')) {
                $table->dropColumn('periode');
            }
            if (Schema::hasColumn('tagihan', 'tahun_akademik_id')) {
                $table->dropConstrainedForeignId('tahun_akademik_id');
            }
        });
    }

    private function migratePeriode(?string $period, ?string $createdAt): int
    {
        if ($period) {
            $normalized = TagihanPeriode::normalize($period);
            if ($normalized !== null) {
                return $normalized;
            }

            $lower = strtolower($period);
            $month = null;

            foreach (['januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12] as $name => $number) {
                if (str_contains($lower, $name)) {
                    $month = $number;
                    break;
                }
            }

            if ($month !== null && preg_match('/(\d{4})\/(\d{4})/', $period, $matches)) {
                $calendarYear = $month >= 7 ? (int) $matches[1] : (int) $matches[2];

                return TagihanPeriode::fromCalendarMonth($calendarYear, $month);
            }

            if ($month !== null && preg_match('/(\d{4})/', $period, $matches)) {
                return TagihanPeriode::fromCalendarMonth((int) $matches[1], $month);
            }
        }

        $timestamp = strtotime($createdAt ?? 'now');

        return TagihanPeriode::fromCalendarMonth((int) date('Y', $timestamp), (int) date('n', $timestamp));
    }
};
