<?php

namespace App\Http\Requests\Student\Concerns;

use App\Models\OrangTua;
use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use App\Support\WhatsAppLink;
use Illuminate\Validation\Validator;

trait ValidatesOrangTuaContact
{
    /**
     * @return array<string, mixed>
     */
    protected function orangTuaFieldRules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'nama_ayah' => ['nullable', 'string', 'max:100'],
            'telepon_ayah' => ['nullable', 'string', 'max:20'],
            'email_ayah' => ['nullable', 'email', 'max:100'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:100'],
            'nama_ibu' => ['nullable', 'string', 'max:100'],
            'telepon_ibu' => ['nullable', 'string', 'max:20'],
            'email_ibu' => ['nullable', 'email', 'max:100'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:100'],
            'nama_wali' => ['nullable', 'string', 'max:100'],
            'telepon_wali' => ['nullable', 'string', 'max:20'],
            'email_wali' => ['nullable', 'email', 'max:100'],
            'pekerjaan_wali' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:aktif,nonaktif'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['telepon_ayah', 'telepon_ibu', 'telepon_wali'] as $field) {
            $value = trim((string) $this->input($field, ''));
            if ($value === '') {
                $merged[$field] = null;

                continue;
            }

            if (str_starts_with($value, "'")) {
                $value = substr($value, 1);
            }

            $merged[$field] = WhatsAppLink::normalizePhone($value) ?? $value;
        }

        foreach (['email_ayah', 'email_ibu', 'email_wali'] as $field) {
            $value = trim((string) $this->input($field, ''));
            $merged[$field] = $value === '' ? null : $value;
        }

        $this->merge($merged);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $hasContact = collect(['nama_ayah', 'nama_ibu', 'nama_wali'])
                ->contains(fn (string $field) => trim((string) $this->input($field, '')) !== '');

            if (! $hasContact) {
                $validator->errors()->add(
                    'nama_ayah',
                    'Minimal salah satu nama ayah, ibu, atau wali wajib diisi.'
                );
            }

            $this->assertDistinctContactPhones($validator);
            $this->assertUniqueContactPhones($validator);
        });
    }

    protected function assertDistinctContactPhones(Validator $validator): void
    {
        $phones = [
            'telepon_ayah' => ['label' => 'ayah', 'value' => $this->normalizedPhoneInput('telepon_ayah')],
            'telepon_ibu' => ['label' => 'ibu', 'value' => $this->normalizedPhoneInput('telepon_ibu')],
            'telepon_wali' => ['label' => 'wali', 'value' => $this->normalizedPhoneInput('telepon_wali')],
        ];

        $fields = array_keys($phones);

        for ($i = 0; $i < count($fields); $i++) {
            for ($j = $i + 1; $j < count($fields); $j++) {
                $left = $phones[$fields[$i]];
                $right = $phones[$fields[$j]];

                if ($left['value'] === null || $right['value'] === null) {
                    continue;
                }

                if ($left['value'] !== $right['value']) {
                    continue;
                }

                $message = 'Nomor telepon '.$left['label'].' dan '.$right['label'].' tidak boleh sama.';

                if (! $validator->errors()->has($fields[$i])) {
                    $validator->errors()->add($fields[$i], $message);
                }

                if (! $validator->errors()->has($fields[$j])) {
                    $validator->errors()->add($fields[$j], $message);
                }
            }
        }
    }

    protected function assertUniqueContactPhones(Validator $validator): void
    {
        // Store has no route model; update must ignore the current orang tua.
        $ignoreId = $this->route('orangTua')
            ? RouteModelId::require($this, 'orangTua', 'Data orang tua tidak valid untuk pembaruan.')
            : null;

        $fields = [
            'telepon_ayah' => 'ayah',
            'telepon_ibu' => 'ibu',
            'telepon_wali' => 'wali',
        ];

        foreach ($fields as $field => $label) {
            $phone = $this->normalizedPhoneInput($field);
            if ($phone === null || $validator->errors()->has($field)) {
                continue;
            }

            $exists = OrangTua::withoutGlobalScopes()
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '<>', $ignoreId))
                ->where(function ($q) use ($phone) {
                    $q->where('telepon_ayah', $phone)
                        ->orWhere('telepon_ibu', $phone)
                        ->orWhere('telepon_wali', $phone);
                })
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    $field,
                    'Nomor telepon '.$label.' sudah digunakan pada data orang tua lain.'
                );
            }
        }
    }

    protected function normalizedPhoneInput(string $field): ?string
    {
        $value = $this->input($field);

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (string) $value;
    }
}
