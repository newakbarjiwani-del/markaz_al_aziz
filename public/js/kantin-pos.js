(function () {
    var root = document.getElementById('kantin-pos-root');
    if (!root) return;

    var form = document.getElementById('kantin-pos-form');
    var rfidInput = document.getElementById('kantin-rfid-input');
    var faceSiswaInput = document.getElementById('kantin-face-siswa');
    var saldoInput = document.getElementById('kantin-saldo-input');
    var billInput = document.getElementById('kantin-bill-input');
    var descriptionInput = document.getElementById('kantin-description-input');
    var submitBtn = document.getElementById('kantin-submit-btn');
    var studentSummary = document.getElementById('kantin-student-summary');
    var studentName = document.getElementById('kantin-student-name');
    var studentMeta = document.getElementById('kantin-student-meta');
    var lookupUrl = root.dataset.lookupUrl;
    var chargeUrl = root.dataset.chargeUrl;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    var currentRfid = '';
    var currentSiswaId = '';
    var activeMethod = 'rfid';
    var lookupTimer = null;

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function parseAmount(value) {
        if (window.parseFormattedNumber) {
            return window.parseFormattedNumber(value);
        }

        return Number(String(value || '').replace(/\D/g, '')) || 0;
    }

    function resetStudentState() {
        currentRfid = '';
        currentSiswaId = '';
        if (faceSiswaInput) {
            faceSiswaInput.value = '';
            faceSiswaInput.disabled = true;
        }
        if (submitBtn) submitBtn.disabled = true;
        if (saldoInput) saldoInput.value = 'Rp 0';
        if (studentSummary) studentSummary.classList.add('hidden');
        if (studentName) studentName.textContent = '-';
        if (studentMeta) studentMeta.textContent = '-';
    }

    function hasResolvedStudent() {
        return activeMethod === 'face' ? currentSiswaId !== '' : currentRfid !== '';
    }

    function setStudentState(payload) {
        var siswa = payload.siswa || {};
        var saldo = Number(payload.saldo_cashless ?? payload.saldo_kantin ?? payload.dompet?.saldo_kantin ?? 0);
        saldo = Math.max(0, saldo);

        if (activeMethod === 'face') {
            currentSiswaId = siswa.id != null ? String(siswa.id) : '';
            if (faceSiswaInput) {
                faceSiswaInput.disabled = currentSiswaId === '';
                faceSiswaInput.value = currentSiswaId;
            }
        } else {
            currentRfid = (rfidInput?.value || '').trim();
        }

        if (submitBtn) submitBtn.disabled = !hasResolvedStudent();
        if (saldoInput) saldoInput.value = formatRupiah(saldo);
        if (studentName) studentName.textContent = siswa.name || '-';
        if (studentMeta) {
            studentMeta.textContent = [
                siswa.nis ? 'NIS ' + siswa.nis : null,
                siswa.kelas || null,
            ].filter(Boolean).join(' · ') || '-';
        }
        if (studentSummary) studentSummary.classList.remove('hidden');
    }

    function setMethod(method) {
        activeMethod = method === 'face' ? 'face' : 'rfid';
        resetStudentState();

        root.querySelectorAll('[data-kantin-method]').forEach(function (btn) {
            var active = btn.getAttribute('data-kantin-method') === activeMethod;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        root.querySelectorAll('[data-kantin-panel]').forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-kantin-panel') !== activeMethod);
        });

        if (rfidInput) {
            rfidInput.disabled = activeMethod !== 'rfid';
            rfidInput.required = activeMethod === 'rfid';
            if (activeMethod === 'rfid') {
                rfidInput.value = '';
                window.setTimeout(function () { rfidInput.focus(); }, 50);
            }
        }

        if (window.kantinPosFace && typeof window.kantinPosFace.onMethodChange === 'function') {
            window.kantinPosFace.onMethodChange(activeMethod);
        }
    }

    function createRequestError(response, data) {
        var error = new Error(data.message || 'Permintaan gagal.');
        error.response = response;
        error.payload = data;
        return error;
    }

    function inferHint(message, context) {
        var text = String(message || '').toLowerCase();

        if (text.includes('tidak dikenali') || text.includes('tidak aktif')) {
            return context === 'lookup'
                ? 'Pastikan kartu sudah terdaftar di Data Siswa. Scan ulang kartu, atau hubungi admin jika masalah berlanjut.'
                : 'Scan ulang kartu siswa. Jika kartu sudah diganti, minta admin memperbarui RFID di Data Siswa.';
        }

        if (text.includes('diblokir')) {
            return 'Hubungi admin sekolah untuk membuka blokir kartu RFID siswa ini.';
        }

        if (text.includes('tidak terdaftar di sekolah')) {
            return 'Operator kantin hanya dapat melayani siswa dari sekolah yang sama.';
        }

        if (text.includes('limit harian siswa')) {
            return 'Kurangi total belanja sesuai sisa limit siswa hari ini, atau lanjutkan besok setelah limit direset.';
        }

        if (text.includes('limit harian sekolah')) {
            return 'Kurangi total belanja sesuai sisa limit sekolah hari ini, atau hubungi admin jika limit perlu disesuaikan.';
        }

        if (text.includes('saldo cashless tidak mencukupi') || text.includes('tidak mencukupi')) {
            return 'Kurangi nominal total belanja, atau minta siswa/ortu melakukan top-up saldo cashless terlebih dahulu.';
        }

        if (text.includes('rfid')) {
            return 'Hubungi admin sekolah untuk memeriksa status kartu RFID siswa.';
        }

        return context === 'lookup'
            ? 'Periksa kartu RFID lalu scan ulang.'
            : 'Periksa kembali data transaksi, lalu coba simpan ulang.';
    }

    function resolveFailureTitle(message, context) {
        var text = String(message || '').toLowerCase();

        if (text.includes('tidak dikenali') || text.includes('tidak aktif')) {
            return 'Kartu tidak dikenali';
        }

        if (text.includes('diblokir')) {
            return 'Kartu RFID diblokir';
        }

        if (text.includes('tidak terdaftar di sekolah')) {
            return 'Siswa di luar sekolah';
        }

        if (text.includes('limit harian siswa')) {
            return 'Limit harian siswa terlampaui';
        }

        if (text.includes('limit harian sekolah')) {
            return 'Limit harian sekolah terlampaui';
        }

        if (text.includes('saldo cashless tidak mencukupi') || text.includes('tidak mencukupi')) {
            return 'Saldo tidak mencukupi';
        }

        return context === 'lookup' ? 'Scan RFID gagal' : 'Transaksi gagal';
    }

    function buildFailureAlert(context, error) {
        var payload = error.payload || {};
        var message = payload.message || error.message || 'Permintaan tidak dapat diproses.';
        var hint = payload.hint || inferHint(message, context);
        var detail = '<strong>Solusi:</strong> ' + hint;
        var detailRows = [];

        if (payload.data && payload.data.saldo_cashless != null && payload.data.amount != null) {
            detailRows.push(
                { label: 'Saldo tersedia', value: formatRupiah(payload.data.saldo_cashless) },
                { label: 'Total belanja', value: formatRupiah(payload.data.amount) }
            );
        }

        return {
            title: resolveFailureTitle(message, context),
            message: message,
            detail: detailRows.length ? detailRows : detail,
            variant: context === 'lookup' ? 'warning' : 'danger',
        };
    }

    function showFailureAlert(context, error) {
        var options = buildFailureAlert(context, error);

        if (!error.response) {
            options.title = 'Koneksi gagal';
            options.message = 'Tidak dapat terhubung ke server.';
            options.detail = '<strong>Solusi:</strong> Periksa koneksi internet, lalu coba lagi.';
            options.variant = 'danger';
        }

        window.showAlert?.(options);
    }

    async function postJson(url, payload) {
        var response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw createRequestError(response, data);
        }

        return data;
    }

    async function lookupStudent() {
        var uid = (rfidInput?.value || '').trim();
        if (!uid) {
            resetStudentState();
            return;
        }

        try {
            var response = await postJson(lookupUrl, { rfid_uid: uid });
            setStudentState(response.data || {});
            billInput?.focus();
        } catch (error) {
            resetStudentState();
            showFailureAlert('lookup', error);
        }
    }

    async function lookupBySiswaId(siswaId) {
        if (!siswaId) return;

        try {
            var response = await postJson(lookupUrl, { siswa_id: siswaId });
            setStudentState(response.data || {});
            billInput?.focus();
            return true;
        } catch (error) {
            resetStudentState();
            showFailureAlert('lookup', error);
            return false;
        }
    }

    // Dipanggil oleh kantin-pos-face.js saat wajah siswa terdeteksi.
    window.kantinPosSetFaceStudent = function (siswaId) {
        if (activeMethod !== 'face') setMethod('face');
        return lookupBySiswaId(siswaId);
    };

    function scheduleLookup() {
        clearTimeout(lookupTimer);
        lookupTimer = setTimeout(lookupStudent, 250);
    }

    function resetForm() {
        if (form) form.reset();
        resetStudentState();
        if (activeMethod === 'rfid') {
            if (rfidInput) rfidInput.disabled = false;
            rfidInput?.focus();
        } else if (window.kantinPosFace && typeof window.kantinPosFace.onReset === 'function') {
            window.kantinPosFace.onReset();
        }
    }

    async function submitTransaction(event) {
        event.preventDefault();

        var isFace = activeMethod === 'face';
        var uid = isFace ? '' : (currentRfid || (rfidInput?.value || '').trim());
        var siswaId = isFace ? currentSiswaId : '';
        var amount = parseAmount(billInput?.value);
        var description = (descriptionInput?.value || '').trim();

        if (!uid && !siswaId) {
            window.showAlert?.({
                title: isFace ? 'Wajah belum terdeteksi' : 'RFID belum discan',
                message: isFace ? 'Siswa belum terdeteksi dari kamera.' : 'Kartu siswa belum terbaca.',
                detail: isFace
                    ? '<strong>Solusi:</strong> Deteksi wajah siswa terlebih dahulu sebelum menyimpan transaksi.'
                    : '<strong>Solusi:</strong> Scan kartu RFID siswa terlebih dahulu sebelum menyimpan transaksi.',
                variant: 'warning',
            });
            if (!isFace) rfidInput?.focus();
            return;
        }

        if (!amount || amount < 1) {
            window.showAlert?.({
                title: 'Total belanja kosong',
                message: 'Nominal belanja belum diisi atau tidak valid.',
                detail: '<strong>Solusi:</strong> Masukkan nominal total belanja yang valid (minimal Rp 1).',
                variant: 'warning',
            });
            billInput?.focus();
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        try {
            var payload = {
                amount: amount,
                description: description || null,
            };
            if (isFace) {
                payload.siswa_id = Number(siswaId);
            } else {
                payload.rfid_uid = uid;
            }

            var response = await postJson(chargeUrl, payload);

            window.showToast?.(response.message || 'Transaksi berhasil.', 'success');
            resetForm();
        } catch (error) {
            showFailureAlert('charge', error);
        } finally {
            if (submitBtn) submitBtn.disabled = !hasResolvedStudent();
        }
    }

    rfidInput?.addEventListener('input', function () {
        if (!(rfidInput.value || '').trim()) {
            resetStudentState();
            return;
        }

        scheduleLookup();
    });

    rfidInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(lookupTimer);
            lookupStudent();
        }
    });

    root.querySelectorAll('[data-kantin-method]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setMethod(btn.getAttribute('data-kantin-method'));
        });
    });

    form?.addEventListener('submit', submitTransaction);
    rfidInput?.focus();
})();
