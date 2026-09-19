<?php

namespace App\Models;

use App\Support\TagihanPesanKategori;
use Illuminate\Database\Eloquent\Builder;

class TemplatePesanTagihan extends BaseModel
{
    protected $table = 'template_pesan_tagihan';

    protected $fillable = [
        'nama',
        'kategori',
        'isi_pesan',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForKategori(Builder $query, string $kategori): Builder
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('nama');
    }

    public function kategoriLabel(): string
    {
        return TagihanPesanKategori::label($this->kategori);
    }
}
