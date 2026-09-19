<x-modal id="siswa-orang-tua-modal" title="Orang Tua / Wali" size="xl">
    <div class="space-y-5" data-siswa-orang-tua-root>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Siswa</p>
            <p class="mt-1 text-base font-semibold text-slate-900 dark:text-white" data-siswa-orang-tua-name>—</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" data-siswa-orang-tua-meta>—</p>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="form-section">
                <div class="form-section__title flex flex-wrap items-center justify-between gap-2">
                    <span>Daftar Orang Tua / Wali</span>
                    <span class="text-xs font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400" data-siswa-orang-tua-count>0 terhubung</span>
                </div>
                <div class="form-section__body space-y-3">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200 hidden"
                         data-siswa-orang-tua-limit-warning>
                        Setiap siswa hanya boleh punya satu data orang tua. Hapus keterkaitan yang ada jika ingin mengganti.
                    </div>
                    <div class="space-y-0 divide-y divide-slate-200 dark:divide-slate-800" data-siswa-orang-tua-list>
                        <p class="text-muted py-2 text-sm" data-siswa-orang-tua-empty>Belum ada orang tua terhubung.</p>
                    </div>
                </div>
            </section>

            @can('students.create')
                <form id="siswa-orang-tua-form"
                      data-fetch-form
                      data-reload-table
                      data-reset-on-success="true"
                      action="#"
                      method="POST"
                      class="flex min-h-0 flex-col"
                      data-siswa-orang-tua-assign>
                    @csrf
                    <section class="form-section flex-1">
                        <h4 class="form-section__title">Tambah Orang Tua</h4>
                        <div class="form-section__body">
                            <x-orang-tua-select
                                id="siswa-orang-tua-select"
                                name="orang_tua_id"
                                label="Cari orang tua / wali"
                                :status="null"
                                placeholder="Cari nama ayah/ibu/wali atau telepon (min. 3 karakter)"
                            />
                            <p class="text-muted mt-2 text-xs">
                                Siswa hanya dapat terhubung ke satu data orang tua.
                            </p>
                        </div>
                    </section>

                    <div class="modal-panel__footer mt-auto flex justify-end gap-2">
                        <button type="button" data-modal-close="siswa-orang-tua-modal" class="btn-secondary">Tutup</button>
                        <button type="submit" class="btn-primary">
                            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Keterkaitan
                        </button>
                    </div>
                </form>
                <div class="modal-panel__footer hidden justify-end gap-2 lg:col-start-2" data-siswa-orang-tua-close-only>
                    <button type="button" data-modal-close="siswa-orang-tua-modal" class="btn-secondary">Tutup</button>
                </div>
            @else
                <div class="modal-panel__footer flex justify-end lg:col-start-2">
                    <button type="button" data-modal-close="siswa-orang-tua-modal" class="btn-secondary">Tutup</button>
                </div>
            @endcan
        </div>
    </div>
</x-modal>
