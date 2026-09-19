<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalAccessToken extends Model
{
    protected $table = 'portal_access_tokens';

    protected $fillable = [
        'user_id',
        'role',
        'token',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    /** Subquery: latest active ortu token expiry for orang tua linked to a siswa row. */
    public static function activeExpiresSubqueryForLinkedOrangTua(string $siswaTable = 'siswa'): Builder
    {
        return static::query()
            ->select('portal_access_tokens.expires_at')
            ->join('users', 'users.id', '=', 'portal_access_tokens.user_id')
            ->join('orang_tua_siswa', 'orang_tua_siswa.orang_tua_id', '=', 'users.orang_tua_id')
            ->whereColumn('orang_tua_siswa.siswa_id', "{$siswaTable}.id")
            ->where('portal_access_tokens.role', 'orang_tua')
            ->whereNull('portal_access_tokens.revoked_at')
            ->where('portal_access_tokens.expires_at', '>', now())
            ->whereNull('users.deleted_at')
            ->orderByDesc('portal_access_tokens.id')
            ->limit(1);
    }

    /** Subquery: latest active token expiry for a portal user linked on users.{foreignKey}. */
    public static function activeExpiresSubquery(string $userForeignKey, string $parentTable, string $role): Builder
    {
        return static::query()
            ->select('portal_access_tokens.expires_at')
            ->join('users', 'users.id', '=', 'portal_access_tokens.user_id')
            ->whereColumn("users.{$userForeignKey}", "{$parentTable}.id")
            ->where('portal_access_tokens.role', $role)
            ->whereNull('portal_access_tokens.revoked_at')
            ->where('portal_access_tokens.expires_at', '>', now())
            ->whereNull('users.deleted_at')
            ->orderByDesc('portal_access_tokens.id')
            ->limit(1);
    }
}
