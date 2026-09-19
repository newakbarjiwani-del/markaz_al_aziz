<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Sekolah extends BaseModel
{
    protected $table = 'sekolah';

    protected $fillable = ['code', 'name', 'address', 'phone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sekolah_id');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'sekolah_id');
    }

    public function guru(): HasMany
    {
        return $this->hasMany(Guru::class, 'sekolah_id');
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'sekolah_id');
    }

    public function tahunAkademik(): HasMany
    {
        return $this->hasMany(TahunAkademik::class, 'sekolah_id');
    }
}
