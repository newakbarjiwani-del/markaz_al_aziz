<?php

namespace Tests\Feature;

use App\Models\JenisPelanggaran;
use App\Models\Perizinan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PelanggaranCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerizinanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_perizinan_dashboard_and_pages(): void
    {
        $admin = User::factory()->create(['username' => 'admin_test_'.uniqid()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('portal.perizinan.dashboard'))
            ->assertStatus(200)
            ->assertSee('Dashboard Perizinan');

        $this->actingAs($admin)
            ->get(route('portal.perizinan.keluar-masuk.index'))
            ->assertStatus(200)
            ->assertSee('Izin Keluar Masuk (Sekolah)');

        $this->actingAs($admin)
            ->get(route('portal.perizinan.keluar-masuk-pondok.index'))
            ->assertStatus(200)
            ->assertSee('Izin Keluar Masuk Pondok (Asrama)');

        $this->actingAs($admin)
            ->get(route('portal.perizinan.pulang-libur.index'))
            ->assertStatus(200)
            ->assertSee('Izin Pulang Libur (Mudik Santri)');

        $this->actingAs($admin)
            ->get(route('portal.perizinan.rekap-laporan'))
            ->assertStatus(200)
            ->assertSee('Rekap', false);
    }

    public function test_admin_can_store_and_checkin_perizinan(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '990011',
            'name' => 'Ahmad Santri',
            'gender' => 'L',
        ]);

        $admin = User::factory()->create(['username' => 'admin_test_'.uniqid()]);
        $admin->assignRole('admin');

        $tglMulai = Carbon::now()->format('Y-m-d\TH:i');
        $tglSampai = Carbon::now()->addHours(4)->format('Y-m-d\TH:i');

        $response = $this->actingAs($admin)
            ->postJson(route('portal.perizinan.keluar-masuk.store'), [
                'siswa_id' => $siswa->id,
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
                'alasan' => 'Beli perlengkapan sekolah',
                'tgl_mulai' => $tglMulai,
                'tgl_sampai' => $tglSampai,
                'penanggung_jawab' => 'Wali Santri',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('perizinan', [
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Beli perlengkapan sekolah',
            'pemberi_izin' => $admin->name,
        ]);

        $perizinan = Perizinan::where('siswa_id', $siswa->id)->firstOrFail();

        // Checkin test
        $checkinResponse = $this->actingAs($admin)
            ->postJson(route('portal.perizinan.keluar-masuk.checkin', $perizinan));

        $checkinResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $perizinan->refresh();
        $this->assertEquals(Perizinan::STATUS_KEMBALI, $perizinan->status);
        $this->assertNotNull($perizinan->tgl_kembali_aktual);
    }

    public function test_late_checkin_creates_pelanggaran_siswa(): void
    {
        $this->seed(PelanggaranCatalogSeeder::class);

        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '990013',
            'name' => 'Citra Santri',
            'gender' => 'P',
        ]);

        $petugas = User::factory()->create(['username' => 'petugas_late_'.uniqid()]);
        $petugas->assignRole('perizinan');

        $perizinan = Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Ke apotek',
            'tgl_mulai' => Carbon::now()->subHours(3),
            'tgl_sampai' => Carbon::now()->subHour(),
            'status' => Perizinan::STATUS_DISETUJUI,
        ]);

        $jenis = JenisPelanggaran::query()
            ->where('nama', config('prestasi-pelanggaran.perizinan_late_return_jenis_nama'))
            ->firstOrFail();

        $preview = $this->actingAs($petugas)
            ->getJson(route('portal.perizinan.keluar-masuk.checkin.preview', $perizinan));

        $preview->assertOk()
            ->assertJsonPath('data.is_late', true)
            ->assertJsonPath('data.default_pelanggaran.jenis_pelanggaran_id', $jenis->id);

        $checkin = $this->actingAs($petugas)
            ->postJson(route('portal.perizinan.keluar-masuk.checkin', $perizinan), [
                'pelanggaran_jenis_pelanggaran_id' => $jenis->id,
                'pelanggaran_judul' => $jenis->nama,
                'pelanggaran_point' => $jenis->point,
                'pelanggaran_keterangan' => 'Kembali terlambat dari izin keluar.',
            ]);

        $checkin->assertOk()->assertJson(['success' => true]);

        $perizinan->refresh();
        $this->assertSame(Perizinan::STATUS_TERLAMBAT, $perizinan->status);
        $this->assertNotNull($perizinan->tgl_kembali_aktual);

        $this->assertDatabaseHas('pelanggaran_siswa', [
            'siswa_id' => $siswa->id,
            'jenis_pelanggaran_id' => $jenis->id,
            'judul' => $jenis->nama,
            'reported_by' => $petugas->id,
        ]);
    }

    public function test_late_checkin_requires_pelanggaran_fields(): void
    {
        $sekolah = Sekolah::create(['code' => 'mts', 'name' => 'MTs Test']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '990014',
            'name' => 'Doni Santri',
            'gender' => 'L',
        ]);

        $admin = User::factory()->create(['username' => 'admin_late_'.uniqid()]);
        $admin->assignRole('admin');

        $perizinan = Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Urus SIM',
            'tgl_mulai' => Carbon::now()->subHours(2),
            'tgl_sampai' => Carbon::now()->subMinutes(30),
            'status' => Perizinan::STATUS_DISETUJUI,
        ]);

        $this->actingAs($admin)
            ->postJson(route('portal.perizinan.keluar-masuk.checkin', $perizinan))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pelanggaran_jenis_pelanggaran_id']);
    }

    public function test_admin_can_upload_file_for_perizinan_stored_in_public_disk(): void
    {
        Storage::fake('public');

        $sekolah = Sekolah::create(['code' => 'mts', 'name' => 'MTs Ittihad']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '880022',
            'name' => 'Siti Santri',
            'gender' => 'P',
        ]);

        $admin = User::factory()->create(['username' => 'admin_test_'.uniqid()]);
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->create('surat_dokter.pdf', 500, 'application/pdf');

        $tglMulai = Carbon::now()->format('Y-m-d\TH:i');
        $tglSampai = Carbon::now()->addDays(2)->format('Y-m-d\TH:i');

        $response = $this->actingAs($admin)
            ->postJson(route('portal.perizinan.keluar-masuk-pondok.store'), [
                'siswa_id' => $siswa->id,
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK_PONDOK,
                'alasan' => 'Izin periksa mata di RS',
                'tgl_mulai' => $tglMulai,
                'tgl_sampai' => $tglSampai,
                'penanggung_jawab' => 'Ibu Kandung',
                'file' => $file,
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $perizinan = Perizinan::where('siswa_id', $siswa->id)->firstOrFail();
        $this->assertNotNull($perizinan->file_path);
        $this->assertStringStartsWith('perizinan/keluar_masuk_pondok/', $perizinan->file_path);

        Storage::disk('public')->assertExists($perizinan->file_path);

        $showResponse = $this->actingAs($admin)
            ->getJson(route('portal.perizinan.keluar-masuk-pondok.show', $perizinan));

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.file_name', basename($perizinan->file_path));
    }

    public function test_pemberi_izin_can_be_custom_and_shown_in_rekap(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '990012',
            'name' => 'Budi Santri',
            'gender' => 'L',
        ]);

        $petugas = User::factory()->create([
            'username' => 'satpam_test_'.uniqid(),
            'name' => 'Pak Satpam',
        ]);
        $petugas->assignRole('perizinan');

        $tglMulai = Carbon::now()->format('Y-m-d\TH:i');
        $tglSampai = Carbon::now()->addHours(2)->format('Y-m-d\TH:i');

        $this->actingAs($petugas)
            ->postJson(route('portal.perizinan.keluar-masuk.store'), [
                'siswa_id' => $siswa->id,
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
                'alasan' => 'Ke bank',
                'tgl_mulai' => $tglMulai,
                'tgl_sampai' => $tglSampai,
                'pemberi_izin' => 'Ustadz Ahmad',
            ])
            ->assertJson(['success' => true]);

        $perizinan = Perizinan::where('siswa_id', $siswa->id)->firstOrFail();
        $this->assertSame('Ustadz Ahmad', $perizinan->pemberi_izin);
        $this->assertSame('Ustadz Ahmad', $perizinan->pemberiIzinLabel());

        $rekap = $this->actingAs($petugas)
            ->getJson(route('portal.perizinan.rekap-laporan.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $rekap->assertStatus(200);
        $payload = json_decode($rekap->getContent(), true);
        $pemberiCell = $payload['data'][0][3]['display'] ?? '';
        $this->assertStringContainsString('Ustadz Ahmad', $pemberiCell);
    }
}
