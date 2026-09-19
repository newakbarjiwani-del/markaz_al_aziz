<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekeningBank extends BaseModel
{
    protected $table = 'rekening_bank';

    protected $fillable = ['sekolah_id', 'bank', 'account_number', 'account_name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }
}
