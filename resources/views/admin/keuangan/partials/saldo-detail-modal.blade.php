<div id="saldo-detail-modal" class="modal saldo-detail-modal fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="saldo-detail-modal-title">
    <div class="absolute inset-0 bg-black/50" data-modal-close="saldo-detail-modal"></div>
    <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center sm:p-6">
        <div class="modal-panel card relative my-4 w-full max-w-5xl shadow-xl dark:shadow-black/40 sm:my-0">
            <div class="modal-panel__header">
                <h3 id="saldo-detail-modal-title" class="min-w-0 truncate text-lg font-semibold text-slate-900 dark:text-white">Detail Saldo Siswa</h3>
                <button type="button" data-modal-close="saldo-detail-modal" class="modal-panel__close" aria-label="Tutup">
                    <x-icon name="x" size="md" />
                </button>
            </div>
            <div class="modal-panel__body saldo-detail-modal__body">
                <div id="saldo-detail-summary" class="saldo-detail-modal__summary">
                    <p class="text-sm text-slate-500">Memuat data siswa...</p>
                </div>

                <form id="saldo-trx-filter-form" class="saldo-detail-modal__filters filter-form" novalidate>
                    @include('admin.partials.filters.date-range', [
                        'from' => null,
                        'to' => null,
                        'fromId' => 'saldo-detail-date-from',
                        'toId' => 'saldo-detail-date-to',
                        'fromLabel' => 'Dari',
                        'toLabel' => 'Sampai',
                        'colClass' => 'saldo-detail-modal__filter-field',
                    ])
                    <div class="saldo-detail-modal__filter-field">
                        <label class="form-label" for="saldo-detail-metode">Metode</label>
                        <select name="metode" id="saldo-detail-metode" class="form-input">
                            <option value="">Semua</option>
                            @foreach($metodeOptions as $metode)
                                <option value="{{ $metode }}">{{ $metode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-filter-actions />
                </form>

                <section class="saldo-detail-modal__transactions" aria-labelledby="saldo-detail-trx-title">
                    <h4 id="saldo-detail-trx-title" class="saldo-detail-modal__section-title">Riwayat Transaksi</h4>
                    <div class="saldo-detail-modal__table-wrap">
                        <table id="saldo_trx_table"
                               class="datatable-main w-full display"
                               style="--table-min-width: 42rem"
                               data-ajax-url="">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th>Kredit</th>
                                    <th>Debet</th>
                                    <th>Referensi</th>
                                    <th>Channel</th>
                                    <th>Bank</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
