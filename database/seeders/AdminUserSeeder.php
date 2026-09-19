<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        /** @var list<array{username: string, name: string, email: string, role: string, sekolah_id: ?int}> $accounts */
        $accounts = [
            [
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'email' => 'superadmin@ittihad.test',
                'role' => 'super_admin',
                'sekolah_id' => null,
            ],
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'email' => 'admin@ittihad.test',
                'role' => 'admin',
                'sekolah_id' => null,
            ],
            [
                'username' => 'admin2',
                'name' => 'Administrator 2',
                'email' => 'admin2@ittihad.test',
                'role' => 'admin',
                'sekolah_id' => null,
            ],
            [
                'username' => 'kantin',
                'name' => 'Operator Kantin',
                'email' => 'kantin@ittihad.test',
                'role' => 'kantin',
                'sekolah_id' => null,
            ],
            [
                'username' => 'kantin2',
                'name' => 'Operator Kantin 2',
                'email' => 'kantin2@ittihad.test',
                'role' => 'kantin',
                'sekolah_id' => null,
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $password,
                    'status' => UserStatus::ACTIVE,
                    'sekolah_id' => $account['sekolah_id'],
                ],
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
