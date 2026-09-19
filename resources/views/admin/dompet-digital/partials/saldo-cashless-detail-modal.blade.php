<div id="saldo-cashless-detail-modal" class="modal fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="saldo-cashless-detail-modal-title">
    <div class="absolute inset-0 bg-black/50" data-modal-close="saldo-cashless-detail-modal"></div>
    <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center sm:p-6">
        <div class="modal-panel card relative my-4 w-full max-w-5xl shadow-xl dark:shadow-black/40 sm:my-0">
            <div class="modal-panel__header">
                <h3 id="saldo-cashless-detail-modal-title" class="min-w-0 truncate text-lg font-semibold text-slate-900 dark:text-white">Detail Saldo Cashless</h3>
                <button type="button" data-modal-close="saldo-cashless-detail-modal" class="modal-panel__close" aria-label="Tutup">
                    <x-icon name="x" size="md" />
                </button>
            </div>
            <div class="modal-panel__body saldo-detail-modal__body">
                <section class="form-section">
                    <h4 class="form-section__title">Ringkasan Siswa</h4>
                    <div class="form-section__body">
                        <div id="saldo-cashless-detail-summary">
                            <p class="text-sm text-slate-500">Memuat data siswa...</p>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h4 class="form-section__title">Filter Transaksi</h4>
                    <div class="form-section__body">
                        <form id="saldo-cashless-trx-filter-form" class="filter-form" novalidate>
                            @include('admin.partials.filters.date-range', [
                                'from' => null,
                                'to' => null,
                                'fromLabel' => 'Dari',
                                'toLabel' => 'Sampai',
                                'colClass' => 'saldo-detail-modal__filter-field',
                            ])
                            <div class="saldo-detail-modal__filter-field">
                                <label class="form-label" for="cashless-detail-wallet">Dompet</label>
                                <select name="wallet" id="cashless-detail-wallet" class="form-input">
                                    <option value="">Semua</option>
                                    <option value="us">Uang Saku</option>
                                    <option value="kantin">Kantin</option>
                                    <option value="tabungan">Tabungan</option>
                                </select>
                            </div>
                            <x-filter-actions />
                        </form>
                    </div>
                </section>

                <section class="form-section" aria-labelledby="saldo-cashless-trx-title">
                    <h4 id="saldo-cashless-trx-title" class="form-section__title">Riwayat Transaksi</h4>
                    <div class="form-section__body">
                        <div class="overflow-x-auto">
                            <table id="saldo_cashless_trx_table"
                                   class="datatable-main w-full display"
                                   style="--table-min-width: 42rem"
                                   data-ajax-url="">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Metode</th>
                                        <th>Dompet</th>
                                        <th>Kredit</th>
                                        <th>Debet</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </section>

                <div class="modal-panel__footer flex justify-end gap-2">
                    <button type="button" data-modal-close="saldo-cashless-detail-modal" class="btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
