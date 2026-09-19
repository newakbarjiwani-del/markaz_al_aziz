<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Buku extends BaseModel
{
    protected $table = 'buku';

    protected $fillable = [
        'sekolah_id',
        'isbn',
        'isbn_key',
        'kode_buku',
        'judul',
        'pengarang',
        'penerbit',
        'kategori',
        'tahun_terbit',
        'cetak_ke',
        'jumlah',
        'keadaan_baik',
        'keadaan_rusak_ringan',
        'keadaan_rusak_berat',
        'tersedia',
        'nilai_rata',
        'tanggal_penerimaan',
        'sumber_dana',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_penerimaan' => 'date',
            'tahun_terbit' => 'integer',
            'cetak_ke' => 'integer',
            'jumlah' => 'integer',
            'keadaan_baik' => 'integer',
            'keadaan_rusak_ringan' => 'integer',
            'keadaan_rusak_berat' => 'integer',
            'tersedia' => 'integer',
            'nilai_rata' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Buku $buku): void {
            $buku->isbn_key = $buku->deleted_at !== null || ! filled($buku->isbn)
                ? null
                : $buku->isbn;

            if ($buku->cetak_ke === null) {
                $buku->cetak_ke = 1;
            }
        });

        static::deleted(function (Buku $buku): void {
            if (! $buku->trashed()) {
                return;
            }

            DB::table('buku')
                ->where('id', $buku->id)
                ->update(['isbn_key' => null]);
        });

        static::restoring(function (Buku $buku): void {
            $buku->isbn_key = filled($buku->isbn) ? $buku->isbn : null;
        });
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanBuku::class, 'buku_id');
    }

    public function totalKeadaan(): int
    {
        return (int) $this->keadaan_baik
            + (int) $this->keadaan_rusak_ringan
            + (int) $this->keadaan_rusak_berat;
    }

    public function activeLoanCount(): int
    {
        return (int) $this->peminjaman()
            ->where('status', PeminjamanBuku::STATUS_DIPINJAM)
            ->sum('qty');
    }

    public function syncTersediaFromLoans(): void
    {
        $this->tersedia = max(0, (int) $this->keadaan_baik - $this->activeLoanCount());
    }
}
