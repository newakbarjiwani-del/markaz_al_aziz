<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KartuGuru extends BaseModel
{
    protected $table = 'kartu_guru';

    protected $fillable = ['guru_id', 'status'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public static function provisionFor(Guru $guru): self
    {
        return static::firstOrCreate(
            ['guru_id' => $guru->id],
            ['status' => 'aktif']
        );
    }
}
