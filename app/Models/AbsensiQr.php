<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AbsensiQr extends BaseModel
{
    protected $table = 'absensi_qr';

    protected $fillable = [
        'sekolah_id', 'checkinable_type', 'checkinable_id', 'checked_in_at', 'source',
    ];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function checkinable(): MorphTo
    {
        return $this->morphTo();
    }
}
