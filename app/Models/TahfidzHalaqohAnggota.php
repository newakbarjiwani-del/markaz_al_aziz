<?php

namespace App\Models;

use Database\Factories\TahfidzHalaqohAnggotaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzHalaqohAnggota extends Model
{
    /** @use HasFactory<TahfidzHalaqohAnggotaFactory> */
    use HasFactory;

    protected $table = 'tahfidz_halaqoh_anggota';

    protected $fillable = [
        'halaqoh_id',
        'siswa_id',
        'total_juz',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_juz' => 'integer',
        ];
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
}
