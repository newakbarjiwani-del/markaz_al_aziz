<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesFaceCapture;
use App\Http\Traits\PerpustakaanPortal;
use App\Models\Guru;
use App\Models\PengunjungPerpustakaan;
use App\Models\Scopes\OperatorSekolahScope;
use App\Models\Siswa;
use App\Services\RfidResolver;
use App\Support\ActionMessage;
use App\Support\AjaxSelect;
use App\Support\GuruSekolahFilter;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RekapPengunjungController extends Controller
{
    use DataTableTrait;
    use HandlesFaceCapture;
    use PerpustakaanPortal;

    public function __construct(
        private readonly RfidResolver $rfidResolver,
    ) {}

    public function index(): View
    {
        $todayCount = PengunjungPerpustakaan::query()
            ->when($this->librarySekolahId(), fn ($q, int $sekolahId) => $q->where('sekolah_id', $sekolahId))
            ->whereDate('visited_at', now()->toDateString())
            ->count();

        return view('portal.perpustakaan.rekap-pengunjung', [
            'title' => 'Rekap Pengunjung',
            'todayCount' => $todayCount,
            'ajaxUrl' => route('portal.perpustakaan.rekap-pengunjung.data'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PengunjungPerpustakaan::query()
            ->select([
                'id', 'sekolah_id', 'visitor_type', 'siswa_id', 'guru_id', 'nama', 'nis',
                'asal', 'telepon', 'method', 'rfid_uid', 'visited_at', 'catatan',
                'recorded_by', 'has_foto_wajah', 'created_at', 'updated_at',
            ])
            ->with(['siswa:id,kelas_id,name', 'siswa.kelas:id,name', 'guru:id,name'])
            ->when($this->librarySekolahId(), fn ($q, int $sekolahId) => $q->where('sekolah_id', $sekolahId))
            ->when($request->filled('visitor_type'), fn ($q) => $q->where('visitor_type', $request->string('visitor_type')))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('visited_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('visited_at', '<=', $request->string('date_to')))
            ->when(
                ! $request->filled('date_from') && ! $request->filled('date_to'),
                fn ($q) => $q->whereDate('visited_at', now()->toDateString())
            )
            ->orderByDesc('visited_at');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nama', 'nis', 'asal', 'telepon', 'visitor_type', 'method', 'rfid_uid'],
            'orderable' => ['id', 'visited_at', 'visited_at', 'visitor_type', 'nama', 'nis', 'asal', 'telepon', 'method'],
        ], function (PengunjungPerpustakaan $row) {
            return [
                $row->id,
                $row->visited_at,
                $row->visited_at->format('H:i'),
                $row->visitorTypeLabel(),
                $row->nama,
                $row->identitasLabel(),
                $row->asalLabel(),
                $row->telepon ?? '-',
                $row->methodLabel(),
                $row->hasFotoWajah()
                    ? $this->cell(
                        '<button type="button" class="visitor-photo-btn" data-visit-id="'.$row->id.'" title="Lihat foto" aria-label="Lihat foto kunjungan"><i class="ti ti-photo"></i></button>',
                        null,
                        'action'
                    )
                    : $this->cell('<span class="text-slate-400">—</span>', '-', 'text'),
            ];
        });
    }

    public function lookup(Request $request): JsonResponse
    {
        return $this->lookupSiswa($request);
    }

    public function lookupShow(int $siswa): JsonResponse
    {
        $siswa = Siswa::query()->withoutGlobalScope(OperatorSekolahScope::class)->findOrFail($siswa);

        abort_if($this->siswaOutsideLibraryScope($siswa), 404);

        $siswa->load('kelas:id,name');

        return response()->json($this->mapSiswaLookup($siswa));
    }

    public function guruLookup(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $gurus = Guru::query()
            ->withoutGlobalScope(OperatorSekolahScope::class)
            ->where('status', 'aktif')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'nip', 'name', 'jabatan', 'status']);

        $legacy = $gurus->map(fn (Guru $guru) => $this->mapGuruRow($guru))->values()->all();
        $results = collect($legacy)->map(fn (array $item) => [
            'id' => $item['id'],
            'text' => $item['label'],
        ])->all();

        return AjaxSelect::respond($results, $legacy);
    }

    public function guruLookupShow(int $guru): JsonResponse
    {
        $guru = Guru::query()->withoutGlobalScope(OperatorSekolahScope::class)->findOrFail($guru);

        abort_if($this->guruOutsideLibraryScope($guru), 404);

        return response()->json($this->mapGuruLookup($guru));
    }

    public function references(): JsonResponse
    {
        $students = Siswa::query()
            ->with('kelas:id,name')
            ->when($this->librarySekolahId(), fn ($q, int $sekolahId) => $q->where('sekolah_id', $sekolahId))
            ->where('status', Siswa::STATUS_ACTIVE)
            ->where('has_foto_wajah', 1)
            ->orderBy('name')
            ->get(['id', 'nis', 'name', 'kelas_id', 'updated_at']);

        return $this->jsonSuccess('OK', [
            'students' => $students->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name ?? '-',
                'photo_url' => route('portal.perpustakaan.rekap-pengunjung.siswa-photo', $siswa).'?v='.($siswa->updated_at?->timestamp ?? $siswa->id),
            ])->values(),
        ]);
    }

    public function resolveRfid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:64'],
        ]);

        $resolved = $this->resolveRfidUid(trim($validated['rfid_uid']));

        if (! $resolved) {
            return $this->jsonError('Kartu RFID tidak dikenali.', null, 404);
        }

        return $this->jsonSuccess('OK', ['visitor' => $resolved]);
    }

    public function siswaPhoto(Siswa $siswa): Response
    {
        abort_if($this->siswaOutsideLibraryScope($siswa), 404);
        abort_if(! $siswa->hasFotoWajah(), 404);

        return $this->respondFacePhoto($siswa);
    }

    public function photo(PengunjungPerpustakaan $pengunjung): Response
    {
        abort_if($this->pengunjungOutsideLibraryScope($pengunjung), 404);
        abort_if(! $pengunjung->hasFotoWajah(), 404);

        $dataUrl = $pengunjung->fotoWajahDataUrl();
        $commaPos = strpos($dataUrl, ',');
        if ($commaPos === false) {
            abort(404);
        }

        $binary = base64_decode(substr($dataUrl, $commaPos + 1), true);
        if ($binary === false) {
            abort(404);
        }

        $mime = 'image/jpeg';
        if (preg_match('/^data:(image\/[a-z0-9.+-]+);base64,/i', $dataUrl, $matches)) {
            $mime = strtolower($matches[1]);
        }

        return response($binary, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_type' => ['required', Rule::in(PengunjungPerpustakaan::visitorTypes())],
            'method' => ['required', Rule::in(PengunjungPerpustakaan::methods())],
            'siswa_id' => ['nullable', 'integer', SoftDeleteRules::exists('siswa')],
            'guru_id' => ['nullable', 'integer', SoftDeleteRules::exists('guru')],
            'nama' => ['nullable', 'string', 'max:255'],
            'asal' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'foto_wajah' => ['nullable', 'string', 'max:1400000'],
            'rfid_uid' => ['nullable', 'string', 'max:64'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $operatorSekolahId = $this->librarySekolahId();

        if ($validated['method'] === PengunjungPerpustakaan::METHOD_RFID) {
            $uid = trim((string) ($validated['rfid_uid'] ?? ''));
            if ($uid === '') {
                return $this->jsonError('UID kartu RFID wajib diisi.', ['rfid_uid' => ['RFID wajib diisi.']]);
            }

            $resolved = $this->resolveRfidUid($uid);
            if (! $resolved) {
                return $this->jsonError('Kartu RFID tidak dikenali.', null, 404);
            }

            $payload = array_merge($resolved, [
                'method' => PengunjungPerpustakaan::METHOD_RFID,
                'rfid_uid' => $uid,
                'foto_wajah' => null,
                'catatan' => $validated['catatan'] ?? null,
            ]);
        } else {
            $payload = $this->buildVisitorPayload($operatorSekolahId, $validated);
            if ($payload instanceof JsonResponse) {
                return $payload;
            }
        }

        $recordSekolahId = $operatorSekolahId ?? $this->resolveRecordSekolahId($payload);
        if ($recordSekolahId === null) {
            return $this->jsonError(
                'Akun perpustakaan tanpa sekolah hanya dapat mencatat kunjungan siswa, guru, atau RFID yang terdaftar.',
                null,
                422
            );
        }

        $fotoWajah = $payload['foto_wajah'] ?? null;
        unset($payload['foto_wajah']);

        $visit = DB::transaction(function () use ($payload, $fotoWajah, $recordSekolahId) {
            $visit = PengunjungPerpustakaan::create(array_merge($payload, [
                'sekolah_id' => $recordSekolahId,
                'visited_at' => now(),
                'recorded_by' => auth()->id(),
                'has_foto_wajah' => filled($fotoWajah) ? 1 : 0,
            ]));

            if (filled($fotoWajah)) {
                $visit->wajah()->create(['foto_wajah' => $fotoWajah]);
            }

            return $visit;
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Kunjungan berhasil dicatat', $visit->nama),
            ['visit' => $this->visitPayload($visit)],
            201
        );
    }

    private function lookupSiswa(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $students = Siswa::query()
            ->withoutGlobalScope(OperatorSekolahScope::class)
            ->with('kelas:id,name')
            ->where('status', Siswa::STATUS_ACTIVE)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'nis', 'name', 'kelas_id', 'status']);

        $legacy = $students->map(fn (Siswa $siswa) => $this->mapSiswaRow($siswa))->values()->all();
        $results = collect($legacy)->map(fn (array $item) => [
            'id' => $item['id'],
            'text' => $item['label'],
        ])->all();

        return AjaxSelect::respond($results, $legacy);
    }

    /** @param array<string, mixed> $validated */
    private function buildVisitorPayload(?int $sekolahId, array $validated): array|JsonResponse
    {
        if (! empty($validated['foto_wajah'])) {
            $fotoError = $this->validateFotoWajah($validated['foto_wajah']);
            if ($fotoError) {
                return $this->jsonError($fotoError, ['foto_wajah' => [$fotoError]]);
            }
        }

        return match ($validated['visitor_type']) {
            PengunjungPerpustakaan::TYPE_SISWA => $this->payloadForSiswa($sekolahId, $validated),
            PengunjungPerpustakaan::TYPE_GURU => $this->payloadForGuru($sekolahId, $validated),
            PengunjungPerpustakaan::TYPE_KARYAWAN => $this->payloadForFreeformVisitor(
                PengunjungPerpustakaan::TYPE_KARYAWAN,
                $validated,
                'Nama karyawan wajib diisi.'
            ),
            PengunjungPerpustakaan::TYPE_NON_SISWA => $this->payloadForFreeformVisitor(
                PengunjungPerpustakaan::TYPE_NON_SISWA,
                $validated,
                'Nama pengunjung wajib diisi.'
            ),
            default => $this->jsonError('Tipe pengunjung tidak valid.'),
        };
    }

    /** @param array<string, mixed> $validated */
    private function payloadForSiswa(?int $sekolahId, array $validated): array|JsonResponse
    {
        if (empty($validated['siswa_id'])) {
            return $this->jsonError('Pilih siswa terlebih dahulu.', ['siswa_id' => ['Siswa wajib dipilih.']]);
        }

        $siswa = Siswa::query()
            ->with('kelas:id,name')
            ->when($sekolahId, fn ($q, int $id) => $q->where('sekolah_id', $id))
            ->where('status', Siswa::STATUS_ACTIVE)
            ->find($validated['siswa_id']);

        if (! $siswa) {
            return $this->jsonError('Siswa tidak ditemukan di sekolah ini.', null, 404);
        }

        if ($validated['method'] === PengunjungPerpustakaan::METHOD_FACE && ! $siswa->hasFotoWajah()) {
            return $this->jsonError('Siswa belum memiliki foto wajah referensi.');
        }

        return [
            'visitor_type' => PengunjungPerpustakaan::TYPE_SISWA,
            'method' => $validated['method'],
            'siswa_id' => $siswa->id,
            'guru_id' => null,
            'nama' => $siswa->name,
            'nis' => $siswa->nis,
            'asal' => $siswa->kelas?->name,
            'telepon' => null,
            'foto_wajah' => $validated['foto_wajah'] ?? null,
            'rfid_uid' => null,
            'catatan' => $validated['catatan'] ?? null,
        ];
    }

    /** @param array<string, mixed> $validated */
    private function payloadForGuru(?int $sekolahId, array $validated): array|JsonResponse
    {
        if (empty($validated['guru_id'])) {
            return $this->jsonError('Pilih guru terlebih dahulu.', ['guru_id' => ['Guru wajib dipilih.']]);
        }

        $guru = Guru::query()
            ->withoutGlobalScope(OperatorSekolahScope::class)
            ->when($sekolahId, fn ($q, int $id) => GuruSekolahFilter::applyAccessibleScope($q, $id))
            ->where('status', 'aktif')
            ->find($validated['guru_id']);

        if (! $guru) {
            return $this->jsonError('Guru tidak ditemukan di sekolah ini.', null, 404);
        }

        return [
            'visitor_type' => PengunjungPerpustakaan::TYPE_GURU,
            'method' => $validated['method'],
            'siswa_id' => null,
            'guru_id' => $guru->id,
            'nama' => $guru->name,
            'nis' => $guru->nip,
            'asal' => $guru->jabatan,
            'telepon' => $guru->phone,
            'foto_wajah' => $validated['foto_wajah'] ?? null,
            'rfid_uid' => null,
            'catatan' => $validated['catatan'] ?? null,
        ];
    }

    /** @param array<string, mixed> $validated */
    private function payloadForFreeformVisitor(string $type, array $validated, string $namaError): array|JsonResponse
    {
        $nama = trim((string) ($validated['nama'] ?? ''));
        if ($nama === '') {
            return $this->jsonError($namaError, ['nama' => ['Nama wajib diisi.']]);
        }

        return [
            'visitor_type' => $type,
            'method' => $validated['method'],
            'siswa_id' => null,
            'guru_id' => null,
            'nama' => $nama,
            'nis' => null,
            'asal' => $validated['asal'] ?? null,
            'telepon' => $validated['telepon'] ?? null,
            'foto_wajah' => $validated['foto_wajah'] ?? null,
            'rfid_uid' => null,
            'catatan' => $validated['catatan'] ?? null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function resolveRfidUid(string $uid): ?array
    {
        $resolved = $this->rfidResolver->resolveHolder($uid);
        if (! $resolved) {
            return null;
        }

        if ($resolved['type'] === 'siswa') {
            /** @var Siswa $siswa */
            $siswa = $resolved['model'];
            if (! $siswa->isActive()) {
                return null;
            }
            $siswa->loadMissing('kelas:id,name');

            return [
                'visitor_type' => PengunjungPerpustakaan::TYPE_SISWA,
                'siswa_id' => $siswa->id,
                'guru_id' => null,
                'nama' => $siswa->name,
                'nis' => $siswa->nis,
                'asal' => $siswa->kelas?->name,
                'telepon' => null,
                'catatan' => null,
            ];
        }

        if ($resolved['type'] === 'guru') {
            /** @var Guru $guru */
            $guru = $resolved['model'];
            if ($guru->status !== 'aktif') {
                return null;
            }

            return [
                'visitor_type' => PengunjungPerpustakaan::TYPE_GURU,
                'siswa_id' => null,
                'guru_id' => $guru->id,
                'nama' => $guru->name,
                'nis' => $guru->nip,
                'asal' => $guru->jabatan,
                'telepon' => $guru->phone,
                'catatan' => null,
            ];
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function mapSiswaRow(Siswa $siswa): array
    {
        return [
            'id' => $siswa->id,
            'nis' => $siswa->nis,
            'name' => $siswa->name,
            'kelas' => $siswa->kelas?->name,
            'label' => $siswa->name.' · NIS '.$siswa->nis.($siswa->kelas ? ' · '.$siswa->kelas->name : ''),
        ];
    }

    /** @return array<string, mixed> */
    private function mapSiswaLookup(Siswa $siswa): array
    {
        $mapped = $this->mapSiswaRow($siswa);

        return [
            'id' => $mapped['id'],
            'text' => $mapped['label'],
            'data' => $mapped,
        ];
    }

    /** @return array<string, mixed> */
    private function mapGuruRow(Guru $guru): array
    {
        return [
            'id' => $guru->id,
            'nip' => $guru->nip,
            'name' => $guru->name,
            'jabatan' => $guru->jabatan,
            'label' => $guru->name.' · NIP '.$guru->nip.($guru->jabatan ? ' · '.$guru->jabatan : ''),
        ];
    }

    /** @return array<string, mixed> */
    private function mapGuruLookup(Guru $guru): array
    {
        $mapped = $this->mapGuruRow($guru);

        return [
            'id' => $mapped['id'],
            'text' => $mapped['label'],
            'data' => $mapped,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function resolveRecordSekolahId(array $payload): ?int
    {
        if (! empty($payload['siswa_id'])) {
            return Siswa::query()->whereKey($payload['siswa_id'])->value('sekolah_id');
        }

        if (! empty($payload['guru_id'])) {
            return Guru::query()->whereKey($payload['guru_id'])->value('sekolah_id');
        }

        return null;
    }

    private function validateFotoWajah(?string $fotoWajah, bool $required = false): ?string
    {
        if (! filled($fotoWajah)) {
            return $required ? 'Foto wajah wajib direkam.' : null;
        }

        $fotoWajah = trim($fotoWajah);
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $fotoWajah)) {
            return 'Format foto wajah tidak valid.';
        }

        $base64 = substr($fotoWajah, strpos($fotoWajah, ',') + 1);
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            return 'Data foto wajah tidak valid.';
        }

        if (strlen($decoded) > (750 * 1024)) {
            return 'Ukuran foto terlalu besar. Maksimal sekitar 750 KB.';
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function visitPayload(PengunjungPerpustakaan $visit): array
    {
        $visit->loadMissing(['siswa.kelas', 'guru']);

        return [
            'id' => $visit->id,
            'nama' => $visit->nama,
            'identitas' => $visit->identitasLabel(),
            'visitor_type' => $visit->visitorTypeLabel(),
            'method' => $visit->methodLabel(),
            'visited_at' => $visit->visited_at,
            'asal' => $visit->asalLabel(),
        ];
    }
}
