<?php

namespace App\Models;

use App\Models\SccttranCashless;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dompet extends BaseModel
{
    protected $table = 'dompet';

    protected $fillable = ['siswa_id', 'saldo_us', 'saldo_kantin', 'saldo_tabungan'];

    protected function casts(): array
    {
        return [
            'saldo_us' => 'decimal:2',
            'saldo_kantin' => 'decimal:2',
            'saldo_tabungan' => 'decimal:2',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiCashless::class, 'siswa_id', 'siswa_id');
    }

    public function sccttran(): HasMany
    {
        return $this->hasMany(SccttranCashless::class, 'CUSTID', 'siswa_id');
    }
}
