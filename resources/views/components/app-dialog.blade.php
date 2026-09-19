<div id="app-alert-dialog" class="app-dialog fixed inset-0 z-[60] hidden" role="alertdialog" aria-modal="true" aria-labelledby="app-alert-title">
    <div class="absolute inset-0 bg-black/50" data-alert-close></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="card app-dialog__panel relative w-full max-w-md p-0 shadow-xl dark:shadow-black/40">
            <div id="app-alert-header" class="app-dialog__header app-dialog__header--warning flex items-center gap-2 rounded-t-xl px-5 py-4">
                <span id="app-alert-icon" class="app-dialog__icon" aria-hidden="true"></span>
                <h3 id="app-alert-title" class="text-lg font-semibold">Perhatian</h3>
            </div>
            <div class="app-dialog__body space-y-3 px-5 py-5">
                <p id="app-alert-message" class="text-sm text-slate-600 dark:text-slate-300"></p>
                <ul id="app-alert-list" class="app-dialog__list hidden"></ul>
                <div id="app-alert-detail" class="app-dialog__detail hidden"></div>
            </div>
            <div class="app-dialog__actions flex justify-end border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                <button type="button" id="app-alert-ok" class="btn-primary">Mengerti</button>
            </div>
        </div>
    </div>
</div>

<div id="app-confirm-dialog" class="app-dialog fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title">
    <div class="absolute inset-0 bg-black/50" data-confirm-cancel></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="card app-dialog__panel relative w-full max-w-md p-0 shadow-xl dark:shadow-black/40">
            <div id="app-confirm-header" class="app-dialog__header app-dialog__header--danger flex items-center gap-2 rounded-t-xl px-5 py-4">
                <span class="app-dialog__icon" aria-hidden="true"><i class="ti ti-alert-triangle"></i></span>
                <h3 id="app-confirm-title" class="text-lg font-semibold">Konfirmasi Hapus</h3>
            </div>
            <div class="app-dialog__body space-y-3 px-5 py-5 text-center">
                <p id="app-confirm-message" class="text-sm text-slate-600 dark:text-slate-300"></p>
                <div id="app-confirm-detail" class="app-dialog__detail hidden text-left"></div>
                <p id="app-confirm-footnote" class="text-xs text-slate-500 dark:text-slate-400">
                    <i class="ti ti-info-circle"></i> <span id="app-confirm-footnote-text">Tindakan ini tidak dapat dibatalkan.</span>
                </p>
            </div>
            <div class="app-dialog__actions flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                <button type="button" id="app-confirm-cancel" class="btn-secondary" data-confirm-cancel>Batal</button>
                <button type="button" id="app-confirm-ok" class="btn-danger">
                    <i class="ti ti-trash"></i> Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>
