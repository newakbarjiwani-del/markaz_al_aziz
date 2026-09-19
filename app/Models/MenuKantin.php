<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuKantin extends BaseModel
{
    protected $table = 'menu_kantin';

    protected $fillable = ['sekolah_id', 'name', 'price', 'category', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }
}
