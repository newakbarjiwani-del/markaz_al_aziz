<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogSiswaEdit extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'log_siswa_edit';

    protected $fillable = [
        'siswa_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
