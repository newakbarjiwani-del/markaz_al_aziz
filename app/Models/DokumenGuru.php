<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenGuru extends BaseModel
{
    protected $table = 'dokumen_guru';

    protected $fillable = ['guru_id', 'title', 'file_path'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
}
