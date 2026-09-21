<?php

use App\Http\Controllers\Admin\AchievementViolation\DashboardController as AchievementViolationDashboardController;
use App\Http\Controllers\Admin\AchievementViolation\HukumanSiswaController;
use App\Http\Controllers\Admin\AchievementViolation\KatalogPelanggaranController;
use App\Http\Controllers\Admin\AchievementViolation\KatalogPrestasiController;
use App\Http\Controllers\Admin\AchievementViolation\PelanggaranImportExportController as AchievementViolationPelanggaranImportController;
use App\Http\Controllers\Admin\AchievementViolation\PrestasiImportExportController as AchievementViolationPrestasiImportController;
use App\Http\Controllers\Admin\AchievementViolation\RekapPelanggaranSiswaController;
use App\Http\Controllers\Admin\AchievementViolation\RekapPrestasiSiswaController;
use App\Http\Controllers\Admin\Akademik\DashboardController as AkademikDashboardController;
use App\Http\Controllers\Admin\Akademik\JadwalPelajaranController;
use App\Http\Controllers\Admin\Akademik\KalenderPendidikanController;
use App\Http\Controllers\Admin\Akademik\KurikulumController;
use App\Http\Controllers\Admin\Akademik\LaporanNilaiController;
use App\Http\Controllers\Admin\Akademik\MataPelajaranController;
use App\Http\Controllers\Admin\Akademik\NilaiController;
use App\Http\Controllers\Admin\Akademik\RaporController as AdminRaporController;
use App\Http\Controllers\Admin\Alumni\AlumniController as AdminAlumniController;
use App\Http\Controllers\Admin\Alumni\DashboardController as AlumniDashboardController;
use App\Http\Controllers\Admin\Alumni\TracerController as AdminAlumniTracerController;
use App\Http\Controllers\Admin\Attendance\AbsensiGuruController;
use App\Http\Controllers\Admin\Attendance\AbsensiSesiPengecualianController;
use App\Http\Controllers\Admin\Attendance\AbsensiSiswaController as AttendanceAbsensiSiswaController;
use App\Http\Controllers\Admin\Attendance\DashboardController as AttendanceDashboardController;
use App\Http\Controllers\Admin\Attendance\ExportAbsensiController;
use App\Http\Controllers\Admin\Attendance\HariLiburController;
use App\Http\Controllers\Admin\Attendance\JadwalAbsenController;
use App\Http\Controllers\Admin\Attendance\JadwalAbsensiGuruAttendanceController;
use App\Http\Controllers\Admin\Attendance\JadwalAbsensiGuruController;
use App\Http\Controllers\Admin\Attendance\LaporanAbsensiController;
use App\Http\Controllers\Admin\Attendance\PelajaranController;
use App\Http\Controllers\Admin\Attendance\QrCheckinController;
use App\Http\Controllers\Admin\Attendance\RekapPresensiController;
use App\Http\Controllers\Admin\Attendance\RekapPresensiGuruController;
use App\Http\Controllers\Admin\Attendance\RfidCheckinController;
use App\Http\Controllers\Admin\Booklet\BookletController as AdminBookletController;
use App\Http\Controllers\Admin\Booklet\DashboardController as BookletDashboardController;
use App\Http\Controllers\Admin\BukuLookupController;
use App\Http\Controllers\Admin\Cashless\AlokasiUangSakuController;
use App\Http\Controllers\Admin\Cashless\CashlessPinController;
use App\Http\Controllers\Admin\Cashless\DashboardController as CashlessDashboardController;
use App\Http\Controllers\Admin\Cashless\KartuPosController;
use App\Http\Controllers\Admin\Cashless\LimitKontrolController;
use App\Http\Controllers\Admin\Cashless\MenuKantinController;
use App\Http\Controllers\Admin\Cashless\PenarikanPendapatanKantinController;
use App\Http\Controllers\Admin\Cashless\PendapatanKantinController;
use App\Http\Controllers\Admin\Cashless\PengajuanTambahanController;
use App\Http\Controllers\Admin\Cashless\RfidKontrolController;
use App\Http\Controllers\Admin\Cashless\SaldoCashlessController;
use App\Http\Controllers\Admin\Cashless\SaldoRfidKioskController;
use App\Http\Controllers\Admin\Cashless\TopupSaldoController;
use App\Http\Controllers\Admin\Cashless\TransaksiController;
use App\Http\Controllers\Admin\Cashless\TransferKantinController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\Finance\BatalkanPembayaranController;
use App\Http\Controllers\Admin\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Admin\Finance\ImportExportController as FinanceImportExportController;
use App\Http\Controllers\Admin\Finance\KasManualController;
use App\Http\Controllers\Admin\Finance\KatalogPotonganController;
use App\Http\Controllers\Admin\Finance\KirimTagihanWaController;
use App\Http\Controllers\Admin\Finance\LaporanKeuanganController;
use App\Http\Controllers\Admin\Finance\LogBatalkanPembayaranController;
use App\Http\Controllers\Admin\Finance\PembayaranController;
use App\Http\Controllers\Admin\Finance\PindahSaldoController;
use App\Http\Controllers\Admin\Finance\PotonganPemakaianController;
use App\Http\Controllers\Admin\Finance\PotonganSiswaController;
use App\Http\Controllers\Admin\Finance\QrisPaymentController;
use App\Http\Controllers\Admin\Finance\RekeningController;
use App\Http\Controllers\Admin\Finance\RiwayatPembayaranController;
use App\Http\Controllers\Admin\Finance\SaldoSiswaController;
use App\Http\Controllers\Admin\Finance\TagihanCicilanController;
use App\Http\Controllers\Admin\Finance\TagihanController;
use App\Http\Controllers\Admin\Finance\TemplatePesanTagihanController;
use App\Http\Controllers\Admin\Library\DashboardController as LibraryDashboardController;
use App\Http\Controllers\Admin\Library\DendaKeterlambatanController;
use App\Http\Controllers\Admin\Library\HistoryPeminjamanController;
use App\Http\Controllers\Admin\Library\ImportBukuController as LibraryImportBukuController;
use App\Http\Controllers\Admin\Library\KatalogBukuController;
use App\Http\Controllers\Admin\Library\PeminjamanController;
use App\Http\Controllers\Admin\Library\PengembalianBukuController;
use App\Http\Controllers\Admin\Library\RatingReviewController;
use App\Http\Controllers\Admin\Library\SearchBukuController;
use App\Http\Controllers\Admin\Library\SettingDendaController as LibrarySettingDendaController;
use App\Http\Controllers\Admin\MasterData\JenisTagihanController as MasterDataJenisTagihanController;
use App\Http\Controllers\Admin\MasterData\KamarController as MasterDataKamarController;
use App\Http\Controllers\Admin\MasterData\KelasController as MasterDataKelasController;
use App\Http\Controllers\Admin\MasterData\SekolahController as MasterDataSekolahController;
use App\Http\Controllers\Admin\MasterData\StatusSantriController as MasterDataStatusSantriController;
use App\Http\Controllers\Admin\MasterData\TahunAkademikController as MasterDataTahunAkademikController;
use App\Http\Controllers\Admin\ModuleSettingController;
use App\Http\Controllers\Admin\OrangTuaLookupController;
use App\Http\Controllers\Admin\PeminjamanLookupController;
use App\Http\Controllers\Admin\Perizinan\DashboardController as PerizinanDashboardController;
use App\Http\Controllers\Admin\Perizinan\IzinKeluarMasukController;
use App\Http\Controllers\Admin\Perizinan\IzinKeluarMasukPondokController;
use App\Http\Controllers\Admin\Perizinan\IzinPulangLiburController;
use App\Http\Controllers\Admin\Perizinan\RekapLaporanController as PerizinanRekapLaporanController;
use App\Http\Controllers\Admin\Spmb\BeritaController as AdminSpmbBeritaController;
use App\Http\Controllers\Admin\Spmb\DashboardController as AdminSpmbDashboardController;
use App\Http\Controllers\Admin\Spmb\GaleriController as AdminSpmbGaleriController;
use App\Http\Controllers\Admin\Spmb\PendaftarController as AdminSpmbPendaftarController;
use App\Http\Controllers\Admin\Spmb\PengumumanController as AdminSpmbPengumumanController;
use App\Http\Controllers\Admin\Spmb\PeriodeController as AdminSpmbPeriodeController;
use App\Http\Controllers\Admin\StudentLookupController;
use App\Http\Controllers\Admin\StudentManagement\BerkasSiswaController;
use App\Http\Controllers\Admin\StudentManagement\DashboardController as StudentDashboardController;
use App\Http\Controllers\Admin\StudentManagement\DataSiswaController;
use App\Http\Controllers\Admin\StudentManagement\ImportExportController as StudentImportExportController;
use App\Http\Controllers\Admin\StudentManagement\KartuPelajarController;
use App\Http\Controllers\Admin\StudentManagement\OrangTuaController;
use App\Http\Controllers\Admin\StudentManagement\PelanggaranSiswaController;
use App\Http\Controllers\Admin\StudentManagement\PindahKelasController;
use App\Http\Controllers\Admin\StudentManagement\PortalAccessController;
use App\Http\Controllers\Admin\StudentManagement\PrestasiSiswaController as AdminPrestasiSiswaController;
use App\Http\Controllers\Admin\StudentManagement\ProfilSiswaController;
use App\Http\Controllers\Admin\StudentManagement\RiwayatAkademikController;
use App\Http\Controllers\Admin\TagihanLookupController;
use App\Http\Controllers\Admin\Tahfidz\DashboardController as TahfidzDashboardController;
use App\Http\Controllers\Admin\Tahfidz\HalaqohController as AdminTahfidzHalaqohController;
use App\Http\Controllers\Admin\Tahfidz\JadwalController as AdminTahfidzJadwalController;
use App\Http\Controllers\Admin\Tahfidz\KirimRekapWaController as AdminTahfidzKirimRekapWaController;
use App\Http\Controllers\Admin\Tahfidz\ProgramController as AdminTahfidzProgramController;
use App\Http\Controllers\Admin\Tahfidz\ProgressController as AdminTahfidzProgressController;
use App\Http\Controllers\Admin\Tahfidz\RekapController as AdminTahfidzRekapController;
use App\Http\Controllers\Admin\Tahfidz\TargetController as AdminTahfidzTargetController;
use App\Http\Controllers\Admin\TeacherLookupController;
use App\Http\Controllers\Admin\TeacherManagement\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Admin\TeacherManagement\DataGuruController;
use App\Http\Controllers\Admin\TeacherManagement\ImportExportController as TeacherImportExportController;
use App\Http\Controllers\Admin\TeacherManagement\KartuGuruController;
use App\Http\Controllers\Admin\TeacherManagement\PelanggaranGuruController;
use App\Http\Controllers\Admin\TeacherManagement\PrestasiGuruController;
use App\Http\Controllers\Admin\TeacherManagement\ProfilGuruController;
use App\Http\Controllers\Admin\TeacherManagement\RiwayatMengajarController;
use App\Http\Controllers\Admin\UiComponentsController;
use App\Http\Controllers\Admin\Ujian\DashboardController as UjianDashboardController;
use App\Http\Controllers\Admin\Ujian\UjianController as AdminUjianController;
use App\Http\Controllers\Alumni\TracerController as PublicAlumniTracerController;
use App\Http\Controllers\Auth\AccessController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Portal\BookletController as PortalBookletController;
use App\Http\Controllers\Portal\Guru\AbsensiController as GuruAbsensiController;
use App\Http\Controllers\Portal\Guru\AbsensiSiswaController as GuruAbsensiSiswaController;
use App\Http\Controllers\Portal\Guru\KartuGuruController as PortalGuruKartuGuruController;
use App\Http\Controllers\Portal\Guru\KatalogPelanggaranController as GuruKatalogPelanggaranController;
use App\Http\Controllers\Portal\Guru\PelanggaranSiswaController as GuruPelanggaranSiswaController;
use App\Http\Controllers\Portal\Guru\PrestasiSiswaController as GuruPrestasiSiswaController;
use App\Http\Controllers\Portal\Guru\ProfilController as GuruProfilController;
use App\Http\Controllers\Portal\Guru\RekapPerizinanController;
use App\Http\Controllers\Portal\Guru\RekapSiswaController as GuruRekapSiswaController;
use App\Http\Controllers\Portal\Guru\TahfidzController as GuruTahfidzController;
use App\Http\Controllers\Portal\GuruDashboardController;
use App\Http\Controllers\Portal\Kantin\MenuController as KantinMenuController;
use App\Http\Controllers\Portal\Kantin\PosController as KantinPosController;
use App\Http\Controllers\Portal\Kantin\ProfilController as KantinProfilController;
use App\Http\Controllers\Portal\Kantin\RingkasanController as KantinRingkasanController;
use App\Http\Controllers\Portal\Kantin\TransaksiController as KantinTransaksiController;
use App\Http\Controllers\Portal\KantinDashboardController;
use App\Http\Controllers\Portal\OrangTua\AbsensiController as OrtuAbsensiController;
use App\Http\Controllers\Portal\OrangTua\AksesTokenController as OrtuAksesTokenController;
use App\Http\Controllers\Portal\OrangTua\AnakController as OrtuAnakController;
use App\Http\Controllers\Portal\OrangTua\CashlessPinController as OrtuCashlessPinController;
use App\Http\Controllers\Portal\OrangTua\DompetController as OrtuDompetController;
use App\Http\Controllers\Portal\OrangTua\KartuPelajarController as OrtuKartuPelajarController;
use App\Http\Controllers\Portal\OrangTua\PelanggaranSiswaController as OrtuPelanggaranSiswaController;
use App\Http\Controllers\Portal\OrangTua\PembayaranController as OrtuPembayaranController;
use App\Http\Controllers\Portal\OrangTua\PerpustakaanController as OrtuPerpustakaanController;
use App\Http\Controllers\Portal\OrangTua\PindahSaldoController as OrtuPindahSaldoController;
use App\Http\Controllers\Portal\OrangTua\PrestasiSiswaController as OrtuPrestasiSiswaController;
use App\Http\Controllers\Portal\OrangTua\QrisPaymentController as OrtuQrisPaymentController;
use App\Http\Controllers\Portal\OrangTua\RaporController as OrtuRaporController;
use App\Http\Controllers\Portal\OrangTua\RekapPerizinanController as OrtuRekapPerizinanController;
use App\Http\Controllers\Portal\OrangTua\RekapPresensiController as OrtuRekapPresensiController;
use App\Http\Controllers\Portal\OrangTua\TagihanController as OrtuTagihanController;
use App\Http\Controllers\Portal\OrangTua\TahfidzController as OrtuTahfidzController;
use App\Http\Controllers\Portal\OrangTuaDashboardController;
use App\Http\Controllers\Portal\Perizinan\DashboardController as PortalPerizinanDashboardController;
use App\Http\Controllers\Portal\Perizinan\IzinKeluarMasukController as PortalIzinKeluarMasukController;
use App\Http\Controllers\Portal\Perizinan\IzinKeluarMasukPondokController as PortalIzinKeluarMasukPondokController;
use App\Http\Controllers\Portal\Perizinan\IzinPulangLiburController as PortalIzinPulangLiburController;
use App\Http\Controllers\Portal\Perizinan\RekapLaporanController as PortalPerizinanRekapLaporanController;
use App\Http\Controllers\Portal\Perpustakaan\BukuLookupController as PerpustakaanBukuLookupController;
use App\Http\Controllers\Portal\Perpustakaan\DashboardController as PerpustakaanDashboardController;
use App\Http\Controllers\Portal\Perpustakaan\DendaKeterlambatanController as PerpustakaanDendaController;
use App\Http\Controllers\Portal\Perpustakaan\HistoryPeminjamanController as PerpustakaanHistoryController;
use App\Http\Controllers\Portal\Perpustakaan\ImportBukuController as PerpustakaanImportBukuController;
use App\Http\Controllers\Portal\Perpustakaan\KatalogBukuController as PerpustakaanKatalogController;
use App\Http\Controllers\Portal\Perpustakaan\PeminjamanController as PerpustakaanPeminjamanController;
use App\Http\Controllers\Portal\Perpustakaan\PeminjamanLookupController as PerpustakaanPeminjamanLookupController;
use App\Http\Controllers\Portal\Perpustakaan\PengembalianBukuController as PerpustakaanPengembalianController;
use App\Http\Controllers\Portal\Perpustakaan\RatingReviewController as PerpustakaanRatingController;
use App\Http\Controllers\Portal\Perpustakaan\RekapPengunjungController as PerpustakaanRekapPengunjungController;
use App\Http\Controllers\Portal\Perpustakaan\SearchBukuController as PerpustakaanSearchController;
use App\Http\Controllers\Portal\Perpustakaan\SettingDendaController as PerpustakaanSettingDendaController;
use App\Http\Controllers\Portal\Pimpinan\AbsensiGuruController as PimpinanAbsensiGuruController;
use App\Http\Controllers\Portal\Pimpinan\LaporanAbsensiController as PimpinanLaporanAbsensiController;
use App\Http\Controllers\Portal\Pimpinan\LaporanKantinController;
use App\Http\Controllers\Portal\Pimpinan\LaporanKeuanganController as PimpinanLaporanKeuanganController;
use App\Http\Controllers\Portal\Pimpinan\LaporanPerpustakaanController as PimpinanLaporanPerpustakaanController;
use App\Http\Controllers\Portal\Pimpinan\RekapPresensiController as PimpinanRekapPresensiController;
use App\Http\Controllers\Portal\Pimpinan\RekapPresensiGuruController as PimpinanRekapPresensiGuruController;
use App\Http\Controllers\Portal\PimpinanDashboardController;
use App\Http\Controllers\Portal\PortalEntryController;
use App\Http\Controllers\Portal\PwaController as PortalPwaController;
use App\Http\Controllers\Portal\Siswa\AbsensiController as SiswaAbsensiController;
use App\Http\Controllers\Portal\Siswa\AksesTokenController as SiswaAksesTokenController;
use App\Http\Controllers\Portal\Siswa\DompetController as SiswaDompetController;
use App\Http\Controllers\Portal\Siswa\KartuPelajarController as SiswaKartuPelajarController;
use App\Http\Controllers\Portal\Siswa\PelanggaranSiswaController as SiswaPelanggaranSiswaController;
use App\Http\Controllers\Portal\Siswa\PembayaranController as SiswaPembayaranController;
use App\Http\Controllers\Portal\Siswa\PerpustakaanController as SiswaPerpustakaanController;
use App\Http\Controllers\Portal\Siswa\PrestasiSiswaController as SiswaPrestasiSiswaController;
use App\Http\Controllers\Portal\Siswa\ProfilController as SiswaProfilController;
use App\Http\Controllers\Portal\Siswa\RaporController as SiswaRaporController;
use App\Http\Controllers\Portal\Siswa\TagihanController as SiswaTagihanController;
use App\Http\Controllers\Portal\Siswa\TahfidzController as SiswaTahfidzController;
use App\Http\Controllers\Portal\Siswa\UjianController as SiswaUjianController;
use App\Http\Controllers\Portal\SiswaDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Spmb\LandingController as SpmbLandingController;
use App\Http\Controllers\Spmb\RegistrationController as SpmbRegistrationController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\LoginLogController as SuperAdminLoginLogController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
use App\Support\HomeRedirect;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(HomeRedirect::for(auth()->user())));

