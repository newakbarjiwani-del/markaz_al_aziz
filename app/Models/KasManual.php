<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasManual extends BaseModel
{
    protected $table = 'kas_manual';

    protected $fillable = ['sekolah_id', 'tanggal', 'kategori', 'deskripsi', 'kredit', 'debet', 'created_by'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'kredit' => 'integer',
            'debet' => 'integer',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
