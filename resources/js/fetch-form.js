export function initFetchForms() {
    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('[data-fetch-form]');
        if (!form) return;

        e.preventDefault();

        if (!(await window.validateFormWithDialog?.(form))) {
            return;
        }

        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn?.innerHTML;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Menyimpan...';
        }

        form.querySelectorAll('.field-error').forEach((el) => el.remove());
        form.querySelectorAll('.border-red-500').forEach((el) => el.classList.remove('border-red-500'));

        const method = (form.dataset.method || form.method || 'POST').toUpperCase();
        const url = form.action;
        const formData = new FormData(form);

        if (method === 'PUT' || method === 'PATCH' || method === 'DELETE') {
            formData.append('_method', method);
        }

        try {
            const response = await fetch(url, {
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
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('border-red-500');
                            const error = document.createElement('p');
                            error.className = 'field-error mt-1 text-xs text-red-600';
                            error.textContent = messages[0];
                            input.parentNode.appendChild(error);
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

            if (form.dataset.reloadTable && window.mainTable) {
                window.mainTable.ajax.reload(null, false);
            }

            if (form.dataset.closeModal) {
                document.getElementById(form.dataset.closeModal)?.classList.add('hidden');
            }

            if (form.dataset.resetOnSuccess !== 'false') {
                form.reset();
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

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-fetch-delete]');
        if (!btn) return;

        e.preventDefault();

        const confirmed = await window.showConfirm?.({
            title: btn.dataset.confirmTitle || 'Konfirmasi Hapus',
            message: btn.dataset.confirmMessage || 'Yakin ingin menghapus data ini?',
            detail: btn.dataset.confirmDetail || '',
            confirmText: btn.dataset.confirmText || 'Ya, Hapus',
        });
        if (!confirmed) return;

        const url = btn.dataset.fetchDelete;
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();
        if (response.ok) {
            window.showToast?.(data.message, 'success');
            window.mainTable?.ajax.reload(null, false);
        } else {
            await window.showAlert?.({
                title: 'Tidak Dapat Dihapus',
                message: data.message || 'Gagal menghapus data.',
                variant: 'danger',
            });
        }
    });
}
