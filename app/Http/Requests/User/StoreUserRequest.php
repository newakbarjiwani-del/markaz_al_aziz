<?php

namespace App\Http\Requests\User;

use App\Support\AdminModuleAccess;
use App\Support\MultiRoleConstraint;
use App\Support\PortalEntityUserRules;
use App\Support\SoftDeleteRules;
use App\Support\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /** @var list<string> */
    public const ROLES = [
        'super_admin',
        'admin',
        'guru',
        'orang_tua',
        'siswa',
        'kantin',
        'bendahara',
        'cashless',
        'pimpinan',
        'prestasi_pelanggaran',
        'perpustakaan',
        'perizinan',
    ];

    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', SoftDeleteRules::unique('users', 'username')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', SoftDeleteRules::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20', SoftDeleteRules::unique('users', 'phone')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'status' => UserStatus::rules(),
            'role' => [
                'required',
                'array',
                'min:1',
                new MultiRoleConstraint,
            ],
            'role.*' => ['string', Rule::in(self::ROLES)],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in(AdminModuleAccess::keys())],
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'siswa_id' => [
                Rule::requiredIf(fn () => in_array('siswa', $this->input('role', []), true)),
                'nullable',
                SoftDeleteRules::exists('siswa'),
                SoftDeleteRules::unique('users', 'siswa_id'),
            ],
            'guru_id' => [
                Rule::requiredIf(fn () => in_array('guru', $this->input('role', []), true)),
                'nullable',
                SoftDeleteRules::exists('guru'),
                SoftDeleteRules::unique('users', 'guru_id'),
            ],
            'orang_tua_id' => [
                Rule::requiredIf(fn () => in_array('orang_tua', $this->input('role', []), true)),
                'nullable',
                SoftDeleteRules::exists('orang_tua'),
                SoftDeleteRules::unique('users', 'orang_tua_id'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roles = (array) $this->input('role', []);
            $modules = array_values(array_filter((array) $this->input('modules', [])));

            if ($modules === []) {
                return;
            }

            if (! AdminModuleAccess::modulesAllowedForRoles($roles)) {
                $validator->errors()->add(
                    'modules',
                    'Akses modul tambahan tidak dapat diberikan untuk peran yang dipilih.'
                );
            }
        });
    }

    public function messages(): array
    {
        return array_merge(PortalEntityUserRules::messages(), [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'modules.*.in' => 'Modul akses tidak valid.',
        ]);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $normalized = UserStatus::normalize($this->input('status'));
            if ($normalized !== null) {
                $this->merge(['status' => $normalized]);
            }
        }

        // Normalize role array: filter empty, split comma-joined (legacy hidden input)
        if ($this->has('role') && is_array($this->input('role'))) {
            $roles = array_filter($this->input('role'), fn ($v) => is_string($v) && $v !== '');
            $roles = array_values($roles);

            // Handle comma-separated single-element (legacy fallback)
            if (count($roles) === 1 && is_string($roles[0]) && str_contains($roles[0], ',')) {
                $roles = array_map('trim', explode(',', $roles[0]));
            }

            $this->merge(['role' => array_values(array_unique($roles))]);
        }

        $this->merge([
            'modules' => $this->normalizedModules(),
        ]);

        if ($this->has('sekolah_id') && $this->input('sekolah_id') === '') {
            $this->merge(['sekolah_id' => null]);
        }

        if ($this->has('email') && $this->input('email') === '') {
            $this->merge(['email' => null]);
        }

        foreach (['siswa_id', 'guru_id', 'orang_tua_id'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function normalizedModules(): array
    {
        $modules = $this->input('modules', []);
        if (! is_array($modules)) {
            return [];
        }

        $modules = array_values(array_unique(array_filter(
            $modules,
            fn ($v) => is_string($v) && $v !== ''
        )));

        $roles = (array) $this->input('role', []);
        $implied = AdminModuleAccess::modulesImpliedByRoles($roles);

        return array_values(array_diff($modules, $implied));
    }
}
