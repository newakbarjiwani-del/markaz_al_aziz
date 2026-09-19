<?php

namespace App\Http\Traits;

use App\Support\DisplayDate;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait DataTableTrait
{
    protected function cell(mixed $display, mixed $raw, string $type = 'text'): array
    {
        return [
            'display' => $display,
            'raw' => $raw,
            'type' => $type,
        ];
    }

    /**
     * Send ISO date/datetime for client-side Indonesian formatting in datatable.js.
     */
    protected function dateCell(mixed $value): array|string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $carbon = $value instanceof CarbonInterface
            ? $value
            : \Carbon\Carbon::parse($value);

        $isDateOnly = $carbon->format('H:i:s') === '00:00:00';
        $iso = $isDateOnly
            ? $carbon->format('Y-m-d')
            : $carbon->format('Y-m-d H:i:s');

        return $this->cell($iso, $iso, $isDateOnly ? 'date' : 'datetime');
    }

    protected function badgeCell(string $label, string $class): array
    {
        return $this->cell('<span class="'.$class.'">'.e($label).'</span>', $label, 'text');
    }

    protected function datatableResponse(
        Request $request,
        Builder $query,
        array $columns,
        ?callable $rowMapper = null,
    ): JsonResponse {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(10, (int) $request->input('length', 25)));

        $baseQuery = clone $query;
        $recordsTotal = $baseQuery->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '' && isset($columns['searchable'])) {
            $tableName = $query->getModel()->getTable();
            $query->where(function (Builder $q) use ($search, $columns, $tableName) {
                foreach ($columns['searchable'] as $field) {
                    if (str_contains($field, '.')) {
                        $parts = explode('.', $field);
                        $column = array_pop($parts);
                        $relation = implode('.', $parts);

                        // If the first part is the model's own table, use a direct
                        // WHERE with table-qualified column (not whereHas).
                        if ($parts[0] === $tableName) {
                            $q->orWhere($field, 'like', "%{$search}%");
                        } else {
                            $q->orWhereHas($relation, function (Builder $relQuery) use ($column, $search) {
                                $relQuery->where($column, 'like', "%{$search}%");
                            });
                        }
                    } else {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderable = $columns['orderable'] ?? $columns['searchable'] ?? [];
        if (isset($orderable[$orderColumnIndex])) {
            $query->orderBy($orderable[$orderColumnIndex], $orderDir);
        }

        $recordsFiltered = (clone $query)->count();
        $records = $query->skip($start)->take($length)->get();

        $data = $records->map(function ($record) use ($rowMapper, $columns) {
            if ($rowMapper) {
                $row = $rowMapper($record);
            } else {
                $row = collect($columns['fields'])->map(fn ($field) => data_get($record, $field))->values()->all();
            }

            $cells = is_array($row) ? $row : iterator_to_array($row);

            return array_map(function ($cell) {
                // Preserve explicit structured cells: {display, raw, type}
                if (is_array($cell) && array_key_exists('display', $cell) && array_key_exists('raw', $cell)) {
                    $type = (string) ($cell['type'] ?? 'text');

                    // Date/datetime: keep ISO for client formatting; do not locale-format here.
                    if (in_array($type, ['date', 'datetime'], true)) {
                        $raw = $cell['raw'] ?? $cell['display'];

                        return [
                            'display' => $raw,
                            'raw' => $raw,
                            'type' => $type,
                        ];
                    }

                    $display = DisplayDate::cell($cell['display']);
                    $raw = in_array($type, ['number'], true)
                        ? $cell['raw']
                        : DisplayDate::cell($cell['raw']);

                    // Compact payload: plain text cells with identical display/raw can be scalar.
                    if ($type === 'text' && $display === $raw) {
                        return $display;
                    }

                    return [
                        'display' => $display,
                        'raw' => $raw,
                        'type' => $type,
                    ];
                }

                if ($cell instanceof DateTimeInterface) {
                    return $this->dateCell($cell);
                }

                if (is_string($cell) && DisplayDate::isIsoDateTime($cell)) {
                    return $this->dateCell($cell);
                }

                if (is_string($cell) && DisplayDate::isIsoDate($cell)) {
                    return $this->dateCell($cell);
                }

                $display = DisplayDate::cell($cell);

                // Auto-normalize legacy HTML string cells:
                // keep rich display HTML, but provide text-only raw for sort/filter/export.
                if (is_string($display) && preg_match('/<[^>]+>/', $display) === 1) {
                    $raw = trim(preg_replace('/\s+/', ' ', strip_tags($display)) ?? '');

                    return [
                        'display' => $display,
                        'raw' => $raw,
                        'type' => 'text',
                    ];
                }

                // Compact payload for common plain text/values.
                return $display;
            }, $cells);
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    protected function jsonSuccess(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function jsonError(string $message, mixed $errors = null, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
