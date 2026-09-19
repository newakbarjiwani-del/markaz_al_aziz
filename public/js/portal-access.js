(function () {
    function normalizeWhatsAppPhone(phone) {
        var digits = String(phone || '').replace(/\D/g, '');
        if (!digits) {
            return null;
        }
        if (digits.charAt(0) === '0') {
            digits = '62' + digits.slice(1);
        }
        if (digits.indexOf('62') !== 0) {
            digits = '62' + digits;
        }
        return digits;
    }

    function buildWaMeUrl(phone, message) {
        var normalized = normalizeWhatsAppPhone(phone);
        if (!normalized || !message) {
            return null;
        }
        return 'https://wa.me/' + normalized + '?text=' + encodeURIComponent(message);
    }

    function openWhatsAppDirect(phone, message, fallbackUrl) {
        var url = buildWaMeUrl(phone, message) || fallbackUrl;
        if (!url) {
            return false;
        }
        window.open(url, '_blank', 'noopener,noreferrer');
        return true;
    }

    async function copyToClipboard(text) {
        if (!text) {
            throw new Error('Link login tidak tersedia.');
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

    async function portalRequest(url, options) {
        var method = options?.method || 'POST';
        var body = options?.body ?? null;
        var hasBody = body !== null && method !== 'GET' && method !== 'HEAD';

        var response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(hasBody ? { 'Content-Type': 'application/json' } : {}),
            },
            body: hasBody ? JSON.stringify(body) : null,
        });

        var data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Permintaan gagal.');
        }

        return data;
    }

    function afterPortalMutation() {
        if (document.getElementById('main_table') && typeof window.reloadMainTable === 'function') {
            window.reloadMainTable();
            return;
        }

        if (document.querySelector('[data-portal-actions-context="page"]')) {
            window.location.reload();
        }
    }

    function revokeConfirmCopy(role) {
        if (role === 'orang_tua') {
            return {
                title: 'Cabut Token Orang Tua',
                message: 'Yakin ingin mencabut token login portal orang tua yang aktif?',
            };
        }

        if (role === 'siswa') {
            return {
                title: 'Cabut Token Siswa',
                message: 'Yakin ingin mencabut token login portal siswa yang aktif?',
            };
        }

        return {
            title: 'Cabut Token Akses',
            message: 'Yakin ingin mencabut semua token login portal yang aktif?',
        };
    }

    document.addEventListener('click', async function (event) {
        var generateBtn = event.target.closest('[data-portal-generate]');
        if (generateBtn) {
            event.preventDefault();
            generateBtn.disabled = true;

            try {
                var generateResult = await portalRequest(generateBtn.dataset.portalUrl, { method: 'POST', body: {} });
                window.showToast?.(generateResult.message || 'Token login berhasil dibuat.', 'success');
                afterPortalMutation();
            } catch (error) {
                await window.showAlert?.({
                    title: 'Gagal',
                    message: error.message || 'Gagal membuat token login.',
                    variant: 'danger',
                });
            } finally {
                generateBtn.disabled = false;
            }

            return;
        }

        var copyBtn = event.target.closest('[data-portal-copy]');
        if (copyBtn) {
            event.preventDefault();
            copyBtn.disabled = true;

            try {
                var copyResult = await portalRequest(copyBtn.dataset.portalUrl, { method: 'GET' });
                await copyToClipboard(copyResult.data?.access_url);
                window.showToast?.(copyResult.message || 'Link login berhasil disalin.', 'success');
            } catch (error) {
                await window.showAlert?.({
                    title: 'Gagal',
                    message: error.message || 'Gagal menyalin link login.',
                    variant: 'danger',
                });
            } finally {
                copyBtn.disabled = false;
            }

            return;
        }

        var waBtn = event.target.closest('[data-portal-wa]');
        if (waBtn) {
            event.preventDefault();
            waBtn.disabled = true;

            try {
                var result = await portalRequest(waBtn.dataset.portalUrl, { method: 'POST', body: {} });
                var opened = openWhatsAppDirect(
                    result.data?.phone,
                    result.data?.message,
                    result.data?.whatsapp_url
                );

                if (!opened) {
                    throw new Error('Nomor WhatsApp tidak tersedia.');
                }

                window.showToast?.(result.message || 'WhatsApp dibuka dengan link login.', 'success');
                afterPortalMutation();
            } catch (error) {
                await window.showAlert?.({
                    title: 'Gagal',
                    message: error.message || 'Gagal membuka WhatsApp.',
                    variant: 'danger',
                });
            } finally {
                waBtn.disabled = false;
            }

            return;
        }

        var revokeBtn = event.target.closest('[data-portal-revoke]');
        if (!revokeBtn) {
            return;
        }

        event.preventDefault();

        var role = revokeBtn.dataset.portalRole || null;
        var confirmCopy = revokeConfirmCopy(role);
        var confirmed = await window.showConfirm?.({
            title: confirmCopy.title,
            message: confirmCopy.message,
            tone: 'primary',
            confirmText: 'Ya, Cabut',
        });

        if (!confirmed) {
            return;
        }

        revokeBtn.disabled = true;

        try {
            var payload = role ? { role: role } : null;
            var revokeResult = await portalRequest(revokeBtn.dataset.portalUrl, {
                method: 'DELETE',
                body: payload,
            });
            window.showToast?.(revokeResult.message || 'Token login berhasil dicabut.', 'success');
            afterPortalMutation();
        } catch (error) {
            await window.showAlert?.({
                title: 'Gagal',
                message: error.message || 'Gagal mencabut token.',
                variant: 'danger',
            });
        } finally {
            revokeBtn.disabled = false;
        }
    });
})();
