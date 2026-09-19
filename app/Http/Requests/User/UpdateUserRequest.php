<?php

namespace App\Http\Requests\User;

use App\Support\AdminModuleAccess;
use App\Support\MultiRoleConstraint;
use App\Support\PortalEntityUserRules;
use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use App\Support\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        $userId = RouteModelId::require($this, 'user', 'Data pengguna tidak valid untuk pembaruan.');
        /** @var \App\Models\User|null $targetUser */
        $targetUser = $this->route('user');
        $isSelf = $targetUser && $this->user()?->id === $targetUser->id;
        $currentRoles = is_object($targetUser) ? $targetUser->roles()->pluck('name')->toArray() : [];

        return [
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', SoftDeleteRules::unique('users', 'username', $userId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', SoftDeleteRules::unique('users', 'email', $userId)],
            'phone' => ['nullable', 'string', 'max:20', SoftDeleteRules::unique('users', 'phone', $userId)],
            'password' => [
                Rule::prohibitedIf(fn () => $targetUser
                    && $targetUser->hasRole('super_admin')
                    && ! $isSelf),
                'nullable',
                'string',
                'confirmed',
                Password::defaults(),
            ],
            'current_password' => [
                Rule::requiredIf($isSelf),
                'nullable',
                'current_password',
            ],
            'status' => [
                ...UserStatus::rules(),
                Rule::when($isSelf && $targetUser, Rule::in([(int) $targetUser->status])),
            ],
            'role' => [
                'required',
                'array',
                'min:1',
                new MultiRoleConstraint,
            ],
            'role.*' => ['string', Rule::in(StoreUserRequest::ROLES)],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in(AdminModuleAccess::keys())],
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'siswa_id' => [
                Rule::requiredIf(fn () => in_array('siswa', $this->input('role', []), true)),
                'nullable',
                SoftDeleteRules::exists('siswa'),
                SoftDeleteRules::unique('users', 'siswa_id', $userId),
            ],
            'guru_id' => [
                Rule::requiredIf(fn () => in_array('guru', $this->input('role', []), true)),
                'nullable',
                SoftDeleteRules::exists('guru'),
                SoftDeleteRules::unique('users', 'guru_id', $userId),
            ],
            'orang_tua_id' => [
                Rule::requiredIf(fn () => in_array('orang_tua', $this->input('role', []), true)),
                'nullable',
                SoftDeleteRules::exists('orang_tua'),
                SoftDeleteRules::unique('users', 'orang_tua_id', $userId),
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
            'current_password.required' => 'Password saat ini wajib diisi untuk mengubah akun Anda.',
            'current_password.current_password' => 'Password saat ini tidak cocok.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.prohibited' => 'Password akun super admin lain tidak dapat diubah dari halaman ini.',
            'role.in' => 'Peran akun sendiri tidak dapat diubah dari halaman ini.',
            'status.in' => 'Status akun sendiri tidak dapat diubah dari halaman ini.',
            'modules.*.in' => 'Modul akses tidak valid.',
        ]);
    }

    protected function prepareForValidation(): void
    {
        /** @var \App\Models\User|null $targetUser */
        $targetUser = $this->route('user');
        $isSelf = $targetUser && $this->user()?->id === $targetUser->id;

        if ($isSelf && $targetUser) {
            $this->merge([
                'role' => $targetUser->roles()->pluck('name')->toArray(),
                'status' => (int) $targetUser->status,
                'modules' => AdminModuleAccess::modulesFromDirectPermissions($targetUser),
            ]);
        } elseif ($this->has('status')) {
            $normalized = UserStatus::normalize($this->input('status'));
            if ($normalized !== null) {
                $this->merge(['status' => $normalized]);
            }
        }

        // Normalize role array: filter empty, split comma-joined (legacy hidden input)
        if (! $isSelf && $this->has('role') && is_array($this->input('role'))) {
            $roles = array_filter($this->input('role'), fn ($v) => is_string($v) && $v !== '');
            $roles = array_values($roles);

            // Handle comma-separated single-element (legacy fallback)
            if (count($roles) === 1 && is_string($roles[0]) && str_contains($roles[0], ',')) {
                $roles = array_map('trim', explode(',', $roles[0]));
            }

            $this->merge(['role' => array_values(array_unique($roles))]);
        }

        if (! $isSelf) {
            $this->merge([
                'modules' => $this->normalizedModules(),
            ]);
        }

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
