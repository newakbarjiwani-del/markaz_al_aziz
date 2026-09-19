<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilSiswa extends BaseModel
{
    protected $table = 'profil_siswa';

    protected $fillable = ['siswa_id', 'photo_path', 'extra_fields'];

    protected function casts(): array
    {
        return ['extra_fields' => 'array'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }
}
