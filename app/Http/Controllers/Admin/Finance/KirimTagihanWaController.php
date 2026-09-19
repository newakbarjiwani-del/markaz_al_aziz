<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\BuildTagihanWaRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\Tagihan;
use App\Models\TemplatePesanTagihan;
use App\Services\Finance\TagihanWhatsAppReminderService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\TagihanPesanKategori;
use App\Support\TagihanPeriode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KirimTagihanWaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function __construct(
        private readonly TagihanWhatsAppReminderService $reminderService,
    ) {}

    public function index(): View
    {
        $this->authorize('finance.view');

        return view('admin.keuangan.kirim-tagihan-wa', [
            'title' => 'Kirim Tagihan via WhatsApp',
            'schools' => AdminSchoolScope::schools(),
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
            'operatorSchoolId' => AdminSchoolScope::operatorSekolahId(),
            'kategoriOptions' => TagihanPesanKategori::labels(),
            'templateCount' => TemplatePesanTagihan::query()->active()->count(),
        ]);
    }

    public function data(\Illuminate\Http\Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        $query = Tagihan::query()
            ->rootBill()
            ->unpaid()
            ->hasRemaining()
            ->whereHas('siswa')
            ->with(['siswa.kelas', 'siswa.orangTua', 'tahunAkademik'])
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->jenis))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')))
            ->when($request->filled('periode'), function ($q) use ($request) {
                $periode = TagihanPeriode::normalize($request->periode);
                if ($periode !== null) {
                    $q->where('periode', $periode);
                }
            })
            ->when($request->boolean('overdue_only'), function ($q) {
                $q->whereNotNull('due_date')->whereDate('due_date', '<', today());
            });

        $this->applyKelasFilter($query, $request);
        $this->applySiswaSearchFilter($query, $request);
        $this->applyDateRange($query, $request, 'due_date');

        $siswaNisOrder = DB::raw('(select nis from siswa where siswa.id = tagihan.siswa_id and siswa.deleted_at is null limit 1)');
        $siswaNameOrder = DB::raw('(select name from siswa where siswa.id = tagihan.siswa_id and siswa.deleted_at is null limit 1)');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['jenis', 'periode', 'siswa.name', 'siswa.nis'],
            'orderable' => [
                null, $siswaNisOrder, $siswaNameOrder, null, 'jenis', 'periode',
                'amount', 'due_date',
            ],
        ], function (Tagihan $tagihan) {
            $siswa = $tagihan->siswa;
            $remaining = (int) round($tagihan->remaining());
            $phone = $this->reminderService->resolvePhone($siswa);
            $hasPhone = $phone !== null;
            $isOverdue = $tagihan->due_date && $tagihan->due_date->lt(today());

            $checkbox = $hasPhone
                ? '<span class="dt-col-check__cell"><input type="checkbox" class="tagihan-wa-checkbox form-checkbox" value="'.e((string) $tagihan->id).'"'
                    .' data-tagihan-id="'.e((string) $tagihan->id).'"'
                    .' data-siswa-id="'.e((string) $tagihan->siswa_id).'"'
                    .' data-siswa-name="'.e($siswa?->name ?? '').'"'
                    .' data-siswa-nis="'.e($siswa?->nis ?? '').'"'
                    .' data-remaining="'.e((string) $remaining).'"'
                    .' data-has-phone="1"'
                    .' aria-label="Pilih tagihan"></span>'
                : '<span class="dt-col-check__cell text-muted text-xs" title="Nomor WA tidak tersedia">—</span>';

            $phoneHint = $hasPhone
                ? '<span class="tagihan-wa-siswa__phone tagihan-wa-siswa__phone--ok">WA wali tersedia</span>'
                : '<span class="tagihan-wa-siswa__phone tagihan-wa-siswa__phone--missing">Tidak bisa dipilih · nomor WA wali tidak ada</span>';

            $nameCell = '<div class="tagihan-wa-siswa">'
                .'<span class="tagihan-wa-siswa__name">'.e($siswa?->name ?? '-').'</span>'
                .$phoneHint
                .'</div>';

            $dueCell = $tagihan->due_date
                ? ($isOverdue
                    ? $this->badgeCell($tagihan->due_date->format('d/m/Y'), 'badge badge-red')
                    : $this->dateCell($tagihan->due_date))
                : '-';

            return [
                $this->cell($checkbox, $tagihan->id, 'html'),
                $siswa?->nis ?? '-',
                $this->cell($nameCell, $siswa?->name ?? '-', 'html'),
                $siswa?->kelas?->name ?? '-',
                $tagihan->jenis,
                $tagihan->periode ? TagihanPeriode::display((int) $tagihan->periode) : '-',
                $this->cell('Rp '.number_format($remaining, 0, ',', '.'), $remaining, 'number'),
                $dueCell,
            ];
        });
    }

    public function build(BuildTagihanWaRequest $request): JsonResponse
    {
        $payload = $this->reminderService->build(
            $request->input('tagihan_ids', []),
            $request->filled('template_id') ? $request->integer('template_id') : null,
            $request->boolean('random_template', true),
        );

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pesan WhatsApp siap dikirim', $payload['siswa_name']),
            $payload
        );
    }
}
