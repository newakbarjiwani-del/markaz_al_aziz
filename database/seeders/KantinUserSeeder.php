<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use App\Models\User;
use App\Support\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KantinUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Sekolah::query()->orderBy('id')->get() as $sekolah) {
            $user = User::create([
                'username' => 'kantin.'.$sekolah->code,
                'sekolah_id' => $sekolah->id,
                'name' => 'Operator Kantin '.$sekolah->code,
                'email' => 'kantin.'.$sekolah->code.'@school.local',
                'phone' => '62811000'.str_pad((string) (100 + $sekolah->id), 4, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE,
            ]);

            $user->assignRole('kantin');
        }

        $maSchool = Sekolah::query()->where('code', 'ma')->first();

        if ($maSchool) {
            $legacy = User::create([
                'username' => 'kantin',
                'sekolah_id' => $maSchool->id,
                'name' => 'Operator Kantin Demo',
                'email' => 'kantin@school.local',
                'phone' => '628110000006',
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE,
            ]);

            $legacy->assignRole('kantin');
        }
    }
}
