<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilGuru extends BaseModel
{
    protected $table = 'profil_guru';

    protected $fillable = ['guru_id', 'photo_path', 'address', 'extra_fields'];

    protected function casts(): array
    {
        return ['extra_fields' => 'array'];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }
}
