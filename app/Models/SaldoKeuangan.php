<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoKeuangan extends BaseModel
{
    protected $table = 'saldo_keuangan';

    protected $fillable = ['siswa_id', 'balance'];

    protected function casts(): array
    {
        return ['balance' => 'decimal:2'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
