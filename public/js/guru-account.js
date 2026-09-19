(function () {
    var state = {
        mode: 'create',
        submitUrl: '',
        guruName: '',
    };

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function parseJsonResponse(raw) {
        try {
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            throw new Error('Server mengembalikan respons tidak valid. Muat ulang halaman dan coba lagi.');
        }
    }

    async function requestJson(url, method, fields) {
        var options = {
            method: method || 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            redirect: 'manual',
        };

        if (fields && method !== 'GET') {
            var formData = new FormData();
            Object.entries(fields).forEach(function (entry) {
                formData.append(entry[0], entry[1]);
            });
            formData.append('_token', csrfToken());
            options.body = formData;
        }

        var response = await fetch(url, options);

        if (response.status >= 300 && response.status < 400) {
            throw new Error('Sesi berakhir atau permintaan ditolak. Muat ulang halaman dan coba lagi.');
        }

        var data = parseJsonResponse(await response.text());

        if (!response.ok || !data?.success) {
            var message = data?.message || 'Permintaan gagal diproses.';
            if (data?.errors) {
                var firstError = Object.values(data.errors).flat()[0];
                if (firstError) message = firstError;
            }
            throw new Error(message);
        }

        return data;
    }

    function modalEl() {
        return document.getElementById('guru-account-modal');
    }

    function formEl() {
        return document.getElementById('guru-account-form');
    }

    function openModal() {
        modalEl()?.classList.remove('hidden');
    }

    function closeModal() {
        modalEl()?.classList.add('hidden');
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    }

    function resetForm() {
        formEl()?.reset();
    }

    function configureCreateMode(btn) {
        state.mode = 'create';
        state.submitUrl = btn.dataset.accountUrl || '';
        state.guruName = btn.dataset.guruName || 'Guru';

        document.getElementById('guru-account-existing')?.classList.add('hidden');
        setText('guru-account-intro', 'Buat akun login portal untuk ' + state.guruName + '. Username dibuat otomatis dari NIP (contoh: gr-ma-002).');

        var submit = document.getElementById('guru-account-submit');
        if (submit) submit.textContent = 'Buat Akun';

        var title = modalEl()?.querySelector('h3');
        if (title) title.textContent = 'Buat Akun Login Guru';
    }

    async function configureManageMode(btn) {
        state.mode = 'reset';
        state.submitUrl = btn.dataset.accountResetUrl || '';
        state.guruName = btn.dataset.guruName || 'Guru';

        var data = await requestJson(btn.dataset.accountShowUrl, 'GET');
        var account = data.data || {};

        document.getElementById('guru-account-existing')?.classList.remove('hidden');
        setText('guru-account-intro', 'Perbarui password login untuk ' + state.guruName + '.');
        setText('guru-account-username', account.username);
        setText('guru-account-email', account.email);
        setText('guru-account-status', account.status ? account.status.charAt(0).toUpperCase() + account.status.slice(1) : '-');

        var submit = document.getElementById('guru-account-submit');
        if (submit) submit.textContent = 'Simpan Password Baru';

        var title = modalEl()?.querySelector('h3');
        if (title) title.textContent = 'Kelola Akun Login Guru';
    }

    async function openFromTrigger(btn) {
        resetForm();

        try {
            if (btn.dataset.hasAccount === '1') {
                await configureManageMode(btn);
            } else {
                configureCreateMode(btn);
            }

            openModal();
        } catch (err) {
            await window.showAlert?.({
                title: 'Gagal',
                message: err.message || 'Tidak dapat memuat data akun guru.',
                variant: 'danger',
            });
        }
    }

    async function submitForm(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        var form = formEl();
        if (!form || !state.submitUrl) return;

        var valid = await window.validateFormWithDialog?.(form);
        if (valid === false) return;

        var password = form.password?.value || '';
        var passwordConfirmation = form.password_confirmation?.value || '';

        if (password !== passwordConfirmation) {
            await window.showAlert?.({
                title: 'Validasi gagal',
                message: 'Konfirmasi password tidak cocok.',
                variant: 'danger',
            });
            return;
        }

        var submitBtn = document.getElementById('guru-account-submit');
        if (submitBtn) submitBtn.disabled = true;

        try {
            var data = await requestJson(state.submitUrl, 'POST', {
                password: password,
                password_confirmation: passwordConfirmation,
            });

            closeModal();
            window.showToast?.(data.message || 'Akun guru berhasil disimpan.', 'success');
            if (document.getElementById('main_table')) {
                window.reloadMainTable?.();
            } else {
                window.location.reload();
            }
        } catch (err) {
            await window.showAlert?.({
                title: 'Gagal',
                message: err.message || 'Tidak dapat menyimpan akun guru.',
                variant: 'danger',
            });
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    function bindGuruAccountForm() {
        var form = formEl();
        if (!form || form.dataset.guruAccountBound === '1') return;

        form.dataset.guruAccountBound = '1';
        form.setAttribute('action', '#');
        form.addEventListener('submit', submitForm, true);
        document.getElementById('guru-account-submit')?.addEventListener('click', submitForm);
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-guru-account-trigger]');
        if (!btn) return;

        openFromTrigger(btn);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindGuruAccountForm);
    } else {
        bindGuruAccountForm();
    }
})();
