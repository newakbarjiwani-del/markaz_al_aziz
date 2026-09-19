(function () {
    function setField(root, field, value) {
        var el = root.querySelector('[data-field="' + field + '"]');
        if (!el) {
            return;
        }

        if (field === 'status') {
            var isSuccess = value === 'success';
            el.innerHTML = '<span class="badge ' + (isSuccess ? 'badge-success' : 'badge-danger') + '">'
                + (isSuccess ? 'Berhasil' : 'Gagal') + '</span>';
            return;
        }

        el.textContent = value && String(value).trim() !== '' ? value : '-';
    }

    function openLoginLogDetail(logId) {
        var root = document.getElementById('login-log-detail-root');
        var modal = document.getElementById('login-log-detail-modal');
        if (!root || !modal || !logId) {
            return;
        }

        var baseUrl = root.dataset.showUrl;
        modal.classList.remove('hidden');

        root.querySelectorAll('[data-field]').forEach(function (el) {
            el.textContent = 'Memuat…';
        });

        fetch(baseUrl + '/' + encodeURIComponent(logId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Gagal memuat detail log login.');
                }

                return response.json();
            })
            .then(function (payload) {
                var data = payload.data || {};
                setField(root, 'created_at', data.created_at);
                setField(root, 'status', data.status);
                setField(root, 'method_label', data.method_label);
                setField(root, 'identifier', data.identifier);
                setField(root, 'username', data.username);
                setField(root, 'name', data.name);
                setField(root, 'role', data.role);
                setField(root, 'ip_address', data.ip_address);
                setField(root, 'browser', data.browser);
                setField(root, 'platform', data.platform);
                setField(root, 'device', data.device);
                setField(root, 'user_agent', data.user_agent);
                setField(root, 'message', data.message);
                setField(root, 'portal_access_token_id', data.portal_access_token_id);
            })
            .catch(function () {
                window.showAlert?.({
                    title: 'Gagal',
                    message: 'Detail log login tidak dapat dimuat.',
                    tone: 'danger',
                });
                modal.classList.add('hidden');
            });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-login-log-detail]');
        if (!button) {
            return;
        }

        openLoginLogDetail(button.dataset.logId);
    });

    window.initLoginLogDetail = function () {
        // Event delegation only — nothing to init.
    };
})();
