<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogLogin extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'log_login';

    protected $fillable = [
        'user_id',
        'method',
        'status',
        'identifier',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'device',
        'portal_access_token_id',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function portalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PortalAccessToken::class);
    }
}