Route::get('/access', [AccessController::class, 'show'])->name('access.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
// Membuka /logout langsung di browser (GET) tidak lagi memicu 405 — tetap logout & ke login.
Route::get('/logout', [LoginController::class, 'destroy'])->middleware('auth');

Route::prefix('spmb')->name('spmb.')->group(function () {
    Route::get('/', [SpmbLandingController::class, 'home'])->name('home');
    Route::get('/pengumuman', [SpmbLandingController::class, 'pengumumanIndex'])->name('pengumuman.index');
    Route::get('/pengumuman/{pengumuman}', [SpmbLandingController::class, 'pengumumanShow'])->name('pengumuman.show');
    Route::get('/berita', [SpmbLandingController::class, 'beritaIndex'])->name('berita.index');
    Route::get('/berita/{berita}', [SpmbLandingController::class, 'beritaShow'])->name('berita.show');
    Route::get('/galeri', [SpmbLandingController::class, 'galeriIndex'])->name('galeri.index');
    Route::get('/daftar', [SpmbRegistrationController::class, 'create'])->name('daftar');
    Route::post('/daftar', [SpmbRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('daftar.store');
});

Route::prefix('alumni')->name('alumni.')->group(function () {
    Route::get('/tracer', [PublicAlumniTracerController::class, 'create'])->name('tracer');
    Route::post('/tracer', [PublicAlumniTracerController::class, 'store'])->middleware('throttle:10,1')->name('tracer.store');
});

Route::get('/portal/manifest.webmanifest', [PortalPwaController::class, 'manifest'])->name('portal.pwa.manifest');
Route::get('/portal/sw.js', [PortalPwaController::class, 'serviceWorker'])->name('portal.pwa.service-worker');
Route::get('/portal/offline', [PortalPwaController::class, 'offline'])->name('portal.pwa.offline');
Route::get('/portal', PortalEntryController::class)->name('portal.entry');

Route::middleware('auth')->group(function () {
    Route::get('/profil-akun', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil-akun', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil-akun/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::redirect('/', '/super-admin/dashboard');
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [SuperAdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/data', [SuperAdminUserController::class, 'data'])->name('users.data');
    Route::post('/users', [SuperAdminUserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [SuperAdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset-password', [SuperAdminUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/login-logs', [SuperAdminLoginLogController::class, 'index'])->name('login-logs.index');
    Route::get('/login-logs/data', [SuperAdminLoginLogController::class, 'data'])->name('login-logs.data');
    Route::get('/login-logs/{logLogin}', [SuperAdminLoginLogController::class, 'show'])->name('login-logs.show');
});

Route::middleware(['auth', 'admin.access'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/manajemen-user', [SuperAdminUserController::class, 'index'])->name('manajemen-user.index');
    Route::get('/manajemen-user/data', [SuperAdminUserController::class, 'data'])->name('manajemen-user.data');
    Route::put('/manajemen-user/{user}', [SuperAdminUserController::class, 'update'])->middleware('permission:users.update')->name('manajemen-user.update');
    Route::post('/manajemen-user/{user}/reset-password', [SuperAdminUserController::class, 'resetPassword'])->middleware('permission:users.update')->name('manajemen-user.reset-password');

    if (app()->isLocal()) {
        Route::get('/komponen-ui', [UiComponentsController::class, 'index'])->name('komponen-ui');
    }

    Route::get('/siswa/lookup', [StudentLookupController::class, 'search'])->name('siswa.lookup');
    Route::get('/siswa/lookup/{siswa}', [StudentLookupController::class, 'show'])->name('siswa.lookup.show');
    Route::get('/guru/lookup', [TeacherLookupController::class, 'search'])->name('guru.lookup');
    Route::get('/guru/lookup/{guru}', [TeacherLookupController::class, 'show'])->name('guru.lookup.show');
    Route::get('/orang-tua/lookup', [OrangTuaLookupController::class, 'search'])->name('orang-tua.lookup');
    Route::get('/orang-tua/lookup/{orangTua}', [OrangTuaLookupController::class, 'show'])->name('orang-tua.lookup.show');
    Route::get('/tagihan/lookup', [TagihanLookupController::class, 'search'])->name('tagihan.lookup');
    Route::get('/tagihan/lookup/{tagihan}', [TagihanLookupController::class, 'show'])->name('tagihan.lookup.show');
    Route::get('/peminjaman/lookup', [PeminjamanLookupController::class, 'search'])->name('peminjaman.lookup');
    Route::get('/peminjaman/lookup/{peminjaman}', [PeminjamanLookupController::class, 'show'])->name('peminjaman.lookup.show');
    Route::get('/buku/lookup', [BukuLookupController::class, 'search'])->name('buku.lookup');
    Route::get('/buku/lookup/{buku}', [BukuLookupController::class, 'show'])->name('buku.lookup.show');

    Route::prefix('pengaturan-modul')->name('pengaturan-modul.')->group(function () {
        Route::get('{module}', [ModuleSettingController::class, 'show'])->name('show');
        Route::put('{module}', [ModuleSettingController::class, 'update'])->name('update');
    });

    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/sekolah', [MasterDataSekolahController::class, 'index'])->name('sekolah.index');
        Route::get('/sekolah/data', [MasterDataSekolahController::class, 'data'])->name('sekolah.data');
        Route::post('/sekolah', [MasterDataSekolahController::class, 'store'])->name('sekolah.store');
        Route::put('/sekolah/{sekolah}', [MasterDataSekolahController::class, 'update'])->name('sekolah.update');
        Route::delete('/sekolah/{sekolah}', [MasterDataSekolahController::class, 'destroy'])->name('sekolah.destroy');

        Route::get('/kelas', [MasterDataKelasController::class, 'index'])->name('kelas.index');
        Route::get('/kelas/data', [MasterDataKelasController::class, 'data'])->name('kelas.data');
        Route::post('/kelas', [MasterDataKelasController::class, 'store'])->name('kelas.store');
        Route::put('/kelas/{kelas}', [MasterDataKelasController::class, 'update'])->name('kelas.update');
        Route::delete('/kelas/{kelas}', [MasterDataKelasController::class, 'destroy'])->name('kelas.destroy');

        Route::get('/tahun-akademik', [MasterDataTahunAkademikController::class, 'index'])->name('tahun-akademik.index');
        Route::get('/tahun-akademik/data', [MasterDataTahunAkademikController::class, 'data'])->name('tahun-akademik.data');
        Route::post('/tahun-akademik', [MasterDataTahunAkademikController::class, 'store'])->name('tahun-akademik.store');
        Route::put('/tahun-akademik/{tahunAkademik}', [MasterDataTahunAkademikController::class, 'update'])->name('tahun-akademik.update');
        Route::delete('/tahun-akademik/{tahunAkademik}', [MasterDataTahunAkademikController::class, 'destroy'])->name('tahun-akademik.destroy');

        Route::get('/jenis-tagihan', [MasterDataJenisTagihanController::class, 'index'])->name('jenis-tagihan.index');
        Route::get('/jenis-tagihan/data', [MasterDataJenisTagihanController::class, 'data'])->name('jenis-tagihan.data');
        Route::post('/jenis-tagihan', [MasterDataJenisTagihanController::class, 'store'])->name('jenis-tagihan.store');
        Route::post('/jenis-tagihan/seed-spp-bulanan', [MasterDataJenisTagihanController::class, 'seedSppBulanan'])->name('jenis-tagihan.seed-spp-bulanan');
        Route::put('/jenis-tagihan/{jenisTagihan}', [MasterDataJenisTagihanController::class, 'update'])->name('jenis-tagihan.update');
        Route::delete('/jenis-tagihan/{jenisTagihan}', [MasterDataJenisTagihanController::class, 'destroy'])->name('jenis-tagihan.destroy');

        Route::get('/kamar', [MasterDataKamarController::class, 'index'])->name('kamar.index');
        Route::get('/kamar/data', [MasterDataKamarController::class, 'data'])->name('kamar.data');
        Route::post('/kamar', [MasterDataKamarController::class, 'store'])->name('kamar.store');
        Route::put('/kamar/{kamar}', [MasterDataKamarController::class, 'update'])->name('kamar.update');
        Route::delete('/kamar/{kamar}', [MasterDataKamarController::class, 'destroy'])->name('kamar.destroy');

        Route::get('/status-santri', [MasterDataStatusSantriController::class, 'index'])->name('status-santri.index');
        Route::get('/status-santri/data', [MasterDataStatusSantriController::class, 'data'])->name('status-santri.data');
        Route::post('/status-santri', [MasterDataStatusSantriController::class, 'store'])->name('status-santri.store');
        Route::put('/status-santri/{statusSantri}', [MasterDataStatusSantriController::class, 'update'])->name('status-santri.update');
        Route::delete('/status-santri/{statusSantri}', [MasterDataStatusSantriController::class, 'destroy'])->name('status-santri.destroy');
    });

    // Manajemen siswa
    Route::prefix('manajemen-siswa')->name('manajemen-siswa.')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/data-siswa', [DataSiswaController::class, 'index'])->name('data-siswa.index');
        Route::get('/data-siswa/data', [DataSiswaController::class, 'data'])->name('data-siswa.data');
        Route::get('/data-siswa/stats', [DataSiswaController::class, 'stats'])->name('data-siswa.stats');
        Route::get('/data-siswa/check-va-suffix', [DataSiswaController::class, 'checkVaSuffix'])->name('data-siswa.check-va-suffix');
        Route::get('/data-siswa/{siswa}', [DataSiswaController::class, 'show'])->name('data-siswa.show');
        Route::post('/data-siswa', [DataSiswaController::class, 'store'])->name('data-siswa.store');
        Route::put('/data-siswa/{siswa}', [DataSiswaController::class, 'update'])->name('data-siswa.update');
        Route::delete('/data-siswa/{siswa}', [DataSiswaController::class, 'destroy'])->name('data-siswa.destroy');
        Route::get('/data-siswa/{siswa}/foto-wajah', [DataSiswaController::class, 'showFacePhoto'])->name('data-siswa.foto-wajah');
        Route::post('/data-siswa/{siswa}/rekam-wajah', [DataSiswaController::class, 'storeFaceCapture'])->name('data-siswa.rekam-wajah.store');
        Route::delete('/data-siswa/{siswa}/rekam-wajah', [DataSiswaController::class, 'deleteFaceCapture'])->name('data-siswa.rekam-wajah.destroy');
        Route::post('/data-siswa/{siswa}/portal-access/ortu', [PortalAccessController::class, 'sendOrangTuaWhatsApp'])->name('data-siswa.portal-access.ortu');
        Route::post('/data-siswa/{siswa}/portal-access/ortu/link', [PortalAccessController::class, 'issueOrangTuaLink'])->name('data-siswa.portal-access.ortu.link');
        Route::get('/data-siswa/{siswa}/portal-access/ortu/link', [PortalAccessController::class, 'copyOrangTuaLink'])->name('data-siswa.portal-access.ortu.link.copy');
        Route::post('/data-siswa/{siswa}/portal-access/siswa', [PortalAccessController::class, 'sendSiswaWhatsApp'])->name('data-siswa.portal-access.siswa');
        Route::post('/data-siswa/{siswa}/portal-access/siswa/link', [PortalAccessController::class, 'issueSiswaLink'])->name('data-siswa.portal-access.siswa.link');
        Route::get('/data-siswa/{siswa}/portal-access/siswa/link', [PortalAccessController::class, 'copySiswaLink'])->name('data-siswa.portal-access.siswa.link.copy');
        Route::delete('/data-siswa/{siswa}/portal-access', [PortalAccessController::class, 'revokeSiswaTokens'])->name('data-siswa.portal-access.revoke');
        Route::get('/data-siswa/{siswa}/orang-tua', [DataSiswaController::class, 'orangTua'])->name('data-siswa.orang-tua.index');
        Route::post('/data-siswa/{siswa}/orang-tua', [DataSiswaController::class, 'assignOrangTua'])->name('data-siswa.orang-tua.assign');
        Route::delete('/data-siswa/{siswa}/orang-tua/{orangTua}', [DataSiswaController::class, 'removeOrangTua'])->name('data-siswa.orang-tua.remove');
        Route::get('/orang-tua', [OrangTuaController::class, 'index'])->name('orang-tua.index');
        Route::get('/orang-tua/data', [OrangTuaController::class, 'data'])->name('orang-tua.data');
        Route::post('/orang-tua', [OrangTuaController::class, 'store'])->name('orang-tua.store');
        Route::get('/orang-tua/{orangTua}', [OrangTuaController::class, 'show'])->name('orang-tua.show');
        Route::put('/orang-tua/{orangTua}', [OrangTuaController::class, 'update'])->name('orang-tua.update');
        Route::delete('/orang-tua/{orangTua}', [OrangTuaController::class, 'destroy'])->name('orang-tua.destroy');
        Route::post('/orang-tua/{orangTua}/portal-access', [PortalAccessController::class, 'sendOrangTuaWhatsAppByParent'])->name('orang-tua.portal-access.send');
        Route::post('/orang-tua/{orangTua}/portal-access/link', [PortalAccessController::class, 'issueOrangTuaLinkByParent'])->name('orang-tua.portal-access.link');
        Route::get('/orang-tua/{orangTua}/portal-access/link', [PortalAccessController::class, 'copyOrangTuaLinkByParent'])->name('orang-tua.portal-access.link.copy');
        Route::delete('/orang-tua/{orangTua}/portal-access', [PortalAccessController::class, 'revokeOrangTuaTokens'])->name('orang-tua.portal-access.revoke');
        Route::post('/orang-tua/{orangTua}/assign-siswa', [OrangTuaController::class, 'assignSiswa'])->name('orang-tua.assign-siswa');
        Route::delete('/orang-tua/{orangTua}/siswa/{siswa}', [OrangTuaController::class, 'removeSiswa'])->name('orang-tua.remove-siswa');
        Route::get('/profil-siswa', [ProfilSiswaController::class, 'index'])->name('profil-siswa');
        Route::get('/profil-siswa/data', [ProfilSiswaController::class, 'data'])->name('profil-siswa.data');
        Route::put('/profil-siswa/{siswa}', [ProfilSiswaController::class, 'update'])->name('profil-siswa.update');
        Route::post('/profil-siswa/{siswa}/photo', [ProfilSiswaController::class, 'uploadPhoto'])->name('profil-siswa.photo');
        Route::get('/berkas-siswa', [BerkasSiswaController::class, 'index'])->name('berkas-siswa');
        Route::get('/berkas-siswa/data', [BerkasSiswaController::class, 'data'])->name('berkas-siswa.data');
        Route::get('/riwayat-akademik', [RiwayatAkademikController::class, 'index'])->name('riwayat-akademik');
        Route::get('/riwayat-akademik/data', [RiwayatAkademikController::class, 'data'])->name('riwayat-akademik.data');
        Route::get('/pindah-kelas', [PindahKelasController::class, 'index'])->name('pindah-kelas');
        Route::get('/pindah-kelas/data', [PindahKelasController::class, 'data'])->name('pindah-kelas.data');
        Route::post('/pindah-kelas', [PindahKelasController::class, 'store'])->name('pindah-kelas.store');
        Route::get('/kartu-pelajar', [KartuPelajarController::class, 'index'])->name('kartu-pelajar');
        Route::get('/impor-ekspor', [StudentImportExportController::class, 'index'])->name('impor-ekspor');
        Route::get('/impor-ekspor/template', [StudentImportExportController::class, 'template'])->name('impor-ekspor.template');
        Route::get('/impor-ekspor/export', [StudentImportExportController::class, 'export'])->name('impor-ekspor.export');
        Route::get('/impor-ekspor/preview', [StudentImportExportController::class, 'showPreview'])->name('impor-ekspor.preview.show');
        Route::post('/impor-ekspor/preview', [StudentImportExportController::class, 'storePreview'])->name('impor-ekspor.preview');
        Route::delete('/impor-ekspor/preview', [StudentImportExportController::class, 'clearPreview'])->name('impor-ekspor.preview.clear');
        Route::post('/impor-ekspor/confirm', [StudentImportExportController::class, 'confirmImport'])->name('impor-ekspor.confirm');

    });

    // Manajemen guru
    Route::prefix('manajemen-guru')->name('manajemen-guru.')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        Route::get('/data-guru', [DataGuruController::class, 'index'])->name('data-guru.index');
        Route::get('/data-guru/data', [DataGuruController::class, 'data'])->name('data-guru.data');
        Route::get('/data-guru/{guru}', [DataGuruController::class, 'show'])->name('data-guru.show');
        Route::post('/data-guru', [DataGuruController::class, 'store'])->name('data-guru.store');
        Route::put('/data-guru/{guru}', [DataGuruController::class, 'update'])->name('data-guru.update');
        Route::post('/data-guru/{guru}/buat-akun', [DataGuruController::class, 'createAccount'])->name('data-guru.create-account');
        Route::get('/data-guru/{guru}/akun', [DataGuruController::class, 'showAccount'])->name('data-guru.account.show');
        Route::post('/data-guru/{guru}/reset-password', [DataGuruController::class, 'resetAccountPassword'])->name('data-guru.account.reset-password');
        Route::delete('/data-guru/{guru}', [DataGuruController::class, 'destroy'])->name('data-guru.destroy');
        Route::get('/riwayat-mengajar', [RiwayatMengajarController::class, 'index'])->name('riwayat-mengajar.index');
        Route::get('/riwayat-mengajar/data', [RiwayatMengajarController::class, 'data'])->name('riwayat-mengajar.data');
        Route::get('/profil-guru', [ProfilGuruController::class, 'index'])->name('profil-guru');
        Route::get('/profil-guru/data', [ProfilGuruController::class, 'data'])->name('profil-guru.data');
        Route::post('/profil-guru/{guru}/photo', [ProfilGuruController::class, 'uploadPhoto'])->name('profil-guru.photo');
        Route::get('/kartu-guru', [KartuGuruController::class, 'index'])->name('kartu-guru');
        Route::get('/impor-ekspor', [TeacherImportExportController::class, 'index'])->name('impor-ekspor');
        Route::get('/impor-ekspor/template', [TeacherImportExportController::class, 'template'])->name('impor-ekspor.template');
        Route::get('/impor-ekspor/export', [TeacherImportExportController::class, 'export'])->name('impor-ekspor.export');
        Route::get('/impor-ekspor/preview', [TeacherImportExportController::class, 'showPreview'])->name('impor-ekspor.preview.show');
        Route::post('/impor-ekspor/preview', [TeacherImportExportController::class, 'storePreview'])->name('impor-ekspor.preview');
        Route::delete('/impor-ekspor/preview', [TeacherImportExportController::class, 'clearPreview'])->name('impor-ekspor.preview.clear');
        Route::post('/impor-ekspor/confirm', [TeacherImportExportController::class, 'confirmImport'])->name('impor-ekspor.confirm');

    });

    Route::prefix('spmb')->name('spmb.')->group(function () {
        Route::get('/dashboard', [AdminSpmbDashboardController::class, 'index'])->name('dashboard');

        Route::get('/periode', [AdminSpmbPeriodeController::class, 'index'])->name('periode.index');
        Route::get('/periode/data', [AdminSpmbPeriodeController::class, 'data'])->name('periode.data');
        Route::post('/periode', [AdminSpmbPeriodeController::class, 'store'])->name('periode.store');
        Route::put('/periode/{periode}', [AdminSpmbPeriodeController::class, 'update'])->name('periode.update');
        Route::delete('/periode/{periode}', [AdminSpmbPeriodeController::class, 'destroy'])->name('periode.destroy');

        Route::get('/pendaftar', [AdminSpmbPendaftarController::class, 'index'])->name('pendaftar.index');
        Route::get('/pendaftar/data', [AdminSpmbPendaftarController::class, 'data'])->name('pendaftar.data');
        Route::get('/pendaftar/{pendaftar}', [AdminSpmbPendaftarController::class, 'show'])->name('pendaftar.show');
        Route::post('/pendaftar/{pendaftar}/verify', [AdminSpmbPendaftarController::class, 'verify'])->name('pendaftar.verify');
        Route::post('/pendaftar/{pendaftar}/accept', [AdminSpmbPendaftarController::class, 'accept'])->name('pendaftar.accept');
        Route::post('/pendaftar/{pendaftar}/reject', [AdminSpmbPendaftarController::class, 'reject'])->name('pendaftar.reject');

        Route::get('/pengumuman', [AdminSpmbPengumumanController::class, 'index'])->name('pengumuman.index');
        Route::get('/pengumuman/data', [AdminSpmbPengumumanController::class, 'data'])->name('pengumuman.data');
        Route::post('/pengumuman', [AdminSpmbPengumumanController::class, 'store'])->name('pengumuman.store');
        Route::put('/pengumuman/{pengumuman}', [AdminSpmbPengumumanController::class, 'update'])->name('pengumuman.update');
        Route::delete('/pengumuman/{pengumuman}', [AdminSpmbPengumumanController::class, 'destroy'])->name('pengumuman.destroy');

        Route::get('/berita', [AdminSpmbBeritaController::class, 'index'])->name('berita.index');
        Route::get('/berita/data', [AdminSpmbBeritaController::class, 'data'])->name('berita.data');
        Route::post('/berita', [AdminSpmbBeritaController::class, 'store'])->name('berita.store');
        Route::put('/berita/{berita}', [AdminSpmbBeritaController::class, 'update'])->name('berita.update');
        Route::delete('/berita/{berita}', [AdminSpmbBeritaController::class, 'destroy'])->name('berita.destroy');

        Route::get('/galeri', [AdminSpmbGaleriController::class, 'index'])->name('galeri.index');
        Route::get('/galeri/data', [AdminSpmbGaleriController::class, 'data'])->name('galeri.data');
        Route::post('/galeri', [AdminSpmbGaleriController::class, 'store'])->name('galeri.store');
        Route::put('/galeri/{galeri}', [AdminSpmbGaleriController::class, 'update'])->name('galeri.update');
        Route::delete('/galeri/{galeri}', [AdminSpmbGaleriController::class, 'destroy'])->name('galeri.destroy');
    });

    Route::prefix('akademik')->name('akademik.')->group(function () {
        Route::get('/dashboard', [AkademikDashboardController::class, 'index'])->name('dashboard');

        Route::get('/mata-pelajaran', [MataPelajaranController::class, 'index'])->name('mata-pelajaran.index');
        Route::get('/mata-pelajaran/data', [MataPelajaranController::class, 'data'])->name('mata-pelajaran.data');
        Route::post('/mata-pelajaran', [MataPelajaranController::class, 'store'])->name('mata-pelajaran.store');
        Route::put('/mata-pelajaran/{mataPelajaran}', [MataPelajaranController::class, 'update'])->name('mata-pelajaran.update');
        Route::delete('/mata-pelajaran/{mataPelajaran}', [MataPelajaranController::class, 'destroy'])->name('mata-pelajaran.destroy');

        Route::get('/kurikulum', [KurikulumController::class, 'index'])->name('kurikulum.index');
        Route::get('/kurikulum/data', [KurikulumController::class, 'data'])->name('kurikulum.data');
        Route::post('/kurikulum', [KurikulumController::class, 'store'])->name('kurikulum.store');
        Route::get('/kurikulum/{kurikulum}', [KurikulumController::class, 'show'])->name('kurikulum.show');
        Route::put('/kurikulum/{kurikulum}', [KurikulumController::class, 'update'])->name('kurikulum.update');
        Route::delete('/kurikulum/{kurikulum}', [KurikulumController::class, 'destroy'])->name('kurikulum.destroy');
        Route::post('/kurikulum/{kurikulum}/mapel', [KurikulumController::class, 'storeMapel'])->name('kurikulum.mapel.store');
        Route::delete('/kurikulum/{kurikulum}/mapel/{kurikulumMapel}', [KurikulumController::class, 'destroyMapel'])->name('kurikulum.mapel.destroy');
        Route::post('/kurikulum/mapel/{kurikulumMapel}/kd', [KurikulumController::class, 'storeKd'])->name('kurikulum.kd.store');
        Route::delete('/kurikulum/mapel/{kurikulumMapel}/kd/{kompetensiDasar}', [KurikulumController::class, 'destroyKd'])->name('kurikulum.kd.destroy');

        Route::get('/jadwal-pelajaran', [JadwalPelajaranController::class, 'index'])->name('jadwal-pelajaran.index');
        Route::get('/jadwal-pelajaran/data', [JadwalPelajaranController::class, 'data'])->name('jadwal-pelajaran.data');
        Route::post('/jadwal-pelajaran', [JadwalPelajaranController::class, 'store'])->name('jadwal-pelajaran.store');
        Route::get('/jadwal-pelajaran/{jadwalPelajaran}', [JadwalPelajaranController::class, 'show'])->name('jadwal-pelajaran.show');
        Route::put('/jadwal-pelajaran/{jadwalPelajaran}', [JadwalPelajaranController::class, 'update'])->name('jadwal-pelajaran.update');
        Route::delete('/jadwal-pelajaran/{jadwalPelajaran}', [JadwalPelajaranController::class, 'destroy'])->name('jadwal-pelajaran.destroy');
        Route::post('/jadwal-pelajaran/{jadwalPelajaran}/slots', [JadwalPelajaranController::class, 'storeSlot'])->name('jadwal-pelajaran.slots.store');
        Route::delete('/jadwal-pelajaran/{jadwalPelajaran}/slots/{slot}', [JadwalPelajaranController::class, 'destroySlot'])->name('jadwal-pelajaran.slots.destroy');

        Route::get('/kalender', [KalenderPendidikanController::class, 'index'])->name('kalender.index');
        Route::get('/kalender/data', [KalenderPendidikanController::class, 'data'])->name('kalender.data');
        Route::post('/kalender', [KalenderPendidikanController::class, 'store'])->name('kalender.store');
        Route::put('/kalender/{kalender}', [KalenderPendidikanController::class, 'update'])->name('kalender.update');
        Route::delete('/kalender/{kalender}', [KalenderPendidikanController::class, 'destroy'])->name('kalender.destroy');

        Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai.index');
        Route::get('/nilai/data', [NilaiController::class, 'data'])->name('nilai.data');
        Route::post('/nilai', [NilaiController::class, 'store'])->name('nilai.store');
        Route::put('/nilai/{nilai}', [NilaiController::class, 'update'])->name('nilai.update');
        Route::delete('/nilai/{nilai}', [NilaiController::class, 'destroy'])->name('nilai.destroy');

        Route::get('/rapor', [AdminRaporController::class, 'index'])->name('rapor.index');
        Route::get('/rapor/data', [AdminRaporController::class, 'data'])->name('rapor.data');
        Route::get('/rapor/{rapor}', [AdminRaporController::class, 'show'])->name('rapor.show');
        Route::post('/rapor/build', [AdminRaporController::class, 'build'])->name('rapor.build');
        Route::post('/rapor/{rapor}/finalize', [AdminRaporController::class, 'finalize'])->name('rapor.finalize');

        Route::get('/laporan-nilai', [LaporanNilaiController::class, 'index'])->name('laporan-nilai.index');
        Route::get('/laporan-nilai/data', [LaporanNilaiController::class, 'data'])->name('laporan-nilai.data');
    });

    Route::prefix('ujian')->name('ujian.')->group(function () {
        Route::get('/dashboard', [UjianDashboardController::class, 'index'])->name('dashboard');

        Route::get('/', [AdminUjianController::class, 'index'])->name('ujian.index');
        Route::get('/data', [AdminUjianController::class, 'data'])->name('ujian.data');
        Route::post('/', [AdminUjianController::class, 'store'])->name('ujian.store');
        Route::get('/{ujian}', [AdminUjianController::class, 'show'])->name('ujian.show');
        Route::put('/{ujian}', [AdminUjianController::class, 'update'])->name('ujian.update');
        Route::delete('/{ujian}', [AdminUjianController::class, 'destroy'])->name('ujian.destroy');
        Route::post('/{ujian}/publish', [AdminUjianController::class, 'publish'])->name('ujian.publish');
        Route::post('/{ujian}/close', [AdminUjianController::class, 'close'])->name('ujian.close');
        Route::post('/{ujian}/soal', [AdminUjianController::class, 'storeSoal'])->name('ujian.soal.store');
        Route::delete('/{ujian}/soal/{soal}', [AdminUjianController::class, 'destroySoal'])->name('ujian.soal.destroy');
    });

    Route::prefix('booklet')->name('booklet.')->group(function () {
        Route::get('/dashboard', [BookletDashboardController::class, 'index'])->name('dashboard');

        Route::get('/', [AdminBookletController::class, 'index'])->name('booklet.index');
        Route::get('/data', [AdminBookletController::class, 'data'])->name('booklet.data');
        Route::post('/', [AdminBookletController::class, 'store'])->name('booklet.store');
        Route::get('/{booklet}', [AdminBookletController::class, 'show'])->name('booklet.show');
        Route::put('/{booklet}', [AdminBookletController::class, 'update'])->name('booklet.update');
        Route::delete('/{booklet}', [AdminBookletController::class, 'destroy'])->name('booklet.destroy');
        Route::post('/{booklet}/pages', [AdminBookletController::class, 'storePage'])->name('booklet.pages.store');
        Route::delete('/{booklet}/pages/{page}', [AdminBookletController::class, 'destroyPage'])->name('booklet.pages.destroy');
    });

    Route::prefix('alumni')->name('alumni.')->group(function () {
        Route::get('/dashboard', [AlumniDashboardController::class, 'index'])->name('dashboard');

        Route::get('/tracer', [AdminAlumniTracerController::class, 'index'])->name('tracer.index');
        Route::get('/tracer/data', [AdminAlumniTracerController::class, 'data'])->name('tracer.data');

        Route::get('/', [AdminAlumniController::class, 'index'])->name('alumni.index');
        Route::get('/data', [AdminAlumniController::class, 'data'])->name('alumni.data');
        Route::post('/', [AdminAlumniController::class, 'store'])->name('alumni.store');
        Route::get('/{alumni}', [AdminAlumniController::class, 'show'])->name('alumni.show');
        Route::put('/{alumni}', [AdminAlumniController::class, 'update'])->name('alumni.update');
        Route::delete('/{alumni}', [AdminAlumniController::class, 'destroy'])->name('alumni.destroy');
        Route::post('/{alumni}/tracers', [AdminAlumniController::class, 'storeTracer'])->name('alumni.tracers.store');
        Route::delete('/{alumni}/tracers/{tracer}', [AdminAlumniController::class, 'destroyTracer'])->name('alumni.tracers.destroy');
    });

    Route::prefix('tahfidz')->name('tahfidz.')->group(function () {
        Route::get('/dashboard', [TahfidzDashboardController::class, 'index'])->name('dashboard');

        Route::get('/program', [AdminTahfidzProgramController::class, 'index'])->name('program.index');
        Route::get('/program/data', [AdminTahfidzProgramController::class, 'data'])->name('program.data');
        Route::post('/program', [AdminTahfidzProgramController::class, 'store'])->name('program.store');
        Route::put('/program/{program}', [AdminTahfidzProgramController::class, 'update'])->name('program.update');
        Route::delete('/program/{program}', [AdminTahfidzProgramController::class, 'destroy'])->name('program.destroy');

        Route::get('/halaqoh', [AdminTahfidzHalaqohController::class, 'index'])->name('halaqoh.index');
        Route::get('/halaqoh/data', [AdminTahfidzHalaqohController::class, 'data'])->name('halaqoh.data');
        Route::post('/halaqoh', [AdminTahfidzHalaqohController::class, 'store'])->name('halaqoh.store');
        Route::get('/halaqoh/{halaqoh}', [AdminTahfidzHalaqohController::class, 'show'])->name('halaqoh.show');
        Route::put('/halaqoh/{halaqoh}', [AdminTahfidzHalaqohController::class, 'update'])->name('halaqoh.update');
        Route::delete('/halaqoh/{halaqoh}', [AdminTahfidzHalaqohController::class, 'destroy'])->name('halaqoh.destroy');
        Route::post('/halaqoh/{halaqoh}/anggota', [AdminTahfidzHalaqohController::class, 'storeAnggota'])->name('halaqoh.anggota.store');
        Route::delete('/halaqoh/{halaqoh}/anggota/{tahfidzHalaqohAnggota}', [AdminTahfidzHalaqohController::class, 'destroyAnggota'])->name('halaqoh.anggota.destroy');

        Route::get('/progress', [AdminTahfidzProgressController::class, 'index'])->name('progress.index');
        Route::get('/progress/data', [AdminTahfidzProgressController::class, 'data'])->name('progress.data');
        Route::post('/progress', [AdminTahfidzProgressController::class, 'store'])->name('progress.store');
        Route::put('/progress/{progress}', [AdminTahfidzProgressController::class, 'update'])->name('progress.update');
        Route::delete('/progress/{progress}', [AdminTahfidzProgressController::class, 'destroy'])->name('progress.destroy');

        Route::get('/target', [AdminTahfidzTargetController::class, 'index'])->name('target.index');
        Route::get('/target/data', [AdminTahfidzTargetController::class, 'data'])->name('target.data');
        Route::post('/target', [AdminTahfidzTargetController::class, 'store'])->name('target.store');
        Route::put('/target/{target}', [AdminTahfidzTargetController::class, 'update'])->name('target.update');
        Route::delete('/target/{target}', [AdminTahfidzTargetController::class, 'destroy'])->name('target.destroy');

        Route::get('/jadwal', [AdminTahfidzJadwalController::class, 'index'])->name('jadwal.index');
        Route::get('/jadwal/data', [AdminTahfidzJadwalController::class, 'data'])->name('jadwal.data');
        Route::post('/jadwal', [AdminTahfidzJadwalController::class, 'store'])->name('jadwal.store');
        Route::put('/jadwal/{jadwal}', [AdminTahfidzJadwalController::class, 'update'])->name('jadwal.update');
        Route::delete('/jadwal/{jadwal}', [AdminTahfidzJadwalController::class, 'destroy'])->name('jadwal.destroy');

        Route::get('/rekap', [AdminTahfidzRekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/data', [AdminTahfidzRekapController::class, 'data'])->name('rekap.data');
        Route::post('/rekap', [AdminTahfidzRekapController::class, 'store'])->name('rekap.store');
        Route::get('/rekap/{rekap}', [AdminTahfidzRekapController::class, 'show'])->name('rekap.show');
        Route::post('/rekap/{rekap}/siap', [AdminTahfidzRekapController::class, 'markSiap'])->name('rekap.siap');
        Route::put('/rekap/{rekap}/baris/{tahfidzRekapSiswa}', [AdminTahfidzRekapController::class, 'updateBaris'])->name('rekap.baris.update');

        Route::get('/kirim-wa', [AdminTahfidzKirimRekapWaController::class, 'index'])->name('kirim-wa.index');
        Route::get('/kirim-wa/data', [AdminTahfidzKirimRekapWaController::class, 'data'])->name('kirim-wa.data');
        Route::post('/kirim-wa/build', [AdminTahfidzKirimRekapWaController::class, 'build'])->name('kirim-wa.build');
        Route::get('/kirim-wa/{rekap}/preview', [AdminTahfidzKirimRekapWaController::class, 'preview'])->name('kirim-wa.preview');
    });

    // Perizinan
    Route::prefix('perizinan')->name('perizinan.')->group(function () {
        Route::get('/dashboard', [PerizinanDashboardController::class, 'index'])->name('dashboard');

        // Izin Keluar Masuk Harian
        Route::get('/keluar-masuk', [IzinKeluarMasukController::class, 'index'])->name('keluar-masuk.index');
        Route::get('/keluar-masuk/data', [IzinKeluarMasukController::class, 'data'])->name('keluar-masuk.data');
        Route::post('/keluar-masuk', [IzinKeluarMasukController::class, 'store'])->name('keluar-masuk.store');
        Route::get('/keluar-masuk/{keluar_masuk}', [IzinKeluarMasukController::class, 'show'])->name('keluar-masuk.show');
        Route::put('/keluar-masuk/{keluar_masuk}', [IzinKeluarMasukController::class, 'update'])->name('keluar-masuk.update');
        Route::delete('/keluar-masuk/{keluar_masuk}', [IzinKeluarMasukController::class, 'destroy'])->name('keluar-masuk.destroy');
        Route::post('/keluar-masuk/{keluar_masuk}/checkin', [IzinKeluarMasukController::class, 'checkin'])->name('keluar-masuk.checkin');
        Route::get('/keluar-masuk/{keluar_masuk}/checkin/preview', [IzinKeluarMasukController::class, 'checkinPreview'])->name('keluar-masuk.checkin.preview');

        // Izin Keluar Masuk Pondok
        Route::get('/keluar-masuk-pondok', [IzinKeluarMasukPondokController::class, 'index'])->name('keluar-masuk-pondok.index');
        Route::get('/keluar-masuk-pondok/data', [IzinKeluarMasukPondokController::class, 'data'])->name('keluar-masuk-pondok.data');
        Route::post('/keluar-masuk-pondok', [IzinKeluarMasukPondokController::class, 'store'])->name('keluar-masuk-pondok.store');
        Route::get('/keluar-masuk-pondok/{keluar_masuk_pondok}', [IzinKeluarMasukPondokController::class, 'show'])->name('keluar-masuk-pondok.show');
        Route::put('/keluar-masuk-pondok/{keluar_masuk_pondok}', [IzinKeluarMasukPondokController::class, 'update'])->name('keluar-masuk-pondok.update');
        Route::delete('/keluar-masuk-pondok/{keluar_masuk_pondok}', [IzinKeluarMasukPondokController::class, 'destroy'])->name('keluar-masuk-pondok.destroy');
        Route::post('/keluar-masuk-pondok/{keluar_masuk_pondok}/checkin', [IzinKeluarMasukPondokController::class, 'checkin'])->name('keluar-masuk-pondok.checkin');
        Route::get('/keluar-masuk-pondok/{keluar_masuk_pondok}/checkin/preview', [IzinKeluarMasukPondokController::class, 'checkinPreview'])->name('keluar-masuk-pondok.checkin.preview');

        // Izin Pulang Libur
        Route::get('/pulang-libur', [IzinPulangLiburController::class, 'index'])->name('pulang-libur.index');
        Route::get('/pulang-libur/data', [IzinPulangLiburController::class, 'data'])->name('pulang-libur.data');
        Route::post('/pulang-libur', [IzinPulangLiburController::class, 'store'])->name('pulang-libur.store');
        Route::get('/pulang-libur/{pulang_libur}', [IzinPulangLiburController::class, 'show'])->name('pulang-libur.show');
        Route::put('/pulang-libur/{pulang_libur}', [IzinPulangLiburController::class, 'update'])->name('pulang-libur.update');
        Route::delete('/pulang-libur/{pulang_libur}', [IzinPulangLiburController::class, 'destroy'])->name('pulang-libur.destroy');
        Route::post('/pulang-libur/{pulang_libur}/checkin', [IzinPulangLiburController::class, 'checkin'])->name('pulang-libur.checkin');
        Route::get('/pulang-libur/{pulang_libur}/checkin/preview', [IzinPulangLiburController::class, 'checkinPreview'])->name('pulang-libur.checkin.preview');

        // Rekap & Laporan
        Route::get('/rekap-laporan', [PerizinanRekapLaporanController::class, 'index'])->name('rekap-laporan');
        Route::get('/rekap-laporan/data', [PerizinanRekapLaporanController::class, 'data'])->name('rekap-laporan.data');
    });

    // Prestasi & Pelanggaran
    Route::prefix('prestasi-pelanggaran')->name('prestasi-pelanggaran.')->group(function () {
        Route::get('/dashboard', [AchievementViolationDashboardController::class, 'index'])->name('dashboard');

        // Katalog Pelanggaran (master)
        Route::get('/katalog-pelanggaran', [KatalogPelanggaranController::class, 'index'])->name('katalog-pelanggaran.index');
        Route::get('/katalog-pelanggaran/data', [KatalogPelanggaranController::class, 'data'])->name('katalog-pelanggaran.data');
        Route::post('/katalog-pelanggaran', [KatalogPelanggaranController::class, 'store'])->name('katalog-pelanggaran.store');
        Route::put('/katalog-pelanggaran/{jenisPelanggaran}', [KatalogPelanggaranController::class, 'update'])->name('katalog-pelanggaran.update');
        Route::delete('/katalog-pelanggaran/{jenisPelanggaran}', [KatalogPelanggaranController::class, 'destroy'])->name('katalog-pelanggaran.destroy');
        Route::get('/katalog-pelanggaran/lookup', [KatalogPelanggaranController::class, 'lookup'])->name('katalog-pelanggaran.lookup');
        Route::get('/katalog-pelanggaran/lookup/{jenisPelanggaran}', [KatalogPelanggaranController::class, 'lookupShow'])->name('katalog-pelanggaran.lookup.show');

        // Katalog Prestasi (master)
        Route::get('/katalog-prestasi', [KatalogPrestasiController::class, 'index'])->name('katalog-prestasi.index');
        Route::get('/katalog-prestasi/data', [KatalogPrestasiController::class, 'data'])->name('katalog-prestasi.data');
        Route::post('/katalog-prestasi', [KatalogPrestasiController::class, 'store'])->name('katalog-prestasi.store');
        Route::put('/katalog-prestasi/{jenisPrestasi}', [KatalogPrestasiController::class, 'update'])->name('katalog-prestasi.update');
        Route::delete('/katalog-prestasi/{jenisPrestasi}', [KatalogPrestasiController::class, 'destroy'])->name('katalog-prestasi.destroy');
        Route::get('/katalog-prestasi/lookup', [KatalogPrestasiController::class, 'lookup'])->name('katalog-prestasi.lookup');
        Route::get('/katalog-prestasi/lookup/{jenisPrestasi}', [KatalogPrestasiController::class, 'lookupShow'])->name('katalog-prestasi.lookup.show');

        // Prestasi Siswa
        Route::get('/prestasi-siswa', [AdminPrestasiSiswaController::class, 'index'])->name('prestasi-siswa.index');
        Route::get('/prestasi-siswa/data', [AdminPrestasiSiswaController::class, 'data'])->name('prestasi-siswa.data');
        Route::post('/prestasi-siswa', [AdminPrestasiSiswaController::class, 'store'])->name('prestasi-siswa.store');
        Route::get('/prestasi-siswa/{prestasiSiswa}', [AdminPrestasiSiswaController::class, 'show'])->name('prestasi-siswa.show');
        Route::put('/prestasi-siswa/{prestasiSiswa}', [AdminPrestasiSiswaController::class, 'update'])->name('prestasi-siswa.update');
        Route::delete('/prestasi-siswa/{prestasiSiswa}', [AdminPrestasiSiswaController::class, 'destroy'])->name('prestasi-siswa.destroy');

        // Pelanggaran Siswa
        Route::get('/pelanggaran-siswa', [PelanggaranSiswaController::class, 'index'])->name('pelanggaran-siswa.index');
        Route::get('/pelanggaran-siswa/data', [PelanggaranSiswaController::class, 'data'])->name('pelanggaran-siswa.data');
        Route::post('/pelanggaran-siswa', [PelanggaranSiswaController::class, 'store'])->name('pelanggaran-siswa.store');
        Route::get('/pelanggaran-siswa/{pelanggaranSiswa}', [PelanggaranSiswaController::class, 'show'])->name('pelanggaran-siswa.show');
        Route::put('/pelanggaran-siswa/{pelanggaranSiswa}', [PelanggaranSiswaController::class, 'update'])->name('pelanggaran-siswa.update');
        Route::delete('/pelanggaran-siswa/{pelanggaranSiswa}', [PelanggaranSiswaController::class, 'destroy'])->name('pelanggaran-siswa.destroy');

        // Rekap per siswa (sorted by total point)
        Route::get('/rekap-prestasi-siswa', [RekapPrestasiSiswaController::class, 'index'])->name('rekap-prestasi-siswa.index');
        Route::get('/rekap-prestasi-siswa/data', [RekapPrestasiSiswaController::class, 'data'])->name('rekap-prestasi-siswa.data');
        Route::get('/rekap-pelanggaran-siswa', [RekapPelanggaranSiswaController::class, 'index'])->name('rekap-pelanggaran-siswa.index');
        Route::get('/rekap-pelanggaran-siswa/data', [RekapPelanggaranSiswaController::class, 'data'])->name('rekap-pelanggaran-siswa.data');

        // Hukuman siswa
        Route::get('/hukuman-siswa', [HukumanSiswaController::class, 'index'])->name('hukuman-siswa.index');
        Route::get('/hukuman-siswa/data', [HukumanSiswaController::class, 'data'])->name('hukuman-siswa.data');
        Route::get('/hukuman-siswa/eligible/data', [HukumanSiswaController::class, 'eligibleData'])->name('hukuman-siswa.eligible.data');
        Route::get('/hukuman-siswa/recommend/{siswa}', [HukumanSiswaController::class, 'recommend'])->name('hukuman-siswa.recommend');
        Route::post('/hukuman-siswa', [HukumanSiswaController::class, 'store'])->name('hukuman-siswa.store');
        Route::get('/hukuman-siswa/{hukumanSiswa}', [HukumanSiswaController::class, 'show'])->name('hukuman-siswa.show');
        Route::put('/hukuman-siswa/{hukumanSiswa}', [HukumanSiswaController::class, 'update'])->name('hukuman-siswa.update');
        Route::delete('/hukuman-siswa/{hukumanSiswa}', [HukumanSiswaController::class, 'destroy'])->name('hukuman-siswa.destroy');

        // Prestasi Guru
        Route::get('/prestasi-guru', [PrestasiGuruController::class, 'index'])->name('prestasi-guru.index');
        Route::get('/prestasi-guru/data', [PrestasiGuruController::class, 'data'])->name('prestasi-guru.data');
        Route::post('/prestasi-guru', [PrestasiGuruController::class, 'store'])->name('prestasi-guru.store');
        Route::get('/prestasi-guru/{prestasiGuru}', [PrestasiGuruController::class, 'show'])->name('prestasi-guru.show');
        Route::put('/prestasi-guru/{prestasiGuru}', [PrestasiGuruController::class, 'update'])->name('prestasi-guru.update');
        Route::delete('/prestasi-guru/{prestasiGuru}', [PrestasiGuruController::class, 'destroy'])->name('prestasi-guru.destroy');

        // Pelanggaran Guru
        Route::get('/pelanggaran-guru', [PelanggaranGuruController::class, 'index'])->name('pelanggaran-guru.index');
        Route::get('/pelanggaran-guru/data', [PelanggaranGuruController::class, 'data'])->name('pelanggaran-guru.data');
        Route::post('/pelanggaran-guru', [PelanggaranGuruController::class, 'store'])->name('pelanggaran-guru.store');
        Route::get('/pelanggaran-guru/{pelanggaranGuru}', [PelanggaranGuruController::class, 'show'])->name('pelanggaran-guru.show');
        Route::put('/pelanggaran-guru/{pelanggaranGuru}', [PelanggaranGuruController::class, 'update'])->name('pelanggaran-guru.update');
        Route::delete('/pelanggaran-guru/{pelanggaranGuru}', [PelanggaranGuruController::class, 'destroy'])->name('pelanggaran-guru.destroy');

        // Import Prestasi
        Route::get('/impor-prestasi', [AchievementViolationPrestasiImportController::class, 'index'])->name('impor-prestasi');
        Route::get('/impor-prestasi/template', [AchievementViolationPrestasiImportController::class, 'template'])->name('impor-prestasi.template');
        Route::get('/impor-prestasi/preview', [AchievementViolationPrestasiImportController::class, 'showPreview'])->name('impor-prestasi.preview.show');
        Route::post('/impor-prestasi/preview', [AchievementViolationPrestasiImportController::class, 'storePreview'])->name('impor-prestasi.preview');
        Route::delete('/impor-prestasi/preview', [AchievementViolationPrestasiImportController::class, 'clearPreview'])->name('impor-prestasi.preview.clear');
        Route::post('/impor-prestasi/confirm', [AchievementViolationPrestasiImportController::class, 'confirmImport'])->name('impor-prestasi.confirm');

        // Import Pelanggaran
        Route::get('/impor-pelanggaran', [AchievementViolationPelanggaranImportController::class, 'index'])->name('impor-pelanggaran');
        Route::get('/impor-pelanggaran/template', [AchievementViolationPelanggaranImportController::class, 'template'])->name('impor-pelanggaran.template');
        Route::get('/impor-pelanggaran/preview', [AchievementViolationPelanggaranImportController::class, 'showPreview'])->name('impor-pelanggaran.preview.show');
        Route::post('/impor-pelanggaran/preview', [AchievementViolationPelanggaranImportController::class, 'storePreview'])->name('impor-pelanggaran.preview');
        Route::delete('/impor-pelanggaran/preview', [AchievementViolationPelanggaranImportController::class, 'clearPreview'])->name('impor-pelanggaran.preview.clear');
        Route::post('/impor-pelanggaran/confirm', [AchievementViolationPelanggaranImportController::class, 'confirmImport'])->name('impor-pelanggaran.confirm');
    });

    // Keuangan
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/dashboard', [FinanceDashboardController::class, 'index'])->name('dashboard');
        Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
        Route::get('/tagihan/data', [TagihanController::class, 'data'])->name('tagihan.data');
        Route::post('/tagihan', [TagihanController::class, 'store'])->name('tagihan.store');
        Route::put('/tagihan/{tagihan}', [TagihanController::class, 'update'])->name('tagihan.update');
        Route::post('/tagihan/generate-preview', [TagihanController::class, 'generatePreview'])->name('tagihan.generate-preview');
        Route::post('/tagihan/generate', [TagihanController::class, 'generate'])->name('tagihan.generate');
        Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'destroy'])->name('tagihan.destroy');
        Route::post('/tagihan/{tagihan}/potongan/apply', [TagihanController::class, 'applyPotongan'])->name('tagihan.potongan.apply');
        Route::get('/tagihan/{tagihan}/potongan', [TagihanController::class, 'showPotongan'])->name('tagihan.potongan.show');

        Route::get('/template-pesan-tagihan', [TemplatePesanTagihanController::class, 'index'])->name('template-pesan-tagihan.index');
        Route::get('/template-pesan-tagihan/data', [TemplatePesanTagihanController::class, 'data'])->name('template-pesan-tagihan.data');
        Route::get('/template-pesan-tagihan/options', [TemplatePesanTagihanController::class, 'options'])->name('template-pesan-tagihan.options');
        Route::post('/template-pesan-tagihan', [TemplatePesanTagihanController::class, 'store'])->name('template-pesan-tagihan.store');
        Route::put('/template-pesan-tagihan/{templatePesanTagihan}', [TemplatePesanTagihanController::class, 'update'])->name('template-pesan-tagihan.update');
        Route::delete('/template-pesan-tagihan/{templatePesanTagihan}', [TemplatePesanTagihanController::class, 'destroy'])->name('template-pesan-tagihan.destroy');

        Route::get('/kirim-tagihan-wa', [KirimTagihanWaController::class, 'index'])->name('kirim-tagihan-wa.index');
        Route::get('/kirim-tagihan-wa/data', [KirimTagihanWaController::class, 'data'])->name('kirim-tagihan-wa.data');
        Route::post('/kirim-tagihan-wa/build', [KirimTagihanWaController::class, 'build'])->name('kirim-tagihan-wa.build');

        Route::get('/katalog-potongan', [KatalogPotonganController::class, 'index'])->name('katalog-potongan.index');
        Route::get('/katalog-potongan/data', [KatalogPotonganController::class, 'data'])->name('katalog-potongan.data');
        Route::post('/katalog-potongan', [KatalogPotonganController::class, 'store'])->name('katalog-potongan.store');
        Route::put('/katalog-potongan/{jenisPotongan}', [KatalogPotonganController::class, 'update'])->name('katalog-potongan.update');
        Route::delete('/katalog-potongan/{jenisPotongan}', [KatalogPotonganController::class, 'destroy'])->name('katalog-potongan.destroy');
        Route::get('/potongan-siswa', [PotonganSiswaController::class, 'index'])->name('potongan-siswa.index');
        Route::get('/potongan-siswa/data', [PotonganSiswaController::class, 'data'])->name('potongan-siswa.data');
        Route::post('/potongan-siswa', [PotonganSiswaController::class, 'store'])->name('potongan-siswa.store');
        Route::put('/potongan-siswa/{potonganSiswa}', [PotonganSiswaController::class, 'update'])->name('potongan-siswa.update');
        Route::delete('/potongan-siswa/{potonganSiswa}', [PotonganSiswaController::class, 'destroy'])->name('potongan-siswa.destroy');
        Route::get('/potongan-pemakaian', [PotonganPemakaianController::class, 'index'])->name('potongan-pemakaian.index');
        Route::get('/potongan-pemakaian/data', [PotonganPemakaianController::class, 'data'])->name('potongan-pemakaian.data');
        Route::get('/tagihan/{tagihan}/cicilan', [TagihanCicilanController::class, 'show'])->name('tagihan.cicilan.show');
        Route::delete('/tagihan/{tagihan}/cicilan', [TagihanCicilanController::class, 'destroy'])->name('tagihan.cicilan.destroy');
        Route::get('/impor-tagihan', [FinanceImportExportController::class, 'index'])->name('impor-tagihan');
        Route::get('/impor-tagihan/template', [FinanceImportExportController::class, 'template'])->name('impor-tagihan.template');
        Route::get('/impor-tagihan/preview', [FinanceImportExportController::class, 'showPreview'])->name('impor-tagihan.preview.show');
        Route::post('/impor-tagihan/preview', [FinanceImportExportController::class, 'storePreview'])->name('impor-tagihan.preview');
        Route::delete('/impor-tagihan/preview', [FinanceImportExportController::class, 'clearPreview'])->name('impor-tagihan.preview.clear');
        Route::post('/impor-tagihan/confirm', [FinanceImportExportController::class, 'confirmImport'])->name('impor-tagihan.confirm');
        Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
        Route::get('/pembayaran/siswa/{siswa}/tagihan', [PembayaranController::class, 'studentTagihan'])->name('pembayaran.tagihan');
        Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');
        Route::post('/pembayaran/qris', [QrisPaymentController::class, 'store'])->name('pembayaran.qris.store');
        Route::get('/pembayaran/qris/{qrisPayment}', [QrisPaymentController::class, 'show'])->name('pembayaran.qris.show');
        Route::post('/pembayaran/qris/{qrisPayment}/check', [QrisPaymentController::class, 'check'])->name('pembayaran.qris.check');
        Route::get('/riwayat-pembayaran', [RiwayatPembayaranController::class, 'index'])->name('riwayat-pembayaran.index');
        Route::get('/riwayat-pembayaran/data', [RiwayatPembayaranController::class, 'data'])->name('riwayat-pembayaran.data');
        Route::get('/riwayat-pembayaran/receipt', [RiwayatPembayaranController::class, 'receipt'])->name('riwayat-pembayaran.receipt');
        Route::get('/batalkan-pembayaran', [BatalkanPembayaranController::class, 'index'])->name('batalkan-pembayaran.index');
        Route::get('/batalkan-pembayaran/data', [BatalkanPembayaranController::class, 'data'])->name('batalkan-pembayaran.data');
        Route::get('/batalkan-pembayaran/receipt', [BatalkanPembayaranController::class, 'receipt'])->name('batalkan-pembayaran.receipt');
        Route::delete('/batalkan-pembayaran/{pembayaran}', [BatalkanPembayaranController::class, 'destroy'])->name('batalkan-pembayaran.destroy');
        Route::get('/log-batalkan-pembayaran', [LogBatalkanPembayaranController::class, 'index'])->name('log-batalkan-pembayaran.index');
        Route::get('/log-batalkan-pembayaran/data', [LogBatalkanPembayaranController::class, 'data'])->name('log-batalkan-pembayaran.data');
        Route::get('/log-batalkan-pembayaran/{logPembayaranBatal}', [LogBatalkanPembayaranController::class, 'show'])->name('log-batalkan-pembayaran.show');
        Route::get('/saldo-siswa', [SaldoSiswaController::class, 'index'])->name('saldo-siswa.index');
        Route::get('/saldo-siswa/data', [SaldoSiswaController::class, 'data'])->name('saldo-siswa.data');
        Route::get('/saldo-siswa/{siswa}', [SaldoSiswaController::class, 'show'])->name('saldo-siswa.show');
        Route::get('/saldo-siswa/{siswa}/transactions', [SaldoSiswaController::class, 'transactions'])->name('saldo-siswa.transactions');
        Route::post('/saldo-siswa/adjust', [SaldoSiswaController::class, 'adjust'])->name('saldo-siswa.adjust');
        Route::get('/rekening', [RekeningController::class, 'index'])->name('rekening.index');
        Route::get('/rekening/data', [RekeningController::class, 'data'])->name('rekening.data');
        Route::post('/rekening', [RekeningController::class, 'store'])->name('rekening.store');
        Route::put('/rekening/{rekening}', [RekeningController::class, 'update'])->name('rekening.update');
        Route::delete('/rekening/{rekening}', [RekeningController::class, 'destroy'])->name('rekening.destroy');
        Route::get('/laporan-keuangan', [LaporanKeuanganController::class, 'index'])->name('laporan-keuangan');
        Route::get('/laporan-keuangan/data', [LaporanKeuanganController::class, 'data'])->name('laporan-keuangan.data');
        Route::get('/setting', fn () => redirect()->route('admin.pengaturan-modul.show', 'finance'))->name('setting');
        Route::get('/pindah-saldo', [PindahSaldoController::class, 'index'])->name('pindah-saldo');
        Route::post('/pindah-saldo', [PindahSaldoController::class, 'store'])->name('pindah-saldo.store');
        Route::get('/kas-manual', [KasManualController::class, 'index'])->name('kas-manual.index');
        Route::get('/kas-manual/data', [KasManualController::class, 'data'])->name('kas-manual.data');
        Route::get('/kas-manual/stats', [KasManualController::class, 'stats'])->name('kas-manual.stats');
        Route::get('/kas-manual/kategori', [KasManualController::class, 'kategoriList'])->name('kas-manual.kategori');
        Route::post('/kas-manual', [KasManualController::class, 'store'])->name('kas-manual.store');
        Route::put('/kas-manual/{kasManual}', [KasManualController::class, 'update'])->name('kas-manual.update');
        Route::delete('/kas-manual/{kasManual}', [KasManualController::class, 'destroy'])->name('kas-manual.destroy');
    });

    // Absensi
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/dashboard', [AttendanceDashboardController::class, 'index'])->name('dashboard');
        Route::get('/absensi-siswa', [AttendanceAbsensiSiswaController::class, 'index'])->name('absensi-siswa.index');
        Route::get('/absensi-siswa/data', [AttendanceAbsensiSiswaController::class, 'data'])->name('absensi-siswa.data');
        Route::get('/absensi-guru', [AbsensiGuruController::class, 'index'])->name('absensi-guru.index');
        Route::get('/absensi-guru/data', [AbsensiGuruController::class, 'data'])->name('absensi-guru.data');
        Route::get('/absensi-qr', [QrCheckinController::class, 'index'])->name('absensi-qr');
        Route::post('/absensi-qr', [QrCheckinController::class, 'store'])->name('absensi-qr.store');
        Route::get('/absensi-qr/data', [QrCheckinController::class, 'data'])->name('absensi-qr.data');
        Route::get('/absensi-rfid', [RfidCheckinController::class, 'index'])->name('absensi-rfid');
        Route::post('/absensi-rfid', [RfidCheckinController::class, 'store'])->name('absensi-rfid.store');
        Route::get('/absensi-rfid/data', [RfidCheckinController::class, 'data'])->name('absensi-rfid.data');
        Route::get('/absensi-rfid/recent', [RfidCheckinController::class, 'recent'])->name('absensi-rfid.recent');
        Route::get('/absensi-rfid/active-slots', [RfidCheckinController::class, 'activeSlots'])->name('absensi-rfid.active-slots');
        Route::get('/rekap-presensi', [RekapPresensiController::class, 'index'])->name('rekap-presensi');
        Route::get('/rekap-presensi/data', [RekapPresensiController::class, 'data'])->name('rekap-presensi.data');
        Route::get('/rekap-presensi/matrix', [RekapPresensiController::class, 'matrixData'])->name('rekap-presensi.matrix');
        Route::get('/rekap-presensi/daily-subject-matrix', [RekapPresensiController::class, 'dailySubjectMatrixData'])->name('rekap-presensi.daily-subject-matrix');
        Route::get('/rekap-presensi/subject-period-summary', [RekapPresensiController::class, 'subjectPeriodSummaryData'])->name('rekap-presensi.subject-period-summary');
        Route::get('/rekap-presensi-guru', [RekapPresensiGuruController::class, 'index'])->name('rekap-presensi-guru');
        Route::get('/rekap-presensi-guru/data', [RekapPresensiGuruController::class, 'data'])->name('rekap-presensi-guru.data');
        Route::get('/laporan-absensi', [LaporanAbsensiController::class, 'index'])->name('laporan-absensi');
        Route::get('/laporan-absensi/data', [LaporanAbsensiController::class, 'data'])->name('laporan-absensi.data');
        Route::get('/ekspor-absensi', [ExportAbsensiController::class, 'index'])->name('ekspor-absensi');
        Route::post('/ekspor-absensi', [ExportAbsensiController::class, 'import'])->name('ekspor-absensi.import');
        Route::get('/pelajaran', [PelajaranController::class, 'index'])->name('pelajaran.index');
        Route::get('/pelajaran/data', [PelajaranController::class, 'data'])->name('pelajaran.data');
        Route::post('/pelajaran', [PelajaranController::class, 'store'])->name('pelajaran.store');
        Route::put('/pelajaran/{pelajaran}', [PelajaranController::class, 'update'])->name('pelajaran.update');
        Route::delete('/pelajaran/{pelajaran}', [PelajaranController::class, 'destroy'])->name('pelajaran.destroy');
        Route::get('/jadwal-absen', [JadwalAbsenController::class, 'index'])->name('jadwal-absen.index');
        Route::get('/jadwal-absen/data', [JadwalAbsenController::class, 'data'])->name('jadwal-absen.data');
        Route::get('/jadwal-absen/create', [JadwalAbsenController::class, 'create'])->name('jadwal-absen.create');
        Route::post('/jadwal-absen', [JadwalAbsenController::class, 'store'])->name('jadwal-absen.store');
        Route::post('/jadwal-absen/preview-students', [JadwalAbsenController::class, 'previewStudents'])->name('jadwal-absen.preview-students');
        Route::get('/jadwal-absen/{jadwal_absen}/edit', [JadwalAbsenController::class, 'edit'])->name('jadwal-absen.edit');
        Route::put('/jadwal-absen/{jadwal_absen}', [JadwalAbsenController::class, 'update'])->name('jadwal-absen.update');
        Route::delete('/jadwal-absen/{jadwal_absen}', [JadwalAbsenController::class, 'destroy'])->name('jadwal-absen.destroy');
        Route::get('/jadwal-absensi-guru', [JadwalAbsensiGuruController::class, 'index'])->name('jadwal-absensi-guru.index');
        Route::get('/jadwal-absensi-guru/data', [JadwalAbsensiGuruController::class, 'data'])->name('jadwal-absensi-guru.data');
        Route::post('/jadwal-absensi-guru', [JadwalAbsensiGuruController::class, 'store'])->name('jadwal-absensi-guru.store');
        Route::put('/jadwal-absensi-guru/{jadwal_absensi_guru}', [JadwalAbsensiGuruController::class, 'update'])->name('jadwal-absensi-guru.update');
        Route::delete('/jadwal-absensi-guru/{jadwal_absensi_guru}', [JadwalAbsensiGuruController::class, 'destroy'])->name('jadwal-absensi-guru.destroy');
        Route::get('/jadwal-absensi-guru/{jadwal_absensi_guru}/gurus', [JadwalAbsensiGuruController::class, 'gurus'])->name('jadwal-absensi-guru.gurus');
        Route::put('/jadwal-absensi-guru/{jadwal_absensi_guru}/assign', [JadwalAbsensiGuruController::class, 'assignGurus'])->name('jadwal-absensi-guru.assign');
        Route::get('/jadwal-absensi-guru/{jadwal_absensi_guru}/absensi', [JadwalAbsensiGuruAttendanceController::class, 'show'])->name('jadwal-absensi-guru.absensi');
        Route::get('/jadwal-absensi-guru/{jadwal_absensi_guru}/absensi/references', [JadwalAbsensiGuruAttendanceController::class, 'references'])->name('jadwal-absensi-guru.absensi.references');
        Route::post('/jadwal-absensi-guru/{jadwal_absensi_guru}/absensi', [JadwalAbsensiGuruAttendanceController::class, 'store'])->name('jadwal-absensi-guru.absensi.store');
        Route::get('/hari-libur', [HariLiburController::class, 'index'])->name('hari-libur.index');
        Route::get('/hari-libur/data', [HariLiburController::class, 'data'])->name('hari-libur.data');
        Route::post('/hari-libur', [HariLiburController::class, 'store'])->name('hari-libur.store');
        Route::put('/hari-libur/{hari_libur}', [HariLiburController::class, 'update'])->name('hari-libur.update');
        Route::delete('/hari-libur/{hari_libur}', [HariLiburController::class, 'destroy'])->name('hari-libur.destroy');
        Route::get('/sesi-pengecualian', [AbsensiSesiPengecualianController::class, 'index'])->name('sesi-pengecualian.index');
        Route::get('/sesi-pengecualian/data', [AbsensiSesiPengecualianController::class, 'data'])->name('sesi-pengecualian.data');
        Route::delete('/sesi-pengecualian/{sesi_pengecualian}', [AbsensiSesiPengecualianController::class, 'destroy'])->name('sesi-pengecualian.destroy');
        Route::redirect('/setting', '/admin/absensi/hari-libur')->name('setting');
    });

    // Dompet digital
    Route::prefix('dompet-digital')->name('dompet-digital.')->group(function () {
        Route::get('/dashboard', [CashlessDashboardController::class, 'index'])->name('dashboard');
        Route::get('/saldo-cashless', [SaldoCashlessController::class, 'index'])->name('saldo-cashless.index');
        Route::get('/saldo-cashless/data', [SaldoCashlessController::class, 'data'])->name('saldo-cashless.data');
        Route::get('/saldo-cashless/{siswa}', [SaldoCashlessController::class, 'show'])->name('saldo-cashless.show');
        Route::get('/saldo-cashless/{siswa}/transactions', [SaldoCashlessController::class, 'transactions'])->name('saldo-cashless.transactions');
        Route::get('/saldo-rfid', [SaldoRfidKioskController::class, 'index'])->name('saldo-rfid.index');
        Route::post('/saldo-rfid/lookup', [SaldoRfidKioskController::class, 'lookup'])->name('saldo-rfid.lookup');
        Route::get('/saldo-rfid/recent', [SaldoRfidKioskController::class, 'recent'])->name('saldo-rfid.recent');
        Route::get('/menu-kantin', [MenuKantinController::class, 'index'])->name('menu-kantin.index');
        Route::get('/menu-kantin/data', [MenuKantinController::class, 'data'])->name('menu-kantin.data');
        Route::get('/pendapatan-kantin', [PendapatanKantinController::class, 'index'])->name('pendapatan-kantin.index');
        Route::get('/pendapatan-kantin/data', [PendapatanKantinController::class, 'data'])->name('pendapatan-kantin.data');
        Route::get('/pendapatan-kantin/penarikan', [PenarikanPendapatanKantinController::class, 'index'])->name('pendapatan-kantin.penarikan.index');
        Route::get('/pendapatan-kantin/penarikan/data', [PenarikanPendapatanKantinController::class, 'data'])->name('pendapatan-kantin.penarikan.data');
        Route::delete('/pendapatan-kantin/penarikan/{penarikan}', [PenarikanPendapatanKantinController::class, 'destroy'])->name('pendapatan-kantin.penarikan.destroy');
        Route::get('/pendapatan-kantin/{user}', [PendapatanKantinController::class, 'show'])->name('pendapatan-kantin.show');
        Route::get('/pendapatan-kantin/{user}/transactions', [PendapatanKantinController::class, 'transactions'])->name('pendapatan-kantin.transactions');
        Route::post('/pendapatan-kantin/{user}/withdraw', [PendapatanKantinController::class, 'withdraw'])->name('pendapatan-kantin.withdraw');
        Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('/transaksi/data', [TransaksiController::class, 'data'])->name('transaksi.data');
        Route::get('/transaksi/summary', [TransaksiController::class, 'summaryData'])->name('transaksi.summary');
        Route::get('/alokasi-uang-saku', [AlokasiUangSakuController::class, 'index'])->name('alokasi-uang-saku');
        Route::get('/alokasi-uang-saku/data', [AlokasiUangSakuController::class, 'data'])->name('alokasi-uang-saku.data');
        Route::post('/alokasi-uang-saku', [AlokasiUangSakuController::class, 'store'])->name('alokasi-uang-saku.store');
        Route::get('/topup-saldo', [TopupSaldoController::class, 'index'])->name('topup-saldo');
        Route::post('/topup-saldo', [TopupSaldoController::class, 'store'])->name('topup-saldo.store');
        Route::get('/topup-saldo/data', [TopupSaldoController::class, 'data'])->name('topup-saldo.data');
        Route::post('/topup-saldo/lookup-rfid', [TopupSaldoController::class, 'lookupRfid'])->name('topup-saldo.lookup-rfid');
        Route::get('/withdraw-saldo/preview', [TopupSaldoController::class, 'withdrawPreview'])->name('withdraw-saldo.preview');
        Route::get('/withdraw-saldo/face/references', [TopupSaldoController::class, 'faceReferences'])->name('withdraw-saldo.face-references');
        Route::get('/withdraw-saldo/face/photo/{siswa}', [TopupSaldoController::class, 'facePhoto'])->name('withdraw-saldo.face-photo');
        Route::post('/withdraw-saldo', [TopupSaldoController::class, 'withdraw'])->name('withdraw-saldo.store');
        Route::get('/transfer-kantin', [TransferKantinController::class, 'index'])->name('transfer-kantin');
        Route::post('/transfer-kantin', [TransferKantinController::class, 'store'])->name('transfer-kantin.store');
        Route::get('/transfer-kantin/data', [TransferKantinController::class, 'data'])->name('transfer-kantin.data');
        Route::get('/pengajuan-tambahan', [PengajuanTambahanController::class, 'index'])->name('pengajuan-tambahan');
        Route::get('/pengajuan-tambahan/data', [PengajuanTambahanController::class, 'data'])->name('pengajuan-tambahan.data');
        Route::post('/pengajuan-tambahan', [PengajuanTambahanController::class, 'store'])->name('pengajuan-tambahan.store');
        Route::put('/pengajuan-tambahan/{pengajuan}/approve', [PengajuanTambahanController::class, 'approve'])->name('pengajuan-tambahan.approve');
        Route::get('/limit-kontrol', [LimitKontrolController::class, 'index'])->name('limit-kontrol');
        Route::get('/limit-kontrol/data', [LimitKontrolController::class, 'data'])->name('limit-kontrol.data');
        Route::post('/limit-kontrol/global', [LimitKontrolController::class, 'updateGlobal'])->name('limit-kontrol.global');
        Route::put('/limit-kontrol/siswa/{siswa}', [LimitKontrolController::class, 'updateSiswa'])->name('limit-kontrol.update-siswa');
        Route::post('/limit-kontrol', [LimitKontrolController::class, 'store'])->name('limit-kontrol.store');
        Route::get('/cashless-pin/{siswa}', [CashlessPinController::class, 'status'])->name('cashless-pin.status');
        Route::put('/cashless-pin/{siswa}', [CashlessPinController::class, 'update'])->name('cashless-pin.update');
        Route::post('/cashless-pin/{siswa}/reset', [CashlessPinController::class, 'reset'])->name('cashless-pin.reset');
        Route::get('/rfid-kontrol', [RfidKontrolController::class, 'index'])->name('rfid-kontrol');
        Route::get('/rfid-kontrol/data', [RfidKontrolController::class, 'data'])->name('rfid-kontrol.data');
        Route::put('/rfid-kontrol/{siswa}/rfid', [RfidKontrolController::class, 'updateRfid'])->name('rfid-kontrol.update-rfid');
        Route::patch('/rfid-kontrol/{siswa}/block', [RfidKontrolController::class, 'updateBlock'])->name('rfid-kontrol.update-block');
        Route::get('/kartu-pos', [KartuPosController::class, 'index'])->name('kartu-pos');
        Route::get('/setting', fn () => redirect()->route('admin.pengaturan-modul.show', 'cashless'))->name('setting');
    });

    // Perpustakaan
    Route::prefix('perpustakaan')->name('perpustakaan.')->group(function () {
        Route::get('/dashboard', [LibraryDashboardController::class, 'index'])->name('dashboard');
        Route::get('/katalog-buku', [KatalogBukuController::class, 'index'])->name('katalog-buku.index');
        Route::get('/katalog-buku/data', [KatalogBukuController::class, 'data'])->name('katalog-buku.data');
        Route::post('/katalog-buku', [KatalogBukuController::class, 'store'])->name('katalog-buku.store');
        Route::put('/katalog-buku/{buku}', [KatalogBukuController::class, 'update'])->name('katalog-buku.update');
        Route::delete('/katalog-buku/{buku}', [KatalogBukuController::class, 'destroy'])->name('katalog-buku.destroy');
        Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
        Route::get('/peminjaman/create', [PeminjamanController::class, 'create'])->name('peminjaman.create');
        Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
        Route::get('/peminjaman/data', [PeminjamanController::class, 'data'])->name('peminjaman.data');
        Route::post('/peminjaman/resolve-rfid', [PeminjamanController::class, 'resolveRfid'])->name('peminjaman.resolve-rfid');
        Route::get('/peminjaman/siswa/{siswa}/ringkasan', [PeminjamanController::class, 'siswaSummary'])->name('peminjaman.siswa-summary');
        Route::get('/peminjaman/guru/{guru}/ringkasan', [PeminjamanController::class, 'guruSummary'])->name('peminjaman.guru-summary');
        Route::get('/peminjaman/tamu/ringkasan', [PeminjamanController::class, 'tamuSummary'])->name('peminjaman.tamu-summary');
        Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
        Route::post('/peminjaman/{peminjaman}/perpanjangan', [PeminjamanController::class, 'extend'])->name('peminjaman.extend');
        // Import katalog buku (Excel) — pratinjau + konfirmasi
        Route::get('/impor-buku', [LibraryImportBukuController::class, 'index'])->name('impor-buku');
        Route::get('/impor-buku/template', [LibraryImportBukuController::class, 'template'])->name('impor-buku.template');
        Route::get('/impor-buku/preview', [LibraryImportBukuController::class, 'showPreview'])->name('impor-buku.preview.show');
        Route::post('/impor-buku/preview', [LibraryImportBukuController::class, 'storePreview'])->name('impor-buku.preview');
        Route::delete('/impor-buku/preview', [LibraryImportBukuController::class, 'clearPreview'])->name('impor-buku.preview.clear');
        Route::post('/impor-buku/confirm', [LibraryImportBukuController::class, 'confirmImport'])->name('impor-buku.confirm');
        Route::get('/pengembalian-buku', [PengembalianBukuController::class, 'index'])->name('pengembalian-buku');
        Route::post('/pengembalian-buku', [PengembalianBukuController::class, 'store'])->name('pengembalian-buku.store');
        Route::get('/pengembalian-buku/preview/{peminjaman}', [PengembalianBukuController::class, 'preview'])->name('pengembalian-buku.preview');
        Route::get('/pengembalian-buku/data', [PengembalianBukuController::class, 'data'])->name('pengembalian-buku.data');
        Route::get('/setting-denda', [LibrarySettingDendaController::class, 'index'])->name('setting-denda.index');
        Route::put('/setting-denda', [LibrarySettingDendaController::class, 'update'])->name('setting-denda.update');
        Route::get('/riwayat-peminjaman', [HistoryPeminjamanController::class, 'index'])->name('riwayat-peminjaman');
        Route::get('/riwayat-peminjaman/data', [HistoryPeminjamanController::class, 'data'])->name('riwayat-peminjaman.data');
        Route::get('/denda-keterlambatan', [DendaKeterlambatanController::class, 'index'])->name('denda-keterlambatan');
        Route::get('/denda-keterlambatan/data', [DendaKeterlambatanController::class, 'data'])->name('denda-keterlambatan.data');
        Route::get('/cari-buku', [SearchBukuController::class, 'index'])->name('cari-buku');
        Route::get('/rating-ulasan', [RatingReviewController::class, 'index'])->name('rating-ulasan');
        Route::get('/rating-ulasan/data', [RatingReviewController::class, 'data'])->name('rating-ulasan.data');
        Route::get('/setting', fn () => redirect()->route('admin.perpustakaan.setting-denda.index'))->name('setting');
    });
});

