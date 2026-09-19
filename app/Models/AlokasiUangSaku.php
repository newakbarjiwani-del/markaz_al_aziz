<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokasiUangSaku extends BaseModel
{
    protected $table = 'alokasi_uang_saku';

    protected $fillable = ['siswa_id', 'amount', 'period', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
