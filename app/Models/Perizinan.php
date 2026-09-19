<?php

namespace App\Models;

use App\Models\Scopes\OperatorSekolahScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

#[ScopedBy([OperatorSekolahScope::class])]
class Perizinan extends BaseModel
{
    protected $table = 'perizinan';

    public const JENIS_KELUAR_MASUK = 'keluar_masuk';
    public const JENIS_KELUAR_MASUK_PONDOK = 'keluar_masuk_pondok';
    public const JENIS_PULANG_LIBUR = 'pulang_libur';

    public const STATUS_PENDING = 'pending';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_KEMBALI = 'kembali';
    public const STATUS_TERLAMBAT = 'terlambat';

    protected $fillable = [
        'sekolah_id',
        'siswa_id',
        'jenis_perizinan',
        'alasan',
        'tgl_mulai',
        'tgl_sampai',
        'tgl_kembali_aktual',
        'penanggung_jawab',
        'pemberi_izin',
        'status',
        'catatan',
        'file_path',
        'approved_by',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Perizinan $perizinan) {
            if ($perizinan->file_path && Storage::disk('public')->exists($perizinan->file_path)) {
                Storage::disk('public')->delete($perizinan->file_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'datetime',
            'tgl_sampai' => 'datetime',
            'tgl_kembali_aktual' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function scopeMatchingSiswa(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->whereHas('siswa', function (Builder $siswaQuery) use ($like) {
            $siswaQuery->where(function (Builder $inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('nis', 'like', $like);
            });
        });
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis_perizinan) {
            self::JENIS_KELUAR_MASUK => 'Izin Keluar Masuk',
            self::JENIS_KELUAR_MASUK_PONDOK => 'Izin Keluar Masuk Pondok',
            self::JENIS_PULANG_LIBUR => 'Izin Pulang Libur',
            default => ucfirst(str_replace('_', ' ', $this->jenis_perizinan)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        // Auto-check terlambat if status is disetujui and current time past tgl_sampai
        $effectiveStatus = $this->status;
        if ($effectiveStatus === self::STATUS_DISETUJUI && ! $this->tgl_kembali_aktual && Carbon::now()->greaterThan($this->tgl_sampai)) {
            $effectiveStatus = self::STATUS_TERLAMBAT;
        }

        return match ($effectiveStatus) {
            self::STATUS_PENDING => '<span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Menunggu</span>',
            self::STATUS_DISETUJUI => '<span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Disetujui</span>',
            self::STATUS_DITOLAK => '<span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10">Ditolak</span>',
            self::STATUS_KEMBALI => '<span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Sudah Kembali</span>',
            self::STATUS_TERLAMBAT => '<span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">Terlambat</span>',
            default => '<span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">' . e($this->status) . '</span>',
        };
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return asset('storage/' . ltrim($this->file_path, '/'));
    }

    public function getFileNameAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return basename($this->file_path);
    }

    public function pemberiIzinLabel(): string
    {
        $custom = trim((string) ($this->pemberi_izin ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return $this->approvedBy?->name
            ?? $this->createdBy?->name
            ?? '-';
    }
}
