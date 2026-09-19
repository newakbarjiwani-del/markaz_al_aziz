<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiEntry extends BaseModel
{
    public const JENIS_HARIAN = 'harian';

    public const JENIS_TUGAS = 'tugas';

    public const JENIS_UTS = 'uts';

    public const JENIS_UAS = 'uas';

    public const JENIS_PRAKTIK = 'praktik';

    protected $table = 'nilai_entry';

    protected $fillable = [
        'siswa_id',
        'mata_pelajaran_id',
        'tahun_akademik_id',
        'semester',
        'jenis',
        'kompetensi_dasar_id',
        'skor',
        'catatan',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return ['skor' => 'decimal:2'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function kompetensiDasar(): BelongsTo
    {
        return $this->belongsTo(KompetensiDasar::class, 'kompetensi_dasar_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return array<string, string>
     */
    public static function jenisLabels(): array
    {
        return [
            self::JENIS_HARIAN => 'Harian',
            self::JENIS_TUGAS => 'Tugas',
            self::JENIS_UTS => 'UTS',
            self::JENIS_UAS => 'UAS',
            self::JENIS_PRAKTIK => 'Praktik',
        ];
    }

    public function jenisLabel(): string
    {
        return self::jenisLabels()[$this->jenis] ?? $this->jenis;
    }
}
