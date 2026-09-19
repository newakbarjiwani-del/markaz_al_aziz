document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('payment-cancel-log-detail-root');
    var modal = document.getElementById('payment-cancel-log-detail-modal');
    var itemsBody = document.getElementById('payment-cancel-log-items-body');

    if (!root || !modal) {
        return;
    }

    function setField(field, value) {
        var el = root.querySelector('[data-field="' + field + '"]');
        if (!el) {
            return;
        }

        el.textContent = value && String(value).trim() !== '' ? value : '-';
    }

    function renderItems(items) {
        if (!itemsBody) {
            return;
        }

        if (!items || !items.length) {
            itemsBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-slate-500">Tidak ada detail tagihan.</td></tr>';
            return;
        }

        itemsBody.innerHTML = items.map(function (item) {
            var cicilanBadge = item.is_cicilan
                ? ' <span class="badge badge-warning ml-1">Cicilan</span>'
                : '';

            return '<tr class="border-t border-slate-100 dark:border-slate-800">'
                + '<td class="px-4 py-3 font-medium text-slate-900 dark:text-white">' + (item.jenis || '-') + cicilanBadge + '</td>'
                + '<td class="px-4 py-3 text-slate-600 dark:text-slate-300">' + (item.periode || '-') + '</td>'
                + '<td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">' + (item.bill_amount_label || item.amount_label || '-') + '</td>'
                + '<td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">' + (item.paid_total_label || item.amount_label || '-') + '</td>'
                + '<td class="px-4 py-3 text-right font-medium text-slate-900 dark:text-white">' + (item.amount_label || '-') + '</td>'
                + '</tr>';
        }).join('');
    }

    function openDetail(logId) {
        var baseUrl = root.dataset.showUrl;
        if (!baseUrl || !logId) {
            return;
        }

        modal.classList.remove('hidden');
        root.querySelectorAll('[data-field]').forEach(function (el) {
            el.textContent = 'Memuat…';
        });

        if (itemsBody) {
            itemsBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-slate-500">Memuat tagihan...</td></tr>';
        }

        fetch(baseUrl + '/' + encodeURIComponent(logId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Gagal memuat detail log.');
                    }

                    return payload.data;
                });
            })
            .then(function (data) {
                var siswa = data.siswa || {};
                var cancelledBy = data.cancelled_by || {};
                var originalOperator = data.original_operator || {};

                setField('created_at', data.created_at);
                setField('reference', data.reference);
                setField('siswa_name', siswa.name);
                setField('siswa_meta', [siswa.nis, siswa.kelas].filter(Boolean).join(' · '));
                setField('method_label', data.method_label);
                setField('paid_at', data.paid_at);
                setField('original_operator', [originalOperator.username, originalOperator.name].filter(Boolean).join(' · '));
                setField('cancelled_by', [cancelledBy.username, cancelledBy.name].filter(Boolean).join(' · '));
                setField('ip_address', data.ip_address);
                setField('pembayaran_id', data.pembayaran_id);
                setField('total_label', data.total_label);
                renderItems(data.items || []);
            })
            .catch(function (err) {
                window.showAlert?.({
                    title: 'Gagal',
                    message: err.message || 'Detail log pembatalan tidak dapat dimuat.',
                    variant: 'danger',
                });
                modal.classList.add('hidden');
            });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-payment-cancel-log-detail]');
        if (!button) {
            return;
        }

        openDetail(button.dataset.logId);
    });
});
