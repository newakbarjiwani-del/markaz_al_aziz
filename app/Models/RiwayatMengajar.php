<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatMengajar extends BaseModel
{
    protected $table = 'riwayat_mengajar';

    protected $fillable = ['guru_id', 'subject', 'class_name', 'year'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
}
