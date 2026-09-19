<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzAyat extends Model
{
    protected $table = 'tahfidz_ayat';

    protected $fillable = [
        'surah_id',
        'ayah_number',
        'text_ar',
        'text_id',
        'juz',
        'page',
    ];

    /**
     * @return BelongsTo<TahfidzSurah, $this>
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(TahfidzSurah::class, 'surah_id');
    }
}
