<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogTagihanEdit extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'log_tagihan_edit';

    protected $fillable = [
        'tagihan_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

