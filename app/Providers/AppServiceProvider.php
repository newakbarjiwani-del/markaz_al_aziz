<?php

namespace App\Providers;

use App\Models\AbsensiGuru;
use App\Models\AbsensiQr;
use App\Models\AbsensiSiswa;
use App\Models\AlokasiUangSaku;
use App\Models\Buku;
use App\Models\Dompet;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsensiGuru;
use App\Models\KasManual;
use App\Models\PotonganPemakaian;
use App\Models\PotonganSiswa;
use App\Models\LogPembayaranBatal;
use App\Models\Kelas;
use App\Models\LimitCashless;
use App\Models\MenuKantin;
use App\Models\OrangTua;
use App\Models\Pelajaran;
use App\Models\Pembayaran;
use App\Models\PeminjamanBuku;
use App\Models\PengaturanCashless;
use App\Models\PengunjungPerpustakaan;
use App\Models\RekeningBank;
use App\Models\SaldoKeuangan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\SccttranCashless;
use App\Models\TransaksiCashless;
use App\Models\Scopes\OperatorSekolahRelationScope;
use App\Models\Scopes\OperatorSekolahScope;
use App\Services\MenuService;
use App\Support\PortalPwa;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerAdminSchoolScopes();
        $this->configureRateLimiting();

        View::composer(['layouts.partials.sidebar', 'layouts.partials.bottom-nav'], function ($view) {
            $user = auth()->user();
            $menuService = app(MenuService::class);

            $view->with([
                'navigationMenu' => $menuService->menuFor($user),
                'navigationHomeRoute' => $menuService->homeRouteFor($user),
                'bottomNavMenu' => $menuService->bottomNavFor($user),
            ]);
        });

        View::composer(['layouts.app', 'layouts.partials.head', 'layouts.partials.scripts'], function ($view) {
            $view->with('portalPwa', PortalPwa::enabled());
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $login = Str::lower(trim((string) $request->input('login', '')));

            return Limit::perMinute(5)->by($request->ip().'|'.$login);
        });
    }

    private function registerAdminSchoolScopes(): void
    {
        $directModels = [
            Siswa::class,
            Guru::class,
            OrangTua::class,
            Kelas::class,
            Tagihan::class,
            Pelajaran::class,
            JadwalAbsen::class,
            JadwalAbsensiGuru::class,
            AbsensiSiswa::class,
            AbsensiGuru::class,
            AbsensiQr::class,
            RekeningBank::class,
            MenuKantin::class,
            LimitCashless::class,
            PengaturanCashless::class,
            KasManual::class,
            PotonganSiswa::class,
        ];

        foreach ($directModels as $modelClass) {
            $includeGlobal = in_array($modelClass, [JadwalAbsen::class, Pelajaran::class, OrangTua::class], true);
            $modelClass::addGlobalScope(new OperatorSekolahScope(includeGlobal: $includeGlobal));
        }

        Sekolah::addGlobalScope(new OperatorSekolahScope('id'));
        HariLibur::addGlobalScope(new OperatorSekolahScope(includeGlobal: true));

        Pembayaran::addGlobalScope(new OperatorSekolahRelationScope('siswa'));
        LogPembayaranBatal::addGlobalScope(new OperatorSekolahRelationScope('siswa'));
        Dompet::addGlobalScope(new OperatorSekolahRelationScope('siswa'));
        SaldoKeuangan::addGlobalScope(new OperatorSekolahRelationScope('siswa'));
        AlokasiUangSaku::addGlobalScope(new OperatorSekolahRelationScope('siswa'));
        TransaksiCashless::addGlobalScope(new OperatorSekolahRelationScope('siswa'));
        SccttranCashless::addGlobalScope(new OperatorSekolahRelationScope('siswa'));
        PotonganPemakaian::addGlobalScope(new OperatorSekolahRelationScope('tagihan.siswa'));
    }
}
