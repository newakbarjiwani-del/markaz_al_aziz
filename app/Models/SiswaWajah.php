<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaWajah extends Model
{
    protected $table = 'siswa_wajah';

    protected $fillable = [
        'siswa_id',
        'foto_wajah',
    ];

    protected $hidden = [
        'foto_wajah',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
