<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split peminjaman_buku into a 1-to-N structure.
 *
 * Before: all transaction metadata (borrower, dates, notes) is duplicated
 *         on every row of peminjaman_buku.
 *
 * After:
 *   peminjaman      — one row per borrow transaction
 *                     holds: borrower identity, dates, catatan, processed_by
 *   peminjaman_buku — one row per book copy borrowed in that transaction
 *                     holds: buku_id, status, kondisi, fine, return_date, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Create parent table.
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->string('borrower_type', 20)->default('siswa');
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('guru')->nullOnDelete();
            $table->string('tamu_nama')->nullable();
            $table->string('tamu_asal')->nullable();
            $table->string('tamu_telepon', 30)->nullable();
            $table->date('loan_date');
            $table->date('due_date');
            $table->text('catatan_pinjam')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Add peminjaman_id FK to peminjaman_buku (nullable so we can backfill).
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->foreignId('peminjaman_id')
                ->nullable()
                ->after('id')
                ->constrained('peminjaman')
                ->cascadeOnDelete();
        });

        // 3. Backfill: for each existing peminjaman_buku row, create a peminjaman parent row.
        //    We group records by borrower to deduplicate where multiple books were borrowed
        //    in the same transaction. Because the old schema had no transaction identifier,
        //    we treat each row independently (one parent per child) to be safe.
        DB::table('peminjaman_buku')->orderBy('id')->eachById(function ($row) {
            $parentId = DB::table('peminjaman')->insertGetId([
                'borrower_type'         => $row->borrower_type,
                'siswa_id'              => $row->siswa_id,
                'guru_id'               => $row->guru_id,
                'tamu_nama'             => $row->tamu_nama,
                'tamu_asal'             => $row->tamu_asal,
                'tamu_telepon'          => $row->tamu_telepon,
                'loan_date'             => $row->loan_date,
                'due_date'              => $row->due_date,
                'catatan_pinjam'        => $row->catatan_pinjam,
                'processed_by_user_id'  => $row->processed_by_user_id,
                'created_at'            => $row->created_at,
                'updated_at'            => $row->updated_at,
            ]);

            DB::table('peminjaman_buku')
                ->where('id', $row->id)
                ->update(['peminjaman_id' => $parentId]);
        });

        // 4. Make peminjaman_id non-nullable after backfill.
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropForeign(['peminjaman_id']);
        });
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->unsignedBigInteger('peminjaman_id')->nullable(false)->change();
            $table->foreign('peminjaman_id')->references('id')->on('peminjaman')->cascadeOnDelete();
        });

        // 5. Drop parent-level fields from peminjaman_buku (now in peminjaman table).
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->dropForeign(['guru_id']);
            $table->dropColumn([
                'borrower_type',
                'siswa_id',
                'guru_id',
                'tamu_nama',
                'tamu_asal',
                'tamu_telepon',
                'loan_date',
                'due_date',
                'catatan_pinjam',
            ]);
        });
    }

    public function down(): void
    {
        // 1. Re-add parent fields to peminjaman_buku.
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->string('borrower_type', 20)->default('siswa')->after('buku_id');
            $table->foreignId('siswa_id')->nullable()->after('borrower_type')->constrained('siswa')->nullOnDelete();
            $table->foreignId('guru_id')->nullable()->after('siswa_id')->constrained('guru')->nullOnDelete();
            $table->string('tamu_nama')->nullable()->after('guru_id');
            $table->string('tamu_asal')->nullable()->after('tamu_nama');
            $table->string('tamu_telepon', 30)->nullable()->after('tamu_asal');
            $table->date('loan_date')->nullable()->after('tamu_telepon');
            $table->date('due_date')->nullable()->after('loan_date');
            $table->text('catatan_pinjam')->nullable()->after('fine_amount');
            $table->foreignId('processed_by_user_id')->nullable()->after('perpanjangan_count')->constrained('users')->nullOnDelete();
        });

        // 2. Restore parent data into child rows from the peminjaman table.
        DB::table('peminjaman_buku')
            ->join('peminjaman', 'peminjaman_buku.peminjaman_id', '=', 'peminjaman.id')
            ->orderBy('peminjaman_buku.id')
            ->select('peminjaman_buku.id', 'peminjaman.*')
            ->eachById(function ($row) {
                DB::table('peminjaman_buku')->where('id', $row->id)->update([
                    'borrower_type'        => $row->borrower_type,
                    'siswa_id'             => $row->siswa_id,
                    'guru_id'              => $row->guru_id,
                    'tamu_nama'            => $row->tamu_nama,
                    'tamu_asal'            => $row->tamu_asal,
                    'tamu_telepon'         => $row->tamu_telepon,
                    'loan_date'            => $row->loan_date,
                    'due_date'             => $row->due_date,
                    'catatan_pinjam'       => $row->catatan_pinjam,
                    'processed_by_user_id' => $row->processed_by_user_id,
                ]);
            }, 'peminjaman_buku.id', 100);

        // 3. Drop the FK and peminjaman_id column.
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropConstrainedForeignId('peminjaman_id');
        });

        // 4. Drop the parent table.
        Schema::dropIfExists('peminjaman');
    }
};
