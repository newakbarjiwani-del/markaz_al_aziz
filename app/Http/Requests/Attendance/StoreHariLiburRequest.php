<?php

namespace App\Http\Requests\Attendance;

use App\Models\HariLibur;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHariLiburRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge($this->sharedFieldRules(), [
            'entries' => ['required', 'array', 'min:1', 'max:50'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.name' => ['required', 'string', 'max:150'],
            'entries.*.notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedFieldRules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'applies_to' => ['required', Rule::in([
                HariLibur::APPLIES_BOTH,
                HariLibur::APPLIES_SISWA,
                HariLibur::APPLIES_GURU,
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $entries = collect($this->input('entries', []))
            ->filter(function ($entry) {
                if (! is_array($entry)) {
                    return false;
                }

                return filled($entry['date'] ?? null) || filled($entry['name'] ?? null);
            })
            ->values()
            ->all();

        $this->merge(['entries' => $entries]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateUniqueDates($validator);
        });
    }

    protected function validateUniqueDates(Validator $validator): void
    {
        $sekolahId = $this->filled('sekolah_id') ? $this->integer('sekolah_id') : null;
        $seen = [];

        foreach ($this->input('entries', []) as $index => $entry) {
            $date = $entry['date'] ?? null;
            if (! $date) {
                continue;
            }

            $key = ($sekolahId ?? 'global').'|'.$date;
            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "entries.{$index}.date",
                    'Tanggal duplikat dalam daftar yang Anda masukkan.'
                );

                continue;
            }
            $seen[$key] = true;

            $exists = HariLibur::query()
                ->where('date', $date)
                ->when(
                    $sekolahId === null,
                    fn ($query) => $query->whereNull('sekolah_id'),
                    fn ($query) => $query->where('sekolah_id', $sekolahId)
                )
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    "entries.{$index}.date",
                    'Hari libur untuk tanggal ini sudah terdaftar.'
                );
            }
        }
    }
}
