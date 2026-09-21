(function () {
    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function initOrtuBayarTagihan() {
        var root = document.getElementById('ortu-tagihan-page');
        if (!root) return;

        var unpaidUrl = root.dataset.unpaidUrl || '';
        var payUrl = root.dataset.payUrl || '';
        var qrisUrl = root.dataset.qrisUrl || '';
        var qrisEnabled = root.dataset.qrisEnabled === '1';
        var defaultSiswaId = root.dataset.defaultSiswaId || '';

        var siswaSelect = document.getElementById('ortu-bayar-siswa');
        var saldoBox = document.getElementById('ortu-bayar-saldo-box');
        var saldoLabel = document.getElementById('ortu-bayar-saldo-label');
        var siswaLabel = document.getElementById('ortu-bayar-siswa-label');
        var emptyEl = document.getElementById('ortu-bayar-empty');
        var listEl = document.getElementById('ortu-bayar-list');
        var summaryEl = document.getElementById('ortu-bayar-summary');
        var totalLabel = document.getElementById('ortu-bayar-total-label');
        var hintEl = document.getElementById('ortu-bayar-hint');
        var submitBtn = document.getElementById('ortu-bayar-submit');
        var qrisBtn = document.getElementById('ortu-bayar-qris');

        var state = {
            siswaId: '',
            saldo: 0,
            items: [],
        };

        function selectedIds() {
            return Array.from(listEl.querySelectorAll('input[type="checkbox"][data-tagihan-id]:checked'))
                .map(function (input) { return Number(input.dataset.tagihanId); });
        }

        function selectedTotal() {
            var ids = selectedIds();
            return state.items
                .filter(function (item) { return ids.indexOf(item.id) !== -1; })
                .reduce(function (sum, item) { return sum + Number(item.remaining || 0); }, 0);
        }

        function updateSummary() {
            var total = selectedTotal();
            var count = selectedIds().length;
            var enough = total > 0 && total <= state.saldo;

            if (summaryEl) {
                summaryEl.classList.toggle('hidden', count === 0 && state.items.length === 0);
            }
            if (totalLabel) totalLabel.textContent = formatRupiah(total);

            if (hintEl) {
                if (count === 0) {
                    hintEl.textContent = state.items.length
                        ? 'Pilih minimal satu tagihan untuk dibayar.'
                        : 'Tidak ada tagihan belum lunas.';
                } else if (!enough) {
                    hintEl.textContent = 'Saldo tidak mencukupi untuk bayar dari saldo. Anda masih bisa bayar via QRIS.'
                        + (qrisEnabled ? '' : '');
                } else {
                    hintEl.textContent = count + ' tagihan siap dibayar (saldo atau QRIS).';
                }
            }

            if (submitBtn) {
                submitBtn.disabled = !(count > 0 && enough && state.siswaId);
            }
            if (qrisBtn) {
                qrisBtn.disabled = !(qrisEnabled && count > 0 && total > 0 && state.siswaId);
            }
        }

        function renderList(items) {
            if (!listEl || !emptyEl) return;

            if (!items.length) {
                listEl.classList.add('hidden');
                listEl.innerHTML = '';
                emptyEl.classList.remove('hidden');
                emptyEl.textContent = 'Tidak ada tagihan belum lunas untuk anak ini.';
                updateSummary();
                return;
            }

            emptyEl.classList.add('hidden');
            listEl.classList.remove('hidden');
            listEl.innerHTML = items.map(function (item) {
                return ''
                    + '<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-700">'
                    + '<input type="checkbox" class="mt-1" data-tagihan-id="' + item.id + '" data-remaining="' + item.remaining + '">'
                    + '<span class="min-w-0 flex-1">'
                    + '<span class="block font-medium text-slate-900 dark:text-white">' + (item.jenis || '-') + '</span>'
                    + '<span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">'
                    + (item.periode || '-') + (item.due_date ? ' · Jatuh tempo ' + item.due_date : '')
                    + '</span>'
                    + '</span>'
                    + '<span class="shrink-0 font-semibold text-slate-900 dark:text-white">' + (item.remaining_label || formatRupiah(item.remaining)) + '</span>'
                    + '</label>';
            }).join('');

            listEl.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
                input.addEventListener('change', updateSummary);
            });

            updateSummary();
        }

        function resetModal() {
            state = { siswaId: '', saldo: 0, items: [] };
            if (saldoBox) saldoBox.classList.add('hidden');
            if (summaryEl) summaryEl.classList.add('hidden');
            if (listEl) {
                listEl.classList.add('hidden');
                listEl.innerHTML = '';
            }
            if (emptyEl) {
                emptyEl.classList.remove('hidden');
                emptyEl.textContent = 'Pilih anak untuk melihat tagihan yang dapat dibayar.';
            }
            if (submitBtn) submitBtn.disabled = true;
        }

        function loadUnpaid(siswaId) {
            if (!siswaId || !unpaidUrl) {
                resetModal();
                return;
            }

            if (emptyEl) {
                emptyEl.classList.remove('hidden');
                emptyEl.textContent = 'Memuat tagihan...';
            }
            if (listEl) {
                listEl.classList.add('hidden');
                listEl.innerHTML = '';
            }
            if (submitBtn) submitBtn.disabled = true;

            var url = unpaidUrl + (unpaidUrl.indexOf('?') === -1 ? '?' : '&') + 'siswa_id=' + encodeURIComponent(siswaId);

            fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) { return response.json().then(function (payload) {
                    return { ok: response.ok, payload: payload };
                }); })
                .then(function (result) {
                    if (!result.ok || !result.payload?.success) {
                        throw new Error(result.payload?.message || 'Gagal memuat tagihan.');
                    }

                    var data = result.payload.data || {};
                    state.siswaId = String(data.siswa?.id || siswaId);
                    state.saldo = Number(data.saldo || 0);
                    state.items = Array.isArray(data.tagihan) ? data.tagihan : [];

                    if (saldoBox) saldoBox.classList.remove('hidden');
                    if (saldoLabel) saldoLabel.textContent = data.saldo_label || formatRupiah(state.saldo);
                    if (siswaLabel) {
                        siswaLabel.textContent = [
                            data.siswa?.nis,
                            data.siswa?.name,
                            data.siswa?.kelas,
                        ].filter(Boolean).join(' · ');
                    }

                    renderList(state.items);
                })
                .catch(function (error) {
                    resetModal();
                    if (emptyEl) {
                        emptyEl.classList.remove('hidden');
                        emptyEl.textContent = error.message || 'Gagal memuat tagihan.';
                    }
                    window.showToast?.(error.message || 'Gagal memuat tagihan.', 'error');
                });
        }

        async function submitPayment() {
            var ids = selectedIds();
            var total = selectedTotal();
            if (!state.siswaId || !ids.length || total <= 0) return;

            var confirmed = await window.showConfirm?.({
                title: 'Bayar dari Saldo',
                message: 'Bayar ' + ids.length + ' tagihan sebesar ' + formatRupiah(total) + ' menggunakan saldo keuangan anak?',
                detail: [
                    { label: 'Anak', value: siswaLabel?.textContent || '-' },
                    { label: 'Saldo tersedia', value: formatRupiah(state.saldo) },
                    { label: 'Total bayar', value: formatRupiah(total) },
                ],
                confirmText: 'Ya, Bayar',
                tone: 'warning',
                confirmIcon: 'ti-wallet',
                headerIcon: 'ti-wallet',
                footnote: 'Saldo keuangan anak akan dikurangi sesuai total tagihan terpilih.',
            });
            if (!confirmed) return;

            var original = submitBtn?.innerHTML;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Memproses...';
            }

            try {
                var response = await fetch(payUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        siswa_id: Number(state.siswaId),
                        items: ids.map(function (id) { return { tagihan_id: id }; }),
                    }),
                });
                var payload = await response.json();

                if (!response.ok || !payload.success) {
                    await window.showAlert?.({
                        title: 'Pembayaran Gagal',
                        message: payload.message || 'Tidak dapat memproses pembayaran.',
                        variant: 'danger',
                    });
                    return;
                }

                window.showToast?.(payload.message || 'Pembayaran berhasil.', 'success');
                document.getElementById('ortu-bayar-tagihan-modal')?.classList.add('hidden');
                window.reloadMainTable?.();
                loadUnpaid(state.siswaId);
            } catch (error) {
                await window.showAlert?.({
                    title: 'Koneksi Gagal',
                    message: 'Tidak dapat terhubung ke server. Coba lagi.',
                    variant: 'danger',
                });
            } finally {
                if (submitBtn) {
                    submitBtn.innerHTML = original;
                    updateSummary();
                }
            }
        }

        async function submitQris() {
            var ids = selectedIds();
            var total = selectedTotal();
            if (!qrisEnabled || !qrisUrl || !state.siswaId || !ids.length || total <= 0) return;

            var original = qrisBtn?.innerHTML;
            if (qrisBtn) {
                qrisBtn.disabled = true;
                qrisBtn.innerHTML = 'Membuat QR...';
            }

            try {
                var response = await fetch(qrisUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        siswa_id: Number(state.siswaId),
                        tagihan_ids: ids,
                    }),
                });
                var payload = await response.json();

                if (!response.ok || !payload.success) {
                    await window.showAlert?.({
                        title: 'QRIS Gagal',
                        message: payload.message || 'Tidak dapat membuat QRIS.',
                        variant: 'danger',
                    });
                    return;
                }

                var qris = payload.data.qris || {};
                document.querySelector('[data-modal-close="ortu-bayar-tagihan-modal"]')?.click();
                document.getElementById('ortu-bayar-tagihan-modal')?.classList.add('hidden');
                window.QrisPaymentUi?.open({
                    qris: qris,
                    statusUrl: '/portal/ortu/tagihan/qris/' + qris.id,
                    checkUrl: '/portal/ortu/tagihan/qris/' + qris.id + '/check',
                    onPaid: function () {
                        window.showToast?.('Pembayaran QRIS berhasil.', 'success');
                        window.reloadMainTable?.();
                        loadUnpaid(state.siswaId);
                    },
                });
            } catch (error) {
                await window.showAlert?.({
                    title: 'Koneksi Gagal',
                    message: 'Tidak dapat terhubung ke server. Coba lagi.',
                    variant: 'danger',
                });
            } finally {
                if (qrisBtn) {
                    qrisBtn.innerHTML = original;
                    updateSummary();
                }
            }
        }

        document.querySelector('[data-open-modal="ortu-bayar-tagihan-modal"]')?.addEventListener('click', function () {
            setTimeout(function () {
                var siswaId = siswaSelect?.value || defaultSiswaId;
                if (siswaSelect && !siswaSelect.value && defaultSiswaId) {
                    siswaSelect.value = defaultSiswaId;
                    siswaId = defaultSiswaId;
                }
                if (siswaId) {
                    loadUnpaid(siswaId);
                } else {
                    resetModal();
                }
            }, 0);
        });

        siswaSelect?.addEventListener('change', function () {
            loadUnpaid(siswaSelect.value);
        });

        submitBtn?.addEventListener('click', function () {
            submitPayment();
        });
        qrisBtn?.addEventListener('click', function () {
            submitQris();
        });
    }

    document.addEventListener('DOMContentLoaded', initOrtuBayarTagihan);
})();
