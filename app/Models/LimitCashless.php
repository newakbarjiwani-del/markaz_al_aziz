<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitCashless extends BaseModel
{
    protected $table = 'limit_cashless';

    protected $fillable = [
        'sekolah_id', 'type', 'target', 'category',
        'daily_limit', 'monthly_limit', 'blocked_categories',
    ];

    protected function casts(): array
    {
        return [
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'blocked_categories' => 'array',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }
}
