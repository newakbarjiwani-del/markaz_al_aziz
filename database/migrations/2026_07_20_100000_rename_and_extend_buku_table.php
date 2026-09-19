<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('buku')) {
            return;
        }

        Schema::table('buku', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->unsignedBigInteger('sekolah_id')->nullable()->change();
            $table->foreign('sekolah_id')->references('id')->on('sekolah')->nullOnDelete();
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->renameColumn('title', 'judul');
            $table->renameColumn('author', 'pengarang');
            $table->renameColumn('stock', 'jumlah');
            $table->renameColumn('available', 'tersedia');
            $table->renameColumn('category', 'kategori');
            $table->renameColumn('rating', 'nilai_rata');
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->string('isbn_key')->nullable()->after('isbn');
            $table->string('kode_buku')->nullable()->after('isbn_key');
            $table->string('penerbit')->nullable()->after('pengarang');
            $table->unsignedSmallInteger('tahun_terbit')->nullable()->after('kategori');
            $table->unsignedTinyInteger('cetak_ke')->default(1)->after('tahun_terbit');
            $table->unsignedInteger('keadaan_baik')->default(0)->after('jumlah');
            $table->unsignedInteger('keadaan_rusak_ringan')->default(0)->after('keadaan_baik');
            $table->unsignedInteger('keadaan_rusak_berat')->default(0)->after('keadaan_rusak_ringan');
            $table->date('tanggal_penerimaan')->nullable()->after('nilai_rata');
            $table->string('sumber_dana')->nullable()->after('tanggal_penerimaan');
            $table->text('keterangan')->nullable()->after('sumber_dana');
        });

        DB::table('buku')->whereNull('deleted_at')->update([
            'keadaan_baik' => DB::raw('jumlah'),
            'keadaan_rusak_ringan' => 0,
            'keadaan_rusak_berat' => 0,
            'cetak_ke' => 1,
        ]);

        DB::table('buku')
            ->whereNull('deleted_at')
            ->whereNotNull('isbn')
            ->where('isbn', '!=', '')
            ->update(['isbn_key' => DB::raw('isbn')]);

        $this->dedupeIsbnKeys();

        Schema::table('buku', function (Blueprint $table) {
            $table->unique('isbn_key');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('buku')) {
            return;
        }

        Schema::table('buku', function (Blueprint $table) {
            $table->dropUnique(['isbn_key']);
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->dropColumn([
                'isbn_key',
                'kode_buku',
                'penerbit',
                'tahun_terbit',
                'cetak_ke',
                'keadaan_baik',
                'keadaan_rusak_ringan',
                'keadaan_rusak_berat',
                'tanggal_penerimaan',
                'sumber_dana',
                'keterangan',
            ]);
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->renameColumn('judul', 'title');
            $table->renameColumn('pengarang', 'author');
            $table->renameColumn('jumlah', 'stock');
            $table->renameColumn('tersedia', 'available');
            $table->renameColumn('kategori', 'category');
            $table->renameColumn('nilai_rata', 'rating');
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->unsignedBigInteger('sekolah_id')->nullable(false)->change();
            $table->foreign('sekolah_id')->references('id')->on('sekolah')->cascadeOnDelete();
        });
    }

    private function dedupeIsbnKeys(): void
    {
        $duplicates = DB::table('buku')
            ->select('isbn_key')
            ->whereNull('deleted_at')
            ->whereNotNull('isbn_key')
            ->groupBy('isbn_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('isbn_key');

        foreach ($duplicates as $isbnKey) {
            $keepId = DB::table('buku')
                ->whereNull('deleted_at')
                ->where('isbn_key', $isbnKey)
                ->orderBy('id')
                ->value('id');

            DB::table('buku')
                ->whereNull('deleted_at')
                ->where('isbn_key', $isbnKey)
                ->where('id', '!=', $keepId)
                ->update(['isbn_key' => null]);
        }
    }
};
