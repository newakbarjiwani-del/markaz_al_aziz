(function () {
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function copyText(text) {
        if (!text) {
            throw new Error('Link login belum tersedia.');
        }

        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }

        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }

    document.addEventListener('click', async function (event) {
        var copyBtn = event.target.closest('[data-portal-token-copy]');
        if (copyBtn) {
            event.preventDefault();
            var urlInput = document.getElementById('portal-token-url');

            try {
                await copyText(urlInput?.value || '');
                window.showToast?.('Link login berhasil disalin.', 'success');
            } catch (error) {
                await window.showAlert?.({
                    title: 'Gagal',
                    message: error.message || 'Gagal menyalin link.',
                    variant: 'danger',
                });
            }

            return;
        }

        var refreshBtn = event.target.closest('[data-portal-token-refresh]');
        if (!refreshBtn) {
            return;
        }

        event.preventDefault();

        var hasLink = Boolean(document.getElementById('portal-token-url')?.value);
        var confirmed = await window.showConfirm?.({
            title: hasLink ? 'Perbarui Link Login' : 'Buat Link Login',
            message: hasLink
                ? 'Link login lama akan dinonaktifkan. Lanjutkan?'
                : 'Buat link login baru untuk perangkat lain?',
            tone: 'primary',
            confirmText: hasLink ? 'Ya, Perbarui' : 'Ya, Buat',
        });

        if (!confirmed) {
            return;
        }

        refreshBtn.disabled = true;

        try {
            var response = await fetch(refreshBtn.dataset.portalTokenRefreshUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
                redirect: 'manual',
            });

            if (response.status >= 300 && response.status < 400) {
                throw new Error('Sesi berakhir. Muat ulang halaman dan coba lagi.');
            }

            var data = await response.json();
            if (!response.ok || !data?.success) {
                throw new Error(data?.message || 'Gagal memperbarui link login.');
            }

            window.showToast?.(data.message || 'Link login berhasil diperbarui.', 'success');
            window.location.reload();
        } catch (error) {
            await window.showAlert?.({
                title: 'Gagal',
                message: error.message || 'Gagal memperbarui link login.',
                variant: 'danger',
            });
        } finally {
            refreshBtn.disabled = false;
        }
    });
})();
