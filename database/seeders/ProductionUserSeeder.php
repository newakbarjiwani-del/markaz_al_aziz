<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use App\Models\User;
use App\Support\UserStatus;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin(config('seed.production.superadmin', []));
        $this->seedAdmin(config('seed.production.admin', []));
    }

    /** @param array<string, mixed> $config */
    private function seedSuperAdmin(array $config): void
    {
        $password = $this->requiredPassword($config['password'] ?? null, 'SEED_SUPERADMIN_PASSWORD');

        $this->createUser(
            username: (string) ($config['username'] ?? 'superadmin'),
            role: 'super_admin',
            attributes: [
                'name' => (string) ($config['name'] ?? 'Super Admin'),
                'email' => $this->nullableString($config['email'] ?? null),
                'phone' => $this->nullableString($config['phone'] ?? null),
                'password' => $password,
                'status' => UserStatus::ACTIVE,
                'sekolah_id' => null,
            ],
        );
    }

    /** @param array<string, mixed> $config */
    private function seedAdmin(array $config): void
    {
        if (! ($config['enabled'] ?? true)) {
            return;
        }

        $password = $this->requiredPassword($config['password'] ?? null, 'SEED_ADMIN_PASSWORD');
        $sekolahCode = $this->nullableString($config['sekolah_code'] ?? null);
        $sekolahId = null;

        if ($sekolahCode !== null) {
            $sekolahId = Sekolah::query()->where('code', $sekolahCode)->value('id');

            if ($sekolahId === null) {
                $this->command?->warn("SEED_ADMIN_SEKOLAH_CODE \"{$sekolahCode}\" tidak ditemukan; admin dibuat tanpa sekolah.");
            }
        }

        $this->createUser(
            username: (string) ($config['username'] ?? 'admin'),
            role: 'admin',
            attributes: [
                'name' => (string) ($config['name'] ?? 'Administrator'),
                'email' => $this->nullableString($config['email'] ?? null),
                'phone' => $this->nullableString($config['phone'] ?? null),
                'password' => $password,
                'status' => UserStatus::ACTIVE,
                'sekolah_id' => $sekolahId,
            ],
        );
    }

    /** @param array<string, mixed> $attributes */
    private function createUser(string $username, string $role, array $attributes): User
    {
        $user = User::query()->firstOrCreate(
            ['username' => $username],
            $attributes
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        if ($user->wasRecentlyCreated) {
            $this->command?->info("User \"{$username}\" ({$role}) berhasil dibuat.");
        } else {
            $this->command?->warn("User \"{$username}\" sudah ada; dilewati.");
        }

        return $user;
    }

    private function requiredPassword(?string $password, string $envKey): string
    {
        $password = trim((string) $password);

        if ($password === '') {
            throw new InvalidArgumentException("{$envKey} wajib diisi untuk production seed.");
        }

        return $password;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
