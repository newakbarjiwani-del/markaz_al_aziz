<?php

use App\Models\Kelas;
use App\Models\LogSiswaEdit;
use App\Models\OrangTua;
use App\Models\ProfilSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'Madrasah Aliyah (MA)',
        'address' => 'Tigamaya',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'is_active' => true,
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '9005005',
        'name' => 'Anak Portal Ortu',
        'gender' => 'L',
        'birth_place' => 'Cirebon',
        'address' => 'Jl. Lama',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->profil = ProfilSiswa::create([
        'siswa_id' => $this->siswa->id,
        'extra_fields' => [
            'nama_panggilan' => 'Anak',
        ],
    ]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Bapak Ortu',
        'nama_ibu' => 'Ibu Ortu',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach($this->siswa->id);

    $this->ortuUser = User::create([
        'username' => 'ortu.child.profile',
        'name' => 'Orang Tua Child Profile',
        'email' => 'ortu-child-profile@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'orang_tua_id' => $this->orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');
});

test('parent can update linked child profile details', function () {
    $this->actingAs($this->ortuUser)
        ->putJson(route('portal.ortu.anak.update', $this->siswa), [
            'name' => 'Anak Portal Ortu Baru',
            'gender' => 'P',
            'birth_date' => '2018-05-12',
            'birth_place' => 'Bandung',
            'address' => 'Jl. Baru No. 10',
            'nama_panggilan' => 'Budi',
            'golongan_darah' => 'O',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->siswa->refresh();
    $extra = $this->profil->fresh()->extra_fields ?? [];

    expect($this->siswa->name)->toBe('Anak Portal Ortu Baru')
        ->and($this->siswa->gender)->toBe('P')
        ->and($this->siswa->birth_place)->toBe('Bandung')
        ->and($this->siswa->address)->toBe('Jl. Baru No. 10')
        ->and($this->siswa->birth_date?->format('Y-m-d'))->toBe('2018-05-12')
        ->and($extra['nama_panggilan'])->toBe('Budi')
        ->and($extra['golongan_darah'])->toBe('O');
});

test('parent edit creates log_siswa_edit entries', function () {
    $this->actingAs($this->ortuUser)
        ->putJson(route('portal.ortu.anak.update', $this->siswa), [
            'name' => 'Nama Baru',
            'gender' => 'P',
            'birth_date' => '2020-01-15',
            'birth_place' => 'Jakarta',
            'address' => 'Jl. Sudirman',
            'nama_panggilan' => 'New Nickname',
            'golongan_darah' => 'B+',
        ]);

    // Verify log entries were created
    $logs = LogSiswaEdit::where('siswa_id', $this->siswa->id)->get();

    expect($logs)->toHaveCount(7) // name, gender, birth_date, birth_place, address, nama_panggilan, golongan_darah
        ->and($logs->where('field', 'name')->first()->old_value)->toBe('Anak Portal Ortu')
        ->and($logs->where('field', 'name')->first()->new_value)->toBe('Nama Baru')
        ->and($logs->where('field', 'gender')->first()->old_value)->toBe('L')
        ->and($logs->where('field', 'gender')->first()->new_value)->toBe('P')
        ->and($logs->where('field', 'nama_panggilan')->first()->old_value)->toBe('Anak')
        ->and($logs->where('field', 'nama_panggilan')->first()->new_value)->toBe('New Nickname')
        ->and($logs->where('field', 'golongan_darah')->first()->new_value)->toBe('B+');
});
