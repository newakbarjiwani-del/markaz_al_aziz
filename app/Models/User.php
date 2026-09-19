<?php

namespace App\Models;

use App\Support\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const STATUS_DISABLED = UserStatus::DISABLED;

    public const STATUS_ACTIVE = UserStatus::ACTIVE;

    protected $fillable = [
        'username',
        'sekolah_id',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'siswa_id',
        'guru_id',
        'orang_tua_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'integer',
        ];
    }

    public function setStatusAttribute(mixed $value): void
    {
        $this->attributes['status'] = UserStatus::normalize($value) ?? UserStatus::ACTIVE;
    }

    public function canLogin(): bool
    {
        return UserStatus::canLogin($this->status);
    }

    public function statusLabel(): string
    {
        return UserStatus::label($this->status);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(OrangTua::class, 'orang_tua_id');
    }

    public function cashlessTransactions(): HasMany
    {
        return $this->hasMany(SccttranCashless::class, 'user_id');
    }

    public function penarikanPendapatanKantin(): HasMany
    {
        return $this->hasMany(PenarikanPendapatanKantin::class, 'kantin_user_id');
    }

    /**
     * @deprecated No longer used anywhere in the codebase. Previously answered
     *             "does this user hold a portal (identity) role?" before portal
     *             roles were re-scoped as identity-scoped (see
     *             AdminSchoolScope::OPERATOR_ROLES). Kept only for reference —
     *             do not rely on it.
     */
    public function isPortalUser(): bool
    {
        return $this->hasAnyRole(['guru', 'orang_tua', 'siswa', 'kantin']);
    }
}
