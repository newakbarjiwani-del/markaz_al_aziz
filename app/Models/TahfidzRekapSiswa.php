<?php

namespace App\Models;

use App\Support\TahfidzJuzList;
use Database\Factories\TahfidzRekapSiswaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzRekapSiswa extends Model
{
    /** @use HasFactory<TahfidzRekapSiswaFactory> */
    use HasFactory;

    protected $table = 'tahfidz_rekap_siswa';

    protected $fillable = [
        'rekap_id',
        'halaqoh_id',
        'siswa_id',
        'tatsbit_juz',
        'murojaah_juz',
        'kehadiran_harian',
        'hadir_hari',
        'sakit_hari',
        'pulang_hari',
        'total_juz',
        'prestasi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tatsbit_juz' => 'array',
            'murojaah_juz' => 'array',
            'kehadiran_harian' => 'array',
            'hadir_hari' => 'integer',
            'sakit_hari' => 'integer',
            'pulang_hari' => 'integer',
            'total_juz' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TahfidzRekap, $this>
     */
    public function rekap(): BelongsTo
    {
        return $this->belongsTo(TahfidzRekap::class, 'rekap_id');
    }

    /**
     * @return BelongsTo<TahfidzHalaqoh, $this>
     */
    public function halaqoh(): BelongsTo
    {
        return $this->belongsTo(TahfidzHalaqoh::class, 'halaqoh_id');
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tatsbitLabel(): string
    {
        return TahfidzJuzList::format($this->tatsbit_juz ?? []);
    }

    public function murojaahLabel(): string
    {
        return TahfidzJuzList::format($this->murojaah_juz ?? []);
    }
}
