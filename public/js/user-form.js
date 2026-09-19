(function () {
    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function toggleCustIdField(form) {
        var role = qs('#user-role', form)?.value;
        var field = qs('#user-custid-field', form);
        var input = qs('#user-custid', form);
        var isSiswa = role === 'siswa';
        field?.classList.toggle('hidden', !isSiswa);
        if (input) {
            input.required = isSiswa;
            if (!isSiswa) {
                input.value = '';
            }
        }
    }

    function setSelfEditMode(form, isSelf, canChangePassword) {
        var note = qs('#user-self-edit-note', form);
        var passwordFields = qs('#user-password-change-fields', form);
        var confirmPanel = qs('#user-self-confirm-panel', form);
        var passwordHint = qs('#user-password-hint', form);

        note?.classList.toggle('hidden', !isSelf);

        ['#user-role', '#user-status', '#user-sekolah', '#user-custid'].forEach(function (sel) {
            var el = qs(sel, form);
            if (!el) {
                return;
            }
            if (el.dataset.forcedDisabled === '1') {
                el.disabled = true;
                return;
            }
            el.disabled = isSelf;
        });

        if (confirmPanel) {
            confirmPanel.classList.toggle('hidden', !isSelf);
            var current = qs('#user-current-password', form);
            if (current) {
                current.required = isSelf;
                if (!isSelf) {
                    current.value = '';
                }
            }
        }

        if (passwordFields) {
            var locked = !isSelf && canChangePassword === false;
            passwordFields.classList.toggle('hidden', locked);
            if (passwordHint) {
                passwordHint.textContent = isSelf
                    ? 'Opsional. Isi password saat ini di bawah jika mengubah data akun.'
                    : 'Wajib untuk user baru. Kosongkan saat edit jika tidak diubah.';
            }
            ['#user-password', '#user-password-confirmation'].forEach(function (sel) {
                var input = qs(sel, form);
                if (input) {
                    input.required = false;
                    if (locked) {
                        input.value = '';
                    }
                }
            });
        }

        toggleCustIdField(form);
    }

    function prepareCreateForm(form) {
        setSelfEditMode(form, false, true);
        var forced = form.getAttribute('data-forced-sekolah') || '';
        if (forced) {
            var sekolah = qs('#user-sekolah', form);
            if (sekolah) {
                sekolah.value = forced;
                sekolah.disabled = true;
                sekolah.dataset.forcedDisabled = '1';
            }
        }
        qs('#user-password', form)?.removeAttribute('value');
        qs('#user-password-confirmation', form)?.removeAttribute('value');
        qs('#user-custid', form).value = '';
        toggleCustIdField(form);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = qs('[data-user-form]');
        if (!form) {
            return;
        }

        prepareCreateForm(form);

        qs('#user-role', form)?.addEventListener('change', function () {
            toggleCustIdField(form);
        });

        document.querySelector('[data-open-modal="user-modal"]')?.addEventListener('click', function () {
            setTimeout(function () {
                prepareCreateForm(form);
            }, 0);
        });

        document.addEventListener('edit-record-populated', function (e) {
            if (!e.detail || e.detail.form !== form) {
                return;
            }

            var btn = e.detail.button;
            var canChangePassword = btn?.dataset.canChangePassword !== '0';
            var isSelf = btn?.dataset.editSelf === '1';
            setSelfEditMode(form, isSelf, canChangePassword);
            qs('#user-password', form).value = '';
            qs('#user-password-confirmation', form).value = '';
            toggleCustIdField(form);
        });
    });
})();