Route::middleware(['auth', 'role:guru'])->prefix('portal/guru')->name('portal.guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    Route::get('/absensi-siswa', [GuruAbsensiSiswaController::class, 'index'])->name('absensi-siswa.index');
    Route::get('/absensi-siswa/referensi', [GuruAbsensiSiswaController::class, 'references'])->name('absensi-siswa.references');
    Route::get('/absensi-siswa/siswa', [GuruAbsensiSiswaController::class, 'students'])->name('absensi-siswa.students');
    Route::get('/absensi-siswa/guru-attendance', [GuruAbsensiSiswaController::class, 'guruAttendanceStatus'])->name('absensi-siswa.guru-attendance');
    Route::get('/absensi-siswa/foto/{siswa}', [GuruAbsensiSiswaController::class, 'photo'])->name('absensi-siswa.photo');
    Route::post('/absensi-siswa/store', [GuruAbsensiSiswaController::class, 'store'])->name('absensi-siswa.store');
    Route::get('/absensi-siswa/sesi-pengecualian', [GuruAbsensiSiswaController::class, 'sessionExemptionStatus'])->name('absensi-siswa.sesi-pengecualian');
    Route::post('/absensi-siswa/sesi-pengecualian', [GuruAbsensiSiswaController::class, 'sessionExemptionStore'])->name('absensi-siswa.sesi-pengecualian.store');
    Route::delete('/absensi-siswa/sesi-pengecualian', [GuruAbsensiSiswaController::class, 'sessionExemptionDestroy'])->name('absensi-siswa.sesi-pengecualian.destroy');
    Route::post('/absensi-siswa/guru-attendance/store', [GuruAbsensiSiswaController::class, 'guruAttendanceStore'])->name('absensi-siswa.guru-attendance.store');
    Route::get('/rekap-siswa', [GuruRekapSiswaController::class, 'index'])->name('rekap-siswa.index');
    Route::get('/rekap-siswa/data', [GuruRekapSiswaController::class, 'data'])->name('rekap-siswa.data');
    Route::get('/rekap-siswa/matrix', [GuruRekapSiswaController::class, 'matrixData'])->name('rekap-siswa.matrix');
    Route::get('/rekap-siswa/daily-subject-matrix', [GuruRekapSiswaController::class, 'dailySubjectMatrixData'])->name('rekap-siswa.daily-subject-matrix');
    Route::get('/rekap-perizinan', [RekapPerizinanController::class, 'index'])->name('rekap-perizinan.index');
    Route::get('/rekap-perizinan/data', [RekapPerizinanController::class, 'data'])->name('rekap-perizinan.data');
    Route::get('/absensi', [GuruAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/data', [GuruAbsensiController::class, 'data'])->name('absensi.data');
    Route::get('/profil', [GuruProfilController::class, 'index'])->name('profil');
    Route::get('/kartu-guru', [PortalGuruKartuGuruController::class, 'index'])->name('kartu-guru');
    Route::get('/siswa/lookup', [StudentLookupController::class, 'search'])->name('siswa.lookup');
    Route::get('/siswa/lookup/{siswa}', [StudentLookupController::class, 'show'])->name('siswa.lookup.show');
    Route::get('/prestasi-siswa', [GuruPrestasiSiswaController::class, 'index'])->name('prestasi-siswa.index');
    Route::get('/prestasi-siswa/data', [GuruPrestasiSiswaController::class, 'data'])->name('prestasi-siswa.data');
    Route::post('/prestasi-siswa', [GuruPrestasiSiswaController::class, 'store'])->name('prestasi-siswa.store');
    Route::get('/prestasi-siswa/{prestasiSiswa}', [GuruPrestasiSiswaController::class, 'show'])->name('prestasi-siswa.show');
    Route::put('/prestasi-siswa/{prestasiSiswa}', [GuruPrestasiSiswaController::class, 'update'])->name('prestasi-siswa.update');
    Route::delete('/prestasi-siswa/{prestasiSiswa}', [GuruPrestasiSiswaController::class, 'destroy'])->name('prestasi-siswa.destroy');
    Route::get('/pelanggaran-siswa', [GuruPelanggaranSiswaController::class, 'index'])->name('pelanggaran-siswa.index');
    Route::get('/pelanggaran-siswa/data', [GuruPelanggaranSiswaController::class, 'data'])->name('pelanggaran-siswa.data');
    Route::post('/pelanggaran-siswa', [GuruPelanggaranSiswaController::class, 'store'])->name('pelanggaran-siswa.store');
    Route::get('/pelanggaran-siswa/katalog-lookup', [GuruKatalogPelanggaranController::class, 'lookup'])->name('pelanggaran-siswa.katalog-lookup');
    Route::get('/pelanggaran-siswa/katalog-lookup/{jenisPelanggaran}', [GuruKatalogPelanggaranController::class, 'lookupShow'])->name('pelanggaran-siswa.katalog-lookup.show');
    Route::get('/pelanggaran-siswa/{pelanggaranSiswa}', [GuruPelanggaranSiswaController::class, 'show'])->name('pelanggaran-siswa.show');
    Route::put('/pelanggaran-siswa/{pelanggaranSiswa}', [GuruPelanggaranSiswaController::class, 'update'])->name('pelanggaran-siswa.update');
    Route::delete('/pelanggaran-siswa/{pelanggaranSiswa}', [GuruPelanggaranSiswaController::class, 'destroy'])->name('pelanggaran-siswa.destroy');
    Route::get('/booklet', [PortalBookletController::class, 'index'])->name('booklet.index');
    Route::get('/booklet/{booklet}', [PortalBookletController::class, 'show'])->name('booklet.show');
    Route::get('/tahfidz', [GuruTahfidzController::class, 'index'])->name('tahfidz.index');
    Route::post('/tahfidz/progress', [GuruTahfidzController::class, 'storeProgress'])->name('tahfidz.progress.store');
    Route::get('/tahfidz/rekap', [GuruTahfidzController::class, 'rekapIndex'])->name('tahfidz.rekap.index');
    Route::post('/tahfidz/rekap', [GuruTahfidzController::class, 'rekapStore'])->name('tahfidz.rekap.store');
    Route::get('/tahfidz/rekap/{rekap}', [GuruTahfidzController::class, 'rekapShow'])->name('tahfidz.rekap.show');
    Route::put('/tahfidz/rekap/{rekap}/baris/{tahfidzRekapSiswa}', [GuruTahfidzController::class, 'rekapUpdateBaris'])->name('tahfidz.rekap.baris.update');
});

Route::middleware(['auth', 'role:orang_tua'])->prefix('portal/ortu')->name('portal.ortu.')->group(function () {
    Route::get('/dashboard', [OrangTuaDashboardController::class, 'index'])->name('dashboard');
    Route::get('/anak', [OrtuAnakController::class, 'index'])->name('anak');
    Route::put('/anak/{siswa}', [OrtuAnakController::class, 'update'])->name('anak.update');
    Route::get('/tagihan', [OrtuTagihanController::class, 'index'])->name('tagihan.index');
    Route::get('/tagihan/data', [OrtuTagihanController::class, 'data'])->name('tagihan.data');
    Route::get('/tagihan/unpaid', [OrtuTagihanController::class, 'unpaid'])->name('tagihan.unpaid');
    Route::post('/tagihan/bayar', [OrtuTagihanController::class, 'pay'])->name('tagihan.bayar');
    Route::post('/tagihan/qris', [OrtuQrisPaymentController::class, 'store'])->name('tagihan.qris.store');
    Route::get('/tagihan/qris/{qrisPayment}', [OrtuQrisPaymentController::class, 'show'])->name('tagihan.qris.show');
    Route::post('/tagihan/qris/{qrisPayment}/check', [OrtuQrisPaymentController::class, 'check'])->name('tagihan.qris.check');
    Route::get('/saldo', [OrtuPembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/saldo/data', [OrtuPembayaranController::class, 'data'])->name('pembayaran.data');
    Route::get('/pindah-saldo', [OrtuPindahSaldoController::class, 'index'])->name('pindah-saldo');
    Route::get('/pindah-saldo/saldo', [OrtuPindahSaldoController::class, 'saldo'])->name('pindah-saldo.saldo');
    Route::post('/pindah-saldo', [OrtuPindahSaldoController::class, 'store'])->name('pindah-saldo.store');
    Route::get('/absensi', [OrtuAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/data', [OrtuAbsensiController::class, 'data'])->name('absensi.data');
    Route::get('/absensi/summary', [OrtuAbsensiController::class, 'summaryData'])->name('absensi.summary');
    Route::get('/rekap-presensi', [OrtuRekapPresensiController::class, 'index'])->name('rekap-presensi.index');
    Route::get('/rekap-presensi/data', [OrtuRekapPresensiController::class, 'data'])->name('rekap-presensi.data');
    Route::get('/rekap-perizinan', [OrtuRekapPerizinanController::class, 'index'])->name('rekap-perizinan.index');
    Route::get('/rekap-perizinan/data', [OrtuRekapPerizinanController::class, 'data'])->name('rekap-perizinan.data');
    Route::get('/rekap-perizinan/{perizinan}', [OrtuRekapPerizinanController::class, 'show'])->name('rekap-perizinan.show');
    Route::get('/dompet', [OrtuDompetController::class, 'index'])->name('dompet.index');
    Route::get('/dompet/data', [OrtuDompetController::class, 'data'])->name('dompet.data');
    Route::get('/dompet/transaksi/{sccttranCashless}', [OrtuDompetController::class, 'show'])->name('dompet.show');
    Route::get('/pin-cashless', [OrtuCashlessPinController::class, 'index'])->name('pin-cashless.index');
    Route::put('/pin-cashless/{siswa}', [OrtuCashlessPinController::class, 'update'])->name('pin-cashless.update');
    Route::get('/perpustakaan', [OrtuPerpustakaanController::class, 'index'])->name('perpustakaan.index');
    Route::get('/perpustakaan/data', [OrtuPerpustakaanController::class, 'data'])->name('perpustakaan.data');
    Route::get('/kartu-pelajar', [OrtuKartuPelajarController::class, 'index'])->name('kartu-pelajar');
    Route::get('/akses-token', [OrtuAksesTokenController::class, 'index'])->name('akses-token.index');
    Route::post('/akses-token/refresh', [OrtuAksesTokenController::class, 'refresh'])->name('akses-token.refresh');
    Route::get('/prestasi-siswa', [OrtuPrestasiSiswaController::class, 'index'])->name('prestasi-siswa.index');
    Route::get('/prestasi-siswa/data', [OrtuPrestasiSiswaController::class, 'data'])->name('prestasi-siswa.data');
    Route::get('/prestasi-siswa/{prestasiSiswa}', [OrtuPrestasiSiswaController::class, 'show'])->name('prestasi-siswa.show');
    Route::get('/pelanggaran-siswa', [OrtuPelanggaranSiswaController::class, 'index'])->name('pelanggaran-siswa.index');
    Route::get('/pelanggaran-siswa/data', [OrtuPelanggaranSiswaController::class, 'data'])->name('pelanggaran-siswa.data');
    Route::get('/pelanggaran-siswa/{pelanggaranSiswa}', [OrtuPelanggaranSiswaController::class, 'show'])->name('pelanggaran-siswa.show');
    Route::get('/rapor', [OrtuRaporController::class, 'index'])->name('rapor.index');
    Route::get('/booklet', [PortalBookletController::class, 'index'])->name('booklet.index');
    Route::get('/booklet/{booklet}', [PortalBookletController::class, 'show'])->name('booklet.show');
    Route::get('/tahfidz', [OrtuTahfidzController::class, 'index'])->name('tahfidz.index');
});

Route::middleware(['auth', 'role:siswa'])->prefix('portal/siswa')->name('portal.siswa.')->group(function () {
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [SiswaProfilController::class, 'index'])->name('profil');
    Route::post('/profil/photo', [SiswaProfilController::class, 'uploadPhoto'])->name('profil.photo');
    Route::get('/profil/foto-wajah', [SiswaProfilController::class, 'showFacePhoto'])->name('profil.foto-wajah');
    Route::post('/profil/rekam-wajah', [SiswaProfilController::class, 'storeFaceCapture'])->name('profil.rekam-wajah.store');
    Route::delete('/profil/rekam-wajah', [SiswaProfilController::class, 'deleteFaceCapture'])->name('profil.rekam-wajah.destroy');
    Route::get('/tagihan', [SiswaTagihanController::class, 'index'])->name('tagihan.index');
    Route::get('/tagihan/data', [SiswaTagihanController::class, 'data'])->name('tagihan.data');
    Route::get('/saldo', [SiswaPembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/saldo/data', [SiswaPembayaranController::class, 'data'])->name('pembayaran.data');
    Route::get('/absensi', [SiswaAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/data', [SiswaAbsensiController::class, 'data'])->name('absensi.data');
    Route::get('/dompet', [SiswaDompetController::class, 'index'])->name('dompet.index');
    Route::get('/dompet/data', [SiswaDompetController::class, 'data'])->name('dompet.data');
    Route::get('/dompet/transaksi/{sccttranCashless}', [SiswaDompetController::class, 'show'])->name('dompet.show');
    Route::get('/perpustakaan', [SiswaPerpustakaanController::class, 'index'])->name('perpustakaan.index');
    Route::get('/perpustakaan/data', [SiswaPerpustakaanController::class, 'data'])->name('perpustakaan.data');
    Route::get('/kartu-pelajar', [SiswaKartuPelajarController::class, 'index'])->name('kartu-pelajar');
    Route::get('/akses-token', [SiswaAksesTokenController::class, 'index'])->name('akses-token.index');
    Route::post('/akses-token/refresh', [SiswaAksesTokenController::class, 'refresh'])->name('akses-token.refresh');
    Route::get('/prestasi-siswa', [SiswaPrestasiSiswaController::class, 'index'])->name('prestasi-siswa.index');
    Route::get('/prestasi-siswa/data', [SiswaPrestasiSiswaController::class, 'data'])->name('prestasi-siswa.data');
    Route::get('/prestasi-siswa/{prestasiSiswa}', [SiswaPrestasiSiswaController::class, 'show'])->name('prestasi-siswa.show');
    Route::get('/pelanggaran-siswa', [SiswaPelanggaranSiswaController::class, 'index'])->name('pelanggaran-siswa.index');
    Route::get('/pelanggaran-siswa/data', [SiswaPelanggaranSiswaController::class, 'data'])->name('pelanggaran-siswa.data');
    Route::get('/pelanggaran-siswa/{pelanggaranSiswa}', [SiswaPelanggaranSiswaController::class, 'show'])->name('pelanggaran-siswa.show');
    Route::get('/rapor', [SiswaRaporController::class, 'index'])->name('rapor.index');
    Route::get('/ujian', [SiswaUjianController::class, 'index'])->name('ujian.index');
    Route::get('/ujian/{ujian}', [SiswaUjianController::class, 'show'])->name('ujian.show');
    Route::post('/ujian/{ujian}/start', [SiswaUjianController::class, 'start'])->name('ujian.start');
    Route::post('/ujian/{ujian}/submit', [SiswaUjianController::class, 'submit'])->name('ujian.submit');
    Route::get('/booklet', [PortalBookletController::class, 'index'])->name('booklet.index');
    Route::get('/booklet/{booklet}', [PortalBookletController::class, 'show'])->name('booklet.show');
    Route::get('/tahfidz', [SiswaTahfidzController::class, 'index'])->name('tahfidz.index');
    Route::get('/tahfidz/surah/{surah}', [SiswaTahfidzController::class, 'surah'])->name('tahfidz.surah');
    Route::get('/tahfidz/juz/{juz}', [SiswaTahfidzController::class, 'juz'])->name('tahfidz.juz');
    Route::get('/tahfidz/page/{page}', [SiswaTahfidzController::class, 'page'])->name('tahfidz.page');
    Route::post('/tahfidz/progress', [SiswaTahfidzController::class, 'storeProgress'])->name('tahfidz.progress.store');
});

Route::middleware(['auth', 'role:kantin'])->prefix('portal/kantin')->name('portal.kantin.')->group(function () {
    Route::get('/dashboard', [KantinDashboardController::class, 'index'])->name('dashboard');
    Route::get('/pos', [KantinPosController::class, 'index'])->name('pos');
    Route::post('/pos/lookup', [KantinPosController::class, 'lookup'])->name('pos.lookup');
    Route::post('/pos/charge', [KantinPosController::class, 'charge'])->name('pos.charge');
    Route::get('/pos/face/references', [KantinPosController::class, 'faceReferences'])->name('pos.face-references');
    Route::get('/pos/face/photo/{siswa}', [KantinPosController::class, 'facePhoto'])->name('pos.face-photo');
    Route::get('/menu', [KantinMenuController::class, 'index'])->name('menu.index');
    Route::get('/transaksi', [KantinTransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/data', [KantinTransaksiController::class, 'data'])->name('transaksi.data');
    Route::get('/ringkasan', [KantinRingkasanController::class, 'index'])->name('ringkasan');
    Route::get('/profil', [KantinProfilController::class, 'index'])->name('profil');
});

Route::middleware(['auth', 'role:pimpinan'])->prefix('portal/pimpinan')->name('portal.pimpinan.')->group(function () {
    Route::redirect('/', '/portal/pimpinan/dashboard');
    Route::get('/dashboard', [PimpinanDashboardController::class, 'index'])->name('dashboard');
    Route::get('/laporan-absensi-siswa', [PimpinanLaporanAbsensiController::class, 'index'])->name('laporan-absensi-siswa');
    Route::get('/laporan-absensi-siswa/data', [PimpinanLaporanAbsensiController::class, 'data'])->name('laporan-absensi-siswa.data');
    Route::get('/laporan-absensi-guru', [PimpinanAbsensiGuruController::class, 'index'])->name('laporan-absensi-guru');
    Route::get('/laporan-absensi-guru/data', [PimpinanAbsensiGuruController::class, 'data'])->name('laporan-absensi-guru.data');
    Route::get('/rekap-presensi', [PimpinanRekapPresensiController::class, 'index'])->name('rekap-presensi');
    Route::get('/rekap-presensi/data', [PimpinanRekapPresensiController::class, 'data'])->name('rekap-presensi.data');
    Route::get('/rekap-presensi-guru', [PimpinanRekapPresensiGuruController::class, 'index'])->name('rekap-presensi-guru');
    Route::get('/rekap-presensi-guru/data', [PimpinanRekapPresensiGuruController::class, 'data'])->name('rekap-presensi-guru.data');
    Route::get('/laporan-keuangan', [PimpinanLaporanKeuanganController::class, 'index'])->name('laporan-keuangan');
    Route::get('/laporan-keuangan/data', [PimpinanLaporanKeuanganController::class, 'data'])->name('laporan-keuangan.data');
    Route::get('/laporan-kantin', [LaporanKantinController::class, 'index'])->name('laporan-kantin');
    Route::get('/laporan-kantin/data', [LaporanKantinController::class, 'data'])->name('laporan-kantin.data');
    Route::get('/laporan-perpustakaan', [PimpinanLaporanPerpustakaanController::class, 'index'])->name('laporan-perpustakaan');
    Route::get('/laporan-perpustakaan/data', [PimpinanLaporanPerpustakaanController::class, 'data'])->name('laporan-perpustakaan.data');
    Route::get('/laporan-perpustakaan/photo/{pengunjung}', [PimpinanLaporanPerpustakaanController::class, 'photo'])->name('laporan-perpustakaan.photo');
    Route::get('/rekap-perizinan', [App\Http\Controllers\Portal\Pimpinan\RekapPerizinanController::class, 'index'])->name('rekap-perizinan.index');
    Route::get('/rekap-perizinan/data', [App\Http\Controllers\Portal\Pimpinan\RekapPerizinanController::class, 'data'])->name('rekap-perizinan.data');
});

Route::middleware(['auth', 'role:perpustakaan'])->prefix('portal/perpustakaan')->name('portal.perpustakaan.')->group(function () {
    Route::redirect('/', '/portal/perpustakaan/dashboard');
    Route::get('/dashboard', [PerpustakaanDashboardController::class, 'index'])->name('dashboard');
    Route::get('/katalog-buku', [PerpustakaanKatalogController::class, 'index'])->name('katalog-buku.index');
    Route::get('/katalog-buku/data', [PerpustakaanKatalogController::class, 'data'])->name('katalog-buku.data');
    Route::post('/katalog-buku', [PerpustakaanKatalogController::class, 'store'])->name('katalog-buku.store');
    Route::put('/katalog-buku/{buku}', [PerpustakaanKatalogController::class, 'update'])->name('katalog-buku.update');
    Route::delete('/katalog-buku/{buku}', [PerpustakaanKatalogController::class, 'destroy'])->name('katalog-buku.destroy');
    // Import katalog buku (Excel) — pratinjau + konfirmasi
    Route::get('/impor-buku', [PerpustakaanImportBukuController::class, 'index'])->name('impor-buku');
    Route::get('/impor-buku/template', [PerpustakaanImportBukuController::class, 'template'])->name('impor-buku.template');
    Route::get('/impor-buku/preview', [PerpustakaanImportBukuController::class, 'showPreview'])->name('impor-buku.preview.show');
    Route::post('/impor-buku/preview', [PerpustakaanImportBukuController::class, 'storePreview'])->name('impor-buku.preview');
    Route::delete('/impor-buku/preview', [PerpustakaanImportBukuController::class, 'clearPreview'])->name('impor-buku.preview.clear');
    Route::post('/impor-buku/confirm', [PerpustakaanImportBukuController::class, 'confirmImport'])->name('impor-buku.confirm');
    Route::get('/peminjaman', [PerpustakaanPeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/create', [PerpustakaanPeminjamanController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman', [PerpustakaanPeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::get('/peminjaman/data', [PerpustakaanPeminjamanController::class, 'data'])->name('peminjaman.data');
    Route::post('/peminjaman/resolve-rfid', [PerpustakaanPeminjamanController::class, 'resolveRfid'])->name('peminjaman.resolve-rfid');
    Route::get('/peminjaman/siswa/{siswa}/ringkasan', [PerpustakaanPeminjamanController::class, 'siswaSummary'])->name('peminjaman.siswa-summary');
    Route::get('/peminjaman/guru/{guru}/ringkasan', [PerpustakaanPeminjamanController::class, 'guruSummary'])->name('peminjaman.guru-summary');
    Route::get('/peminjaman/tamu/ringkasan', [PerpustakaanPeminjamanController::class, 'tamuSummary'])->name('peminjaman.tamu-summary');
    Route::get('/peminjaman/{peminjaman}', [PerpustakaanPeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::post('/peminjaman/{peminjaman}/perpanjangan', [PerpustakaanPeminjamanController::class, 'extend'])->name('peminjaman.extend');
    Route::get('/peminjaman/lookup', [PerpustakaanPeminjamanLookupController::class, 'search'])->name('peminjaman.lookup');
    Route::get('/peminjaman/lookup/{peminjaman}', [PerpustakaanPeminjamanLookupController::class, 'show'])->name('peminjaman.lookup.show');
    Route::get('/buku/lookup', [PerpustakaanBukuLookupController::class, 'search'])->name('buku.lookup');
    Route::get('/buku/lookup/{buku}', [PerpustakaanBukuLookupController::class, 'show'])->name('buku.lookup.show');
    Route::get('/pengembalian-buku', [PerpustakaanPengembalianController::class, 'index'])->name('pengembalian-buku');
    Route::post('/pengembalian-buku', [PerpustakaanPengembalianController::class, 'store'])->name('pengembalian-buku.store');
    Route::get('/pengembalian-buku/preview/{peminjaman}', [PerpustakaanPengembalianController::class, 'preview'])->name('pengembalian-buku.preview');
    Route::get('/pengembalian-buku/data', [PerpustakaanPengembalianController::class, 'data'])->name('pengembalian-buku.data');
    Route::get('/riwayat-peminjaman', [PerpustakaanHistoryController::class, 'index'])->name('riwayat-peminjaman');
    Route::get('/riwayat-peminjaman/data', [PerpustakaanHistoryController::class, 'data'])->name('riwayat-peminjaman.data');
    Route::get('/denda-keterlambatan', [PerpustakaanDendaController::class, 'index'])->name('denda-keterlambatan');
    Route::get('/denda-keterlambatan/data', [PerpustakaanDendaController::class, 'data'])->name('denda-keterlambatan.data');
    Route::get('/cari-buku', [PerpustakaanSearchController::class, 'index'])->name('cari-buku');
    Route::get('/rating-ulasan', [PerpustakaanRatingController::class, 'index'])->name('rating-ulasan');
    Route::get('/rating-ulasan/data', [PerpustakaanRatingController::class, 'data'])->name('rating-ulasan.data');
    Route::get('/setting-denda', [PerpustakaanSettingDendaController::class, 'index'])->name('setting-denda.index');
    Route::put('/setting-denda', [PerpustakaanSettingDendaController::class, 'update'])->name('setting-denda.update');
    Route::get('/rekap-pengunjung', [PerpustakaanRekapPengunjungController::class, 'index'])->name('rekap-pengunjung.index');
    Route::get('/rekap-pengunjung/data', [PerpustakaanRekapPengunjungController::class, 'data'])->name('rekap-pengunjung.data');
    Route::get('/rekap-pengunjung/guru/lookup/{guru}', [PerpustakaanRekapPengunjungController::class, 'guruLookupShow'])->name('rekap-pengunjung.guru-lookup.show');
    Route::get('/rekap-pengunjung/guru/lookup', [PerpustakaanRekapPengunjungController::class, 'guruLookup'])->name('rekap-pengunjung.guru-lookup');
    Route::post('/rekap-pengunjung/resolve-rfid', [PerpustakaanRekapPengunjungController::class, 'resolveRfid'])->name('rekap-pengunjung.resolve-rfid');
    Route::get('/rekap-pengunjung/lookup/{siswa}', [PerpustakaanRekapPengunjungController::class, 'lookupShow'])->name('rekap-pengunjung.lookup.show');
    Route::get('/rekap-pengunjung/lookup', [PerpustakaanRekapPengunjungController::class, 'lookup'])->name('rekap-pengunjung.lookup');
    Route::get('/rekap-pengunjung/referensi', [PerpustakaanRekapPengunjungController::class, 'references'])->name('rekap-pengunjung.references');
    Route::get('/rekap-pengunjung/siswa-photo/{siswa}', [PerpustakaanRekapPengunjungController::class, 'siswaPhoto'])->name('rekap-pengunjung.siswa-photo');
    Route::get('/rekap-pengunjung/photo/{pengunjung}', [PerpustakaanRekapPengunjungController::class, 'photo'])->name('rekap-pengunjung.photo');
    Route::post('/rekap-pengunjung', [PerpustakaanRekapPengunjungController::class, 'store'])->name('rekap-pengunjung.store');
});

Route::middleware(['auth', 'role:perizinan|admin|super_admin'])->prefix('portal/perizinan')->name('portal.perizinan.')->group(function () {
    Route::redirect('/', '/portal/perizinan/dashboard');
    Route::get('/dashboard', [PortalPerizinanDashboardController::class, 'index'])->name('dashboard');

    Route::prefix('keluar-masuk')->name('keluar-masuk.')->group(function () {
        Route::get('/', [PortalIzinKeluarMasukController::class, 'index'])->name('index');
        Route::get('/data', [PortalIzinKeluarMasukController::class, 'data'])->name('data');
        Route::post('/', [PortalIzinKeluarMasukController::class, 'store'])->name('store');
        Route::get('/{keluar_masuk}', [PortalIzinKeluarMasukController::class, 'show'])->name('show');
        Route::put('/{keluar_masuk}', [PortalIzinKeluarMasukController::class, 'update'])->name('update');
        Route::delete('/{keluar_masuk}', [PortalIzinKeluarMasukController::class, 'destroy'])->name('destroy');
        Route::post('/{keluar_masuk}/checkin', [PortalIzinKeluarMasukController::class, 'checkin'])->name('checkin');
        Route::get('/{keluar_masuk}/checkin/preview', [PortalIzinKeluarMasukController::class, 'checkinPreview'])->name('checkin.preview');
    });

    Route::prefix('keluar-masuk-pondok')->name('keluar-masuk-pondok.')->group(function () {
        Route::get('/', [PortalIzinKeluarMasukPondokController::class, 'index'])->name('index');
        Route::get('/data', [PortalIzinKeluarMasukPondokController::class, 'data'])->name('data');
        Route::post('/', [PortalIzinKeluarMasukPondokController::class, 'store'])->name('store');
        Route::get('/{keluar_masuk_pondok}', [PortalIzinKeluarMasukPondokController::class, 'show'])->name('show');
        Route::put('/{keluar_masuk_pondok}', [PortalIzinKeluarMasukPondokController::class, 'update'])->name('update');
        Route::delete('/{keluar_masuk_pondok}', [PortalIzinKeluarMasukPondokController::class, 'destroy'])->name('destroy');
        Route::post('/{keluar_masuk_pondok}/checkin', [PortalIzinKeluarMasukPondokController::class, 'checkin'])->name('checkin');
        Route::get('/{keluar_masuk_pondok}/checkin/preview', [PortalIzinKeluarMasukPondokController::class, 'checkinPreview'])->name('checkin.preview');
    });

    Route::prefix('pulang-libur')->name('pulang-libur.')->group(function () {
        Route::get('/', [PortalIzinPulangLiburController::class, 'index'])->name('index');
        Route::get('/data', [PortalIzinPulangLiburController::class, 'data'])->name('data');
        Route::post('/', [PortalIzinPulangLiburController::class, 'store'])->name('store');
        Route::get('/{pulang_libur}', [PortalIzinPulangLiburController::class, 'show'])->name('show');
        Route::put('/{pulang_libur}', [PortalIzinPulangLiburController::class, 'update'])->name('update');
        Route::delete('/{pulang_libur}', [PortalIzinPulangLiburController::class, 'destroy'])->name('destroy');
        Route::post('/{pulang_libur}/checkin', [PortalIzinPulangLiburController::class, 'checkin'])->name('checkin');
        Route::get('/{pulang_libur}/checkin/preview', [PortalIzinPulangLiburController::class, 'checkinPreview'])->name('checkin.preview');
    });

    Route::get('/rekap-laporan', [PortalPerizinanRekapLaporanController::class, 'index'])->name('rekap-laporan');
    Route::get('/rekap-laporan/data', [PortalPerizinanRekapLaporanController::class, 'data'])->name('rekap-laporan.data');
});
