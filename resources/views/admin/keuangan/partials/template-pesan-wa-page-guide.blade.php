@php
    $placeholders = [
        ['token' => '{sapaan}', 'detail' => 'Salam waktu (Selamat pagi/siang/sore/malam) saat pesan dibuat'],
        ['token' => '{nama_anak}', 'detail' => 'Nama lengkap siswa'],
        ['token' => '{jumlah_tagihan}', 'detail' => 'Total sisa tagihan terpilih (Rp)'],
        ['token' => '{rincian}', 'detail' => 'Daftar tagihan per baris'],
        ['token' => '{nama_instansi}', 'detail' => 'Nama instansi'],
        ['token' => '{no_va}', 'detail' => 'Nomor virtual account'],
        ['token' => '{nis}', 'detail' => 'NIS siswa'],
    ];
@endphp

<div class="card mt-4 p-4 sm:p-5">
    <div class="flex items-start gap-3">
        <x-icon name="info-circle" size="md" class="text-primary-600 dark:text-primary-400 mt-0.5 shrink-0" />
        <div class="min-w-0 flex-1 space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Cara kerja template pesan</h3>
                <p class="text-muted mt-1 text-sm">
                    Buat beberapa variasi pesan per kategori. Saat mengirim dari <strong>Kirim Tagihan WA</strong>,
                    sistem memilih template acak (atau yang Anda tentukan), lalu mengganti placeholder dengan data nyata.
                    Kategori <strong>lewat jatuh tempo</strong> dipakai bila ada tagihan terpilih yang sudah melewati tanggal jatuh tempo.
                </p>
            </div>
            <div class="wa-template-guide__table-wrap">
                <table class="wa-template-guide__table wa-template-guide__table--compact">
                    <thead>
                        <tr>
                            <th scope="col">Placeholder</th>
                            <th scope="col">Diganti menjadi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($placeholders as $row)
                            <tr>
                                <td><code>{{ $row['token'] }}</code></td>
                                <td>{{ $row['detail'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
