<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzMurajaahLog extends Model
{
    protected $table = 'tahfidz_murajaah_log';

    protected $fillable = [
        'progress_id',
        'reviewed_at',
        'note',
        'source',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TahfidzProgress, $this>
     */
    public function progress(): BelongsTo
    {
        return $this->belongsTo(TahfidzProgress::class, 'progress_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
