<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Sanction level attached to a pelanggaran type (jenis_pelanggaran.sanction).
 *
 * - SP1 / SP2 / SP3 : Surat Peringatan 1..3 (warning notice letter)
 * - DO              : Drop Out — student is expelled
 * - ganti rugi 10 x lipat : compensate 10x the item/item value as punishment
 */
final class PelanggaranSanction
{
    public const SP1 = 'SP1';

    public const SP2 = 'SP2';

    public const SP3 = 'SP3';

    public const DO = 'DO';

    public const GANTI_RUGI = 'ganti rugi 10 x lipat';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SP1 => 'Surat Peringatan 1',
            self::SP2 => 'Surat Peringatan 2',
            self::SP3 => 'Surat Peringatan 3',
            self::DO => 'Drop Out (dikeluarkan)',
            self::GANTI_RUGI => 'Ganti rugi 10x lipat',
        ];
    }

    public static function label(string|int|null $sanction): string
    {
        $code = self::normalize($sanction);

        if ($code === null) {
            return '-';
        }

        return self::labels()[$code] ?? (string) $code;
    }

    /**
     * Normalize form/import input to a canonical sanction code.
     * Accepts "SP1"/"sp1", "DO"/"do"/"drop out", and the ganti-rugi phrase.
     */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', strtolower(trim((string) $value)));

        return match (true) {
            $text === 'sp1' => self::SP1,
            $text === 'sp2' => self::SP2,
            $text === 'sp3' => self::SP3,
            $text === 'do', $text === 'drop out' => self::DO,
            str_contains($text, 'ganti rugi') => self::GANTI_RUGI,
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return ['nullable', Rule::in(array_keys(self::labels()))];
    }

    /**
     * Severity rank for comparing sanctions (higher = more severe).
     */
    public static function severityRank(?string $sanction): int
    {
        $code = self::normalize($sanction);

        return match ($code) {
            self::SP1 => 1,
            self::SP2 => 2,
            self::SP3 => 3,
            self::GANTI_RUGI => 4,
            self::DO => 5,
            default => 0,
        };
    }

    /**
     * Pick the highest-severity sanction from a list of codes.
     *
     * @param  list<string|null>  $sanctions
     */
    public static function highest(array $sanctions): ?string
    {
        $best = null;
        $bestRank = 0;

        foreach ($sanctions as $sanction) {
            $code = self::normalize($sanction);
            if ($code === null) {
                continue;
            }

            $rank = self::severityRank($code);
            if ($rank > $bestRank) {
                $bestRank = $rank;
                $best = $code;
            }
        }

        return $best;
    }
}
