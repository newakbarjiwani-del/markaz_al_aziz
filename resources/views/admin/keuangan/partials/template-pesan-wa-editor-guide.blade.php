@php
    $placeholders = [
        ['token' => '{sapaan}', 'label' => 'Sapaan waktu', 'detail' => 'Salam sesuai jam kirim: Selamat pagi / siang / sore / malam'],
        ['token' => '{nama_anak}', 'label' => 'Nama siswa', 'detail' => 'Nama lengkap siswa yang tagihannya dipilih'],
        ['token' => '{jumlah_tagihan}', 'label' => 'Total sisa', 'detail' => 'Jumlah sisa tagihan terpilih (format Rp)'],
        ['token' => '{rincian}', 'label' => 'Rincian tagihan', 'detail' => 'Daftar per baris: jenis, periode, sisa, jatuh tempo'],
        ['token' => '{nama_instansi}', 'label' => 'Nama instansi', 'detail' => 'Nama sekolah/pondok dari pengaturan aplikasi'],
        ['token' => '{no_va}', 'label' => 'No. VA', 'detail' => 'Virtual account 16 digit untuk pembayaran'],
        ['token' => '{nis}', 'label' => 'NIS', 'detail' => 'Nomor Induk Siswa'],
    ];

    $waFormats = [
        ['syntax' => '*teks*', 'label' => 'Tebal', 'shortcut' => 'Ctrl+B'],
        ['syntax' => '_teks_', 'label' => 'Miring', 'shortcut' => 'Ctrl+I'],
        ['syntax' => '~teks~', 'label' => 'Coret', 'shortcut' => 'Alt+S'],
        ['syntax' => '```teks```', 'label' => 'Monospace', 'shortcut' => 'Ctrl+M'],
    ];
@endphp

<div class="wa-template-guide" id="template-pesan-wa-guide">
    <p class="wa-template-guide__lead">
        Tulis pesan pengingat di editor. Saat admin mengirim dari halaman <strong>Kirim Tagihan WA</strong>,
        setiap <em>placeholder</em> di bawah diganti otomatis dengan data siswa dan tagihan yang dipilih.
        Variasi template membantu mengurangi risiko pesan dianggap spam.
    </p>

    <div class="wa-template-guide__grid">
        <section class="wa-template-guide__panel" aria-labelledby="wa-guide-placeholder-title">
            <h5 class="wa-template-guide__title" id="wa-guide-placeholder-title">
                <x-icon name="code" size="sm" class="shrink-0" />
                Placeholder dinamis
            </h5>
            <p class="wa-template-guide__caption">Klik tombol untuk sisipkan ke posisi kursor di editor:</p>
            <div class="wa-template-guide__chips">
                @foreach($placeholders as $row)
                    <button type="button"
                            class="wa-template-guide__chip"
                            data-wa-insert="{{ $row['token'] }}"
                            title="{{ $row['detail'] }}">
                        <code>{{ $row['token'] }}</code>
                    </button>
                @endforeach
            </div>
            <div class="wa-template-guide__table-wrap">
                <table class="wa-template-guide__table">
                    <thead>
                        <tr>
                            <th scope="col">Kode</th>
                            <th scope="col">Arti</th>
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
        </section>

        <section class="wa-template-guide__panel" aria-labelledby="wa-guide-format-title">
            <h5 class="wa-template-guide__title" id="wa-guide-format-title">
                <x-icon name="brand-whatsapp" size="sm" class="shrink-0" />
                Format WhatsApp
            </h5>
            <p class="wa-template-guide__caption">
                Pilih teks di editor — toolbar muncul — atau gunakan pintasan keyboard:
            </p>
            <div class="wa-template-guide__table-wrap">
                <table class="wa-template-guide__table">
                    <thead>
                        <tr>
                            <th scope="col">Tulis</th>
                            <th scope="col">Hasil di WA</th>
                            <th scope="col">Pintasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($waFormats as $row)
                            <tr>
                                <td><code>{{ $row['syntax'] }}</code></td>
                                <td>{{ $row['label'] }}</td>
                                <td><kbd>{{ $row['shortcut'] }}</kbd></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="wa-template-guide__footnote">
                Baris baru = paragraf baru di WhatsApp. Footer otomatis (<code>*_*pesan otomatis …_*</code>) boleh ditambahkan manual di template.
            </p>
        </section>
    </div>
</div>
