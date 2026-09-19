<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\ActionMessage;
use App\Support\AdminModuleAccess;
use App\Support\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('users.view');
        $canManage = $this->canManageUsers();
        $canCreateUsers = $this->canCreateUsers();

        $roles = Role::query()->orderBy('name');
        if (! $canCreateUsers) {
            $roles->whereIn('name', ['siswa', 'orang_tua', 'guru']);
        }

        return view('super-admin.users.index', [
            'title' => 'Manajemen User',
            'schools' => Sekolah::orderBy('name')->get(),
            'roles' => $roles->pluck('name'),
            'canManageUsers' => $canManage,
            'canCreateUsers' => $canCreateUsers,
            'adminModules' => collect(AdminModuleAccess::definitions())
                ->map(fn (array $def, string $key) => [
                    'key' => $key,
                    'label' => $def['label'],
                ])
                ->values()
                ->all(),
            'roleLabels' => [
                'super_admin' => 'Super Admin',
                'admin' => 'Admin Sekolah',
                'guru' => 'Guru (Portal)',
                'orang_tua' => 'Orang Tua (Portal)',
                'siswa' => 'Siswa (Portal)',
                'kantin' => 'Operator Kantin',
                'bendahara' => 'Bendahara',
                'cashless' => 'Operator Cashless',
                'pimpinan' => 'Pimpinan (Portal)',
                'prestasi_pelanggaran' => 'Prestasi & Pelanggaran',
                'perpustakaan' => 'Perpustakaan (Portal)',
                'perizinan' => 'Pencatat Izin / Security (Portal)',
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('users.view');
        $canManage = $this->canManageUsers();

        $query = User::query()
            ->with(['sekolah', 'roles', 'siswa', 'guru', 'orangTua'])
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->integer('status')));

        if (! $this->canCreateUsers()) {
            $query->role(['siswa', 'orang_tua', 'guru']);
            $sekolahId = $request->user()?->sekolah_id;
            if ($sekolahId) {
                $query->where('sekolah_id', (int) $sekolahId);
            }
        }

        return $this->datatableResponse($request, $query, [
            'searchable' => ['username', 'name', 'email', 'phone', 'status'],
            'orderable' => ['username', 'name', 'email', 'status', 'created_at'],
        ], function (User $user) use ($canManage) {
            $roleNames = $user->roles->pluck('name')->toArray();
            $fields = [
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email ?? '',
                'phone' => $user->phone,
                'status' => (int) $user->status,
                'role' => implode(',', $roleNames),
                'roles' => $roleNames,
                'modules' => AdminModuleAccess::modulesFromDirectPermissions($user),
                'sekolah_id' => $user->sekolah_id ?? '',
                'siswa_id' => $user->siswa_id,
                'guru_id' => $user->guru_id,
                'orang_tua_id' => $user->orang_tua_id,
                'linked_entity_label' => $this->linkedEntityLabel($user),
            ];

            $actions = [];

            if ($canManage && $this->canEditUser($user)) {
                $actions['edit'] = [
                    'update_url' => $this->canCreateUsers()
                        ? route('super-admin.users.update', $user)
                        : route('admin.manajemen-user.update', $user),
                    'form_target' => 'user-form',
                    'modal_target' => 'user-modal',
                    'edit_self' => $user->id === auth()->id(),
                    'can_change_password' => $this->canChangePassword($user),
                    'record' => $fields,
                ];
            }

            if ($this->canCreateUsers() && $canManage && $user->id !== auth()->id()) {
                $actions['delete'] = [
                    'url' => route('super-admin.users.destroy', $user),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus user ini?',
                    'confirm_detail' => [
                        ['label' => 'Username', 'value' => $user->username],
                        ['label' => 'Nama', 'value' => $user->name],
                        ['label' => 'Email', 'value' => $user->email ?? '-'],
                        ['label' => 'Role', 'value' => implode(', ', $roleNames) ?: '-'],
                    ],
                ];
            }

            if ($canManage && $user->id !== auth()->id() && $this->canResetPassword($user)) {
                $actions['reset'] = [
                    'url' => $this->canCreateUsers()
                        ? route('super-admin.users.reset-password', $user)
                        : route('admin.manajemen-user.reset-password', $user),
                    'confirm_title' => 'Reset Password',
                    'confirm_message' => 'Reset password akun ini?',
                    'confirm_detail' => [
                        ['label' => 'Username', 'value' => $user->username],
                        ['label' => 'Role', 'value' => implode(', ', $roleNames) ?: '-'],
                        ['label' => 'Password Baru', 'value' => 'Sama dengan username'],
                    ],
                ];
            }

            return [
                $user->username,
                $user->name,
                $user->email ?? '-',
                implode(', ', $roleNames) ?: '-',
                $user->sekolah?->name ?? 'Semua',
                UserStatus::label($user->status),
                $actions !== [] ? $this->cell('', ['actions' => $actions], 'action') : '-',
            ];
        });
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $this->extractUserPayload($request->validated());
        $data['password'] = Hash::make($request->validated('password'));

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::create($data);
            $user->syncRoles($request->validated('role'));
            $this->syncModulePermissions($user, $request->validated('modules', []));

            return $user;
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('User berhasil ditambahkan', ActionMessage::user($user)),
            $user->load('roles'),
            201
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        if (! $this->canCreateUsers()) {
            if (! $this->canEditUser($user)) {
                return $this->jsonError('Admin hanya dapat mengubah akun siswa, orang tua, atau guru pada sekolah yang sama.');
            }
            $validated['role'] = $user->roles()->pluck('name')->toArray();
            $validated['sekolah_id'] = $user->sekolah_id ?? $request->user()?->sekolah_id;
            $validated['modules'] = [];
        }

        $isSelf = $user->id === auth()->id();
        $data = $this->extractUserPayload($validated);

        if (($password = $validated['password'] ?? null) && $this->canChangePassword($user)) {
            $data['password'] = Hash::make($password);
        }

        DB::transaction(function () use ($user, $data, $validated, $isSelf): void {
            $user->update($data);

            if (! $isSelf) {
                $user->syncRoles($validated['role']);
            }

            if ($this->canCreateUsers() && ! $isSelf) {
                $this->syncModulePermissions($user, $validated['modules'] ?? []);
            }
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('User berhasil diperbarui', ActionMessage::user($user)),
            $user->load('roles')
        );
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->jsonError('Tidak dapat menghapus akun yang sedang login.');
        }

        $detail = ActionMessage::user($user);
        $user->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('User berhasil dihapus', $detail));
    }

    public function resetPassword(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->jsonError('Tidak dapat reset password akun yang sedang login.');
        }

        if ($user->hasRole('super_admin')) {
            return $this->jsonError('Tidak dapat reset password akun super admin lain.');
        }

        if (! $this->canResetPassword($user)) {
            return $this->jsonError('Admin hanya dapat reset password akun siswa, orang tua, atau guru pada sekolah yang sama.');
        }

        $plainPassword = (string) $user->username;
        $user->update([
            'password' => Hash::make($plainPassword),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Password berhasil direset',
                ActionMessage::user($user).' · Password baru sama dengan username.'
            )
        );
    }

    /** @param array<string, mixed> $validated */
    private function extractUserPayload(array $validated): array
    {
        $roles = (array) $validated['role'];
        unset($validated['password'], $validated['role'], $validated['modules']);

        $payload = [
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'sekolah_id' => null,
            'siswa_id' => null,
            'guru_id' => null,
            'orang_tua_id' => null,
        ];

        $payload['siswa_id'] = in_array('siswa', $roles, true)
            ? ($validated['siswa_id'] ?? null)
            : null;

        $payload['guru_id'] = in_array('guru', $roles, true)
            ? ($validated['guru_id'] ?? null)
            : null;

        $payload['orang_tua_id'] = in_array('orang_tua', $roles, true)
            ? ($validated['orang_tua_id'] ?? null)
            : null;

        $portalRoles = ['siswa', 'guru', 'orang_tua'];
        $hasPortalRole = array_intersect($roles, $portalRoles) !== [];

        if (! $hasPortalRole) {
            $payload['sekolah_id'] = $validated['sekolah_id'] ?? null;
        } elseif (in_array('siswa', $roles, true)) {
            $siswa = \App\Models\Siswa::find($validated['siswa_id'] ?? null);
            $payload['sekolah_id'] = $siswa?->sekolah_id ?? ($validated['sekolah_id'] ?? null);
        } elseif (in_array('guru', $roles, true)) {
            $guru = \App\Models\Guru::find($validated['guru_id'] ?? null);
            $payload['sekolah_id'] = $guru?->sekolah_id ?? ($validated['sekolah_id'] ?? null);
        } else {
            $payload['sekolah_id'] = $validated['sekolah_id'] ?? null;
        }

        return $payload;
    }

    /**
     * @param  list<string>  $modules
     */
    private function syncModulePermissions(User $user, array $modules): void
    {
        $user->syncPermissions(AdminModuleAccess::syncDirectPermissionNames($user, $modules));
    }

    private function linkedEntityLabel(User $user): string
    {
        if ($user->siswa_id && $user->siswa) {
            return trim($user->siswa->nis.' — '.$user->siswa->name);
        }

        if ($user->guru_id && $user->guru) {
            return trim($user->guru->nip.' — '.$user->guru->name);
        }

        if ($user->orang_tua_id && $user->orangTua) {
            return $user->orangTua->displayName();
        }

        return '';
    }

    private function canManageUsers(): bool
    {
        return request()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    private function canCreateUsers(): bool
    {
        return request()->user()?->hasRole('super_admin') ?? false;
    }

    private function canEditUser(User $user): bool
    {
        $actor = request()->user();
        if (! $actor) {
            return false;
        }

        if ($actor->hasRole('super_admin')) {
            return true;
        }

        if (! $actor->hasRole('admin')) {
            return false;
        }

        $userRoles = $user->roles()->pluck('name')->toArray();
        $allowedRoles = ['siswa', 'orang_tua', 'guru'];
        if (array_intersect($userRoles, $allowedRoles) === []) {
            return false;
        }

        if (! $actor->sekolah_id) {
            return true;
        }

        return (int) $actor->sekolah_id === (int) $user->sekolah_id;
    }

    private function canResetPassword(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return false;
        }

        $userRoles = $user->roles()->pluck('name')->toArray();
        $allowedRoles = ['siswa', 'orang_tua', 'guru'];
        if (array_intersect($userRoles, $allowedRoles) === []) {
            return false;
        }

        return $this->canEditUser($user);
    }

    private function canChangePassword(User $user): bool
    {
        if (! $user->hasRole('super_admin')) {
            return true;
        }

        return $user->id === auth()->id();
    }
}
