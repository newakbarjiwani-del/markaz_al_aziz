<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahfidzSurah extends Model
{
    protected $table = 'tahfidz_surah';

    protected $fillable = [
        'number',
        'name_ar',
        'name_id',
        'ayah_count',
        'revelation_type',
    ];

    /**
     * @return HasMany<TahfidzAyat, $this>
     */
    public function ayat(): HasMany
    {
        return $this->hasMany(TahfidzAyat::class, 'surah_id')->orderBy('ayah_number');
    }

    public function label(): string
    {
        return $this->number.'. '.$this->name_id;
    }
}
