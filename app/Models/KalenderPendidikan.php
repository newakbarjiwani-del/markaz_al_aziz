<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalenderPendidikan extends BaseModel
{
    public const JENIS_EFEKTIF = 'efektif';

    public const JENIS_LIBUR = 'libur';

    public const JENIS_UJIAN = 'ujian';

    public const JENIS_KEGIATAN = 'kegiatan';

    protected $table = 'kalender_pendidikan';

    protected $fillable = [
        'sekolah_id',
        'tahun_akademik_id',
        'starts_on',
        'ends_on',
        'jenis',
        'name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    /**
     * @return array<string, string>
     */
    public static function jenisLabels(): array
    {
        return [
            self::JENIS_EFEKTIF => 'Hari efektif',
            self::JENIS_LIBUR => 'Libur',
            self::JENIS_UJIAN => 'Ujian',
            self::JENIS_KEGIATAN => 'Kegiatan',
        ];
    }

    public function jenisLabel(): string
    {
        return self::jenisLabels()[$this->jenis] ?? $this->jenis;
    }
}
