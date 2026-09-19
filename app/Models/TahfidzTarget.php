<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzTarget extends BaseModel
{
    protected $table = 'tahfidz_target';

    protected $fillable = [
        'siswa_id',
        'sekolah_id',
        'range_type',
        'juz',
        'surah_id',
        'ayah_from',
        'ayah_to',
        'period',
        'due_date',
        'assigned_by',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * @return BelongsTo<TahfidzSurah, $this>
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(TahfidzSurah::class, 'surah_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function rangeLabel(): string
    {
        if ($this->range_type === 'juz') {
            return 'Juz '.$this->juz;
        }

        $surah = $this->surah?->label() ?? 'Surah';

        return $surah.' ayat '.$this->ayah_from.'–'.$this->ayah_to;
    }
}
