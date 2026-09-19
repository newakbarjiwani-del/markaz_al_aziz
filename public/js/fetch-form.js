(function () {
    function parseConfirmDetail(raw) {
        if (raw == null || raw === '') {
            return '';
        }

        if (Array.isArray(raw)) {
            return raw;
        }

        var text = String(raw).trim();
        if (!text) {
            return '';
        }

        // Prefer dialog's normalizer when available (handles JSON + Js::from leftovers)
        if (typeof window.normalizeConfirmDetail === 'function') {
            var normalized = window.normalizeConfirmDetail(text);
            if (Array.isArray(normalized)) {
                return normalized;
            }
            if (typeof normalized === 'string') {
                return normalized;
            }
        }

        if (text.charAt(0) === '[') {
            try {
                var parsed = JSON.parse(text);
                if (Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (e) { /* fall through */ }

            if (text.indexOf('&quot;') !== -1 || text.indexOf('&#039;') !== -1 || text.indexOf('&amp;') !== -1) {
                var textarea = document.createElement('textarea');
                textarea.innerHTML = text;
                try {
                    var decoded = JSON.parse(textarea.value.trim());
                    if (Array.isArray(decoded)) {
                        return decoded;
                    }
                } catch (e2) { /* fall through */ }
            }
        }

        return text;
    }

    function confirmOptionsFromElement(el, defaults) {
        var opts = Object.assign({}, defaults || {});
        if (el.dataset.confirmTitle) opts.title = el.dataset.confirmTitle;
        if (el.dataset.confirmMessage) opts.message = el.dataset.confirmMessage;
        if (el.dataset.confirmDetail) opts.detail = parseConfirmDetail(el.dataset.confirmDetail);
        if (el.dataset.confirmText) opts.confirmText = el.dataset.confirmText;
        if (el.dataset.confirmTone) opts.tone = el.dataset.confirmTone;
        if (el.dataset.confirmIcon) opts.confirmIcon = el.dataset.confirmIcon;
        if (el.dataset.confirmHeaderIcon) opts.headerIcon = el.dataset.confirmHeaderIcon;
        if (el.hasAttribute('data-confirm-no-footnote')) {
            opts.footnote = false;
        } else if (Object.prototype.hasOwnProperty.call(el.dataset, 'confirmFootnote')) {
            opts.footnote = el.dataset.confirmFootnote;
        }
        return opts;
    }

    function shouldReloadDatatable(form) {
        if (form.dataset.reloadTable === 'false') {
            return false;
        }

        if (form.hasAttribute('data-reload-table')) {
            return true;
        }

        return Boolean(document.getElementById('main_table') && window.mainTable);
    }

    function resolveFormMethod(form) {
        const raw = form.dataset.method || form.getAttribute('method') || 'POST';

        return String(raw).toUpperCase();
    }

    window.initFetchForms = function () {
        document.addEventListener('submit', async function (e) {
            const form = e.target.closest('[data-fetch-form]');
            if (!form) return;
            e.preventDefault();

            if (!(await window.validateFormWithDialog?.(form))) {
                return;
            }

            if (form.hasAttribute('data-confirm-submit')) {
                var confirmOptions = confirmOptionsFromElement(form, {
                    title: 'Konfirmasi',
                    message: 'Yakin melanjutkan?',
                    confirmText: 'Ya, Lanjutkan',
                    tone: 'primary',
                });

                var skipConfirm = false;
                var builderName = form.dataset.confirmBuilder;
                if (builderName && typeof window[builderName] === 'function') {
                    var built = window[builderName](form);
                    if (built === false) {
                        return;
                    }
                    if (built && typeof built === 'object') {
                        if (built.skipConfirm) {
                            skipConfirm = true;
                        } else {
                            Object.assign(confirmOptions, built);
                        }
                    }
                }

                if (!skipConfirm) {
                    var confirmed = await window.showConfirm?.(confirmOptions);
                    if (!confirmed) {
                        return;
                    }
                }
            }

            const submitBtn = form.querySelector('[type="submit"]');
            const originalText = submitBtn?.innerHTML;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Menyimpan...';
            }

            form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
            form.querySelectorAll('[data-field-warning="1"]').forEach(function (el) { el.remove(); });
            form.querySelectorAll('.border-red-500').forEach(function (el) { el.classList.remove('border-red-500'); });
            form.querySelectorAll('.form-input--warning').forEach(function (el) { el.classList.remove('form-input--warning'); });
            form.querySelectorAll('.select2-selection--warning').forEach(function (el) { el.classList.remove('select2-selection--warning'); });

            const method = resolveFormMethod(form);
            const formData = new FormData(form);
            if (method === 'PUT' || method === 'PATCH' || method === 'DELETE') {
                formData.append('_method', method);
            }
            window.stripFormattedFields?.(formData, form);

            try {
                const response = await fetch(form.action, {
                    method: method === 'GET' ? 'GET' : 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: method === 'GET' ? null : formData,
                });
                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        Object.entries(data.errors).forEach(function ([field, messages]) {
                            const input = form.querySelector('[name="' + field + '"]');
                            if (input) {
                                input.classList.add('form-input--warning');
                                input.setAttribute('aria-invalid', 'true');

                                if (input.classList.contains('select2-hidden-accessible')) {
                                    const select2Container = input.nextElementSibling;
                                    const select2Selection = select2Container?.querySelector?.('.select2-selection');
                                    select2Selection?.classList.add('select2-selection--warning');
                                }

                                const error = document.createElement('p');
                                error.className = 'field-error field-warning mt-1 text-xs';
                                error.textContent = messages[0];

                                if (input.classList.contains('select2-hidden-accessible')) {
                                    const select2Container = input.nextElementSibling;
                                    select2Container?.parentNode?.insertBefore(error, select2Container.nextSibling);
                                } else {
                                    input.parentNode.appendChild(error);
                                }
                            }
                        });

                        const serverErrors = Object.values(data.errors).flat();
                        await window.showAlert?.({
                            title: 'Validasi Gagal',
                            message: data.message || 'Periksa kembali data yang Anda masukkan.',
                            items: serverErrors,
                            variant: 'danger',
                        });
                    } else {
                        await window.showAlert?.({
                            title: 'Gagal',
                            message: data.message || 'Terjadi kesalahan.',
                            variant: 'danger',
                        });
                    }
                    return;
                }

                window.showToast?.(data.message || 'Berhasil.', 'success');
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (form.dataset.fetchRedirect) {
                    window.location.href = form.dataset.fetchRedirect;
                    return;
                }
                if (form.hasAttribute('data-reload-page')) {
                    setTimeout(function () { window.location.reload(); }, 300);
                    return;
                }
                if (shouldReloadDatatable(form)) {
                    window.reloadMainTable?.() ?? window.mainTable?.ajax?.reload?.(null, false);
                }
                if (form.dataset.closeModal) {
                    document.getElementById(form.dataset.closeModal)?.classList.add('hidden');
                }
                if (form.dataset.resetOnSuccess !== 'false') {
                    form.reset();
                    window.resetAjaxSelects?.(form);
                }
                form.dispatchEvent(new CustomEvent('fetch-success', { detail: data }));
            } catch (err) {
                await window.showAlert?.({
                    title: 'Koneksi Gagal',
                    message: 'Tidak dapat terhubung ke server. Coba lagi.',
                    variant: 'danger',
                });
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        });

        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('[data-fetch-delete]');
            if (!btn) return;
            e.preventDefault();

            const confirmed = await window.showConfirm?.(confirmOptionsFromElement(btn, {
                title: 'Konfirmasi Hapus',
                message: 'Yakin ingin menghapus data ini?',
                confirmText: 'Ya, Hapus',
                tone: 'danger',
                confirmIcon: 'ti-trash',
            }));
            if (!confirmed) return;

            const response = await fetch(btn.dataset.fetchDelete, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            if (response.ok) {
                window.showToast?.(data.message, 'success');
                btn.dispatchEvent(new CustomEvent('fetch-delete-success', {
                    bubbles: true,
                    detail: data,
                }));
                if (btn.dataset.fetchDeleteRedirect) {
                    window.location.href = btn.dataset.fetchDeleteRedirect;
                    return;
                }
                if (btn.hasAttribute('data-reload-page')) {
                    setTimeout(function () { window.location.reload(); }, 300);
                    return;
                }
                window.reloadMainTable?.() ?? window.mainTable?.ajax?.reload?.(null, false);
            } else {
                await window.showAlert?.({
                    title: 'Tidak Dapat Dihapus',
                    message: data.message || 'Gagal menghapus data.',
                    variant: 'danger',
                });
            }
        });

        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('[data-fetch-post]');
            if (!btn) return;
            e.preventDefault();

            const confirmed = await window.showConfirm?.(confirmOptionsFromElement(btn, {
                title: 'Konfirmasi',
                message: 'Yakin melanjutkan aksi ini?',
                confirmText: 'Ya, Lanjutkan',
                tone: 'warning',
            }));
            if (!confirmed) return;

            const response = await fetch(btn.dataset.fetchPost, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            if (response.ok) {
                window.showToast?.(data.message, 'success');
                window.reloadMainTable?.() ?? window.mainTable?.ajax?.reload?.(null, false);
            } else {
                await window.showAlert?.({
                    title: 'Gagal',
                    message: data.message || 'Aksi gagal diproses.',
                    variant: 'danger',
                });
            }
        });
    };
})();
