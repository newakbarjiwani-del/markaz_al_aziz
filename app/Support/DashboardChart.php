<?php

namespace App\Support;

class DashboardChart
{
    /** @return list<string> */
    public static function monthLabels(int $count = 6): array
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $i) => now()->subMonths($i)->translatedFormat('M'))
            ->values()
            ->all();
    }

    /** @return list<string> */
    public static function dayLabels(int $count = 7): array
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $i) => today()->subDays($i)->translatedFormat('D'))
            ->values()
            ->all();
    }

    /**
     * @param  list<array{label?: string, data: list<float|int>}>  $datasets
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function make(
        string $id,
        string $title,
        string $type,
        array $labels,
        array $datasets,
        array $options = [],
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'type' => $type,
            'labels' => array_values($labels),
            'datasets' => $datasets,
            'options' => $options,
        ];
    }

    /** @param  list<float|int>  $data */
    public static function single(
        string $id,
        string $title,
        string $type,
        array $labels,
        array $data,
        ?string $label = null,
        array $options = [],
    ): array {
        return self::make($id, $title, $type, $labels, [
            ['label' => $label ?? $title, 'data' => array_values($data)],
        ], $options);
    }

    /** @param  array<string|int, float|int>  $map */
    public static function doughnutFromMap(string $id, string $title, array $map): array
    {
        $labels = array_map(
            fn (string $key) => ucfirst(str_replace('_', ' ', (string) $key)),
            array_keys($map)
        );

        return self::single($id, $title, 'doughnut', $labels, array_values($map));
    }
}
