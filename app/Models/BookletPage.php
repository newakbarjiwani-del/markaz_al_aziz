<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookletPage extends BaseModel
{
    protected $table = 'booklet_page';

    protected $fillable = [
        'booklet_id',
        'sort_order',
        'title',
        'body',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function booklet(): BelongsTo
    {
        return $this->belongsTo(Booklet::class, 'booklet_id');
    }
}
