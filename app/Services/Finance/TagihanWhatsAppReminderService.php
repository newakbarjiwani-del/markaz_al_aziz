<?php

namespace App\Services\Finance;

use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TemplatePesanTagihan;
use App\Support\PortalGreeting;
use App\Support\TagihanPesanKategori;
use App\Support\TagihanPeriode;
use App\Support\WhatsAppLink;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TagihanWhatsAppReminderService
{
    /**
     * @param  list<int>  $tagihanIds
     * @return array{
     *     siswa_id: int,
     *     siswa_name: string,
     *     siswa_nis: string,
     *     phone: string,
     *     phone_display: string,
     *     kategori: string,
     *     kategori_label: string,
     *     template_id: int,
     *     template_nama: string,
     *     total_remaining: int,
     *     message: string,
     *     whatsapp_url: string,
     *     tagihan_count: int,
     *     has_overdue: bool
     * }
     */
    public function build(array $tagihanIds, ?int $templateId = null, bool $randomTemplate = true): array
    {
        $tagihans = $this->resolveTagihans($tagihanIds);
        $siswa = $this->assertSingleSiswa($tagihans);
        $phone = $this->resolvePhone($siswa);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Nomor WhatsApp orang tua/wali tidak tersedia untuk siswa ini.',
            ]);
        }

        $kategori = $this->resolveKategori($tagihans);
        $template = $this->pickTemplate($kategori, $templateId, $randomTemplate);

        if ($template === null) {
            throw ValidationException::withMessages([
                'template_id' => 'Belum ada template pesan aktif untuk kategori '.TagihanPesanKategori::label($kategori).'.',
            ]);
        }

        $totalRemaining = (int) round($tagihans->sum(fn (Tagihan $row) => $row->remaining()));
        $message = $this->renderMessage($template, $siswa, $tagihans, $totalRemaining);
        $whatsappUrl = WhatsAppLink::url($phone, $message);

        if ($whatsappUrl === null) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Nomor WhatsApp orang tua/wali tidak valid.',
            ]);
        }

        return [
            'siswa_id' => $siswa->id,
            'siswa_name' => $siswa->name,
            'siswa_nis' => $siswa->nis,
            'phone' => $phone,
            'phone_display' => $this->formatPhoneDisplay($phone),
            'kategori' => $kategori,
            'kategori_label' => TagihanPesanKategori::label($kategori),
            'template_id' => $template->id,
            'template_nama' => $template->nama,
            'total_remaining' => $totalRemaining,
            'message' => $message,
            'whatsapp_url' => $whatsappUrl,
            'tagihan_count' => $tagihans->count(),
            'has_overdue' => $this->hasOverdue($tagihans),
        ];
    }

    /**
     * @param  list<int>  $tagihanIds
     * @return Collection<int, Tagihan>
     */
    public function resolveTagihans(array $tagihanIds): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $tagihanIds)));
        if ($ids === []) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Pilih minimal satu tagihan.',
            ]);
        }

        $tagihans = Tagihan::query()
            ->rootBill()
            ->unpaid()
            ->hasRemaining()
            ->whereHas('siswa')
            ->with(['siswa.kelas', 'siswa.orangTua'])
            ->whereIn('id', $ids)
            ->orderBy('due_date')
            ->orderBy('periode')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        if ($tagihans->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Beberapa tagihan tidak ditemukan atau sudah tidak memiliki sisa tagihan.',
            ]);
        }

        return $tagihans;
    }

    /** @param  Collection<int, Tagihan>  $tagihans */
    public function resolveKategori(Collection $tagihans): string
    {
        return $this->hasOverdue($tagihans)
            ? TagihanPesanKategori::LEWAT_JATUH_TEMPO
            : TagihanPesanKategori::BELUM_JATUH_TEMPO;
    }

    public function pickTemplate(string $kategori, ?int $templateId, bool $randomTemplate): ?TemplatePesanTagihan
    {
        if ($templateId !== null) {
            return TemplatePesanTagihan::query()
                ->active()
                ->forKategori($kategori)
                ->whereKey($templateId)
                ->first();
        }

        $query = TemplatePesanTagihan::query()
            ->active()
            ->forKategori($kategori)
            ->ordered();

        if ($randomTemplate) {
            $templates = $query->get();
            if ($templates->isEmpty()) {
                return null;
            }

            return $templates->random();
        }

        return $query->first();
    }

    /** @param  Collection<int, Tagihan>  $tagihans */
    public function renderMessage(TemplatePesanTagihan $template, Siswa $siswa, Collection $tagihans, int $totalRemaining): string
    {
        $replacements = [
            '{sapaan}' => PortalGreeting::salutation(),
            '{nama_anak}' => $siswa->name,
            '{jumlah_tagihan}' => $this->formatRupiah($totalRemaining),
            '{rincian}' => $this->buildRincian($tagihans),
            '{nama_instansi}' => (string) config('app.nama_instansi', config('app.name')),
            '{no_va}' => (string) ($siswa->virtualAccountNumber() ?? '-'),
            '{nis}' => (string) $siswa->nis,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template->isi_pesan);
    }

    /** @param  Collection<int, Tagihan>  $tagihans */
    public function buildRincian(Collection $tagihans): string
    {
        $lines = $tagihans->map(function (Tagihan $tagihan) {
            $remaining = (int) round($tagihan->remaining());
            $periode = $tagihan->periode ? TagihanPeriode::display((int) $tagihan->periode) : '-';
            $due = $tagihan->due_date?->format('d/m/Y') ?? '-';
            $overdueMark = $tagihan->due_date && $tagihan->due_date->lt(today()) ? ' (lewat jatuh tempo)' : '';

            return '• '.$tagihan->jenis.' — '.$periode.' — sisa '.$this->formatRupiah($remaining).' — jatuh tempo '.$due.$overdueMark;
        });

        return $lines->implode("\n")."\n";
    }

    public function resolvePhone(?Siswa $siswa): ?string
    {
        if ($siswa === null) {
            return null;
        }

        $siswa->loadMissing('orangTua');

        /** @var OrangTua|null $parent */
        foreach ($siswa->orangTua as $parent) {
            $phone = $parent->primaryPhone();
            if ($phone) {
                $normalized = WhatsAppLink::normalizePhone($phone);

                return $normalized;
            }
        }

        return null;
    }

    /** @param  Collection<int, Tagihan>  $tagihans */
    private function assertSingleSiswa(Collection $tagihans): Siswa
    {
        $siswaIds = $tagihans->pluck('siswa_id')->unique()->values();
        if ($siswaIds->count() !== 1) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Tagihan yang dipilih harus milik siswa yang sama.',
            ]);
        }

        $siswa = $tagihans->first()?->siswa;
        if ($siswa === null) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Data siswa pada tagihan tidak ditemukan.',
            ]);
        }

        return $siswa;
    }

    /** @param  Collection<int, Tagihan>  $tagihans */
    private function hasOverdue(Collection $tagihans): bool
    {
        return $tagihans->contains(function (Tagihan $tagihan) {
            return $tagihan->due_date !== null
                && $tagihan->due_date->lt(today())
                && $tagihan->remaining() > 0;
        });
    }

    private function formatRupiah(int|float $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    private function formatPhoneDisplay(string $normalizedPhone): string
    {
        if (str_starts_with($normalizedPhone, '62')) {
            return '0'.substr($normalizedPhone, 2);
        }

        return $normalizedPhone;
    }
}
