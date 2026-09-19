<?php

namespace Database\Seeders;

use App\Models\Alumni;
use App\Models\AlumniTracer;
use App\Models\Sekolah;
use App\Support\AlumniTracerStatus;
use Illuminate\Database\Seeder;

class AlumniDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sekolah = Sekolah::query()->where('is_active', true)->orderBy('id')->first()
            ?? Sekolah::query()->orderBy('id')->first();

        $alumni = Alumni::query()->updateOrCreate(
            [
                'sekolah_id' => $sekolah?->id,
                'nis' => 'ALUMNI-DEMO-001',
            ],
            [
                'name' => 'Ahmad Alumni Demo',
                'angkatan' => '2020',
                'phone' => '081234567890',
                'email' => 'alumni.demo@example.test',
                'address' => 'Pekanbaru',
                'is_active' => true,
                'notes' => 'Data demo tracer study',
            ],
        );

        AlumniTracer::query()->updateOrCreate(
            [
                'alumni_id' => $alumni->id,
                'tahun_tracer' => (string) now()->year,
            ],
            [
                'status_lulusan' => AlumniTracerStatus::KULIAH,
                'institusi' => 'Universitas Demo',
                'jabatan' => 'S1 Pendidikan',
                'bidang' => 'Pendidikan',
                'kota' => 'Pekanbaru',
                'catatan' => 'Respons demo Phase 3c',
                'submitted_at' => now(),
                'source' => AlumniTracer::SOURCE_ADMIN,
            ],
        );
    }
}
