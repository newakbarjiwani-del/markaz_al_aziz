(function () {
    'use strict';

    var LATE_MODAL_ID = 'perizinan-checkin-late-modal';
    var LATE_FORM_ID = 'perizinan-checkin-late-form';
    var MAIN_FORM_ID = 'perizinan-form';

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function previewUrlFromCheckin(checkinUrl) {
        return checkinUrl.replace(/\/checkin\/?$/, '/checkin/preview');
    }

    function previewUrlFromUpdate(updateUrl) {
        return updateUrl.replace(/\/?$/, '') + '/checkin/preview';
    }

    function reloadTable() {
        if (typeof window.reloadMainTable === 'function') {
            window.reloadMainTable();
        } else if (window.mainTable && typeof window.mainTable.ajax?.reload === 'function') {
            window.mainTable.ajax.reload(null, false);
        }
    }

    function formatDateTime(iso) {
        if (!iso) return '-';
        try {
            var d = new Date(iso);
            return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return iso;
        }
    }

    function openLateModal(preview, options) {
        options = options || {};
        var modal = document.getElementById(LATE_MODAL_ID);
        var form = document.getElementById(LATE_FORM_ID);
        if (!modal || !form) return;

        document.getElementById('perizinan-checkin-late-url').value = options.submitUrl || '';
        document.getElementById('perizinan-checkin-late-mode').value = options.mode || 'checkin';
        document.getElementById('perizinan-checkin-late-siswa').textContent = preview.siswa_name || '-';
        document.getElementById('perizinan-checkin-late-batas').textContent = formatDateTime(preview.tgl_sampai);
        document.getElementById('perizinan-checkin-late-detail').textContent =
            'Terlambat ± ' + (preview.minutes_late || 0) + ' menit dari batas waktu kembali. Isi pelanggaran siswa di bawah.';

        var defaults = preview.default_pelanggaran || {};
        var levelSelect = document.getElementById('perizinan-checkin-late-level');
        var jenisSelect = document.getElementById('perizinan-checkin-late-jenis');
        var judulInput = document.getElementById('perizinan-checkin-late-judul');
        var keteranganInput = document.getElementById('perizinan-checkin-late-keterangan');
        var pointInput = document.getElementById('perizinan-checkin-late-point');
        var tanggalInput = document.getElementById('perizinan-checkin-late-tanggal');

        if (typeof window.setPelanggaranJenisSelection === 'function' && defaults.jenis_pelanggaran_id) {
            window.setPelanggaranJenisSelection(form, defaults.jenis_pelanggaran_id);
        } else if (levelSelect && defaults.level) {
            levelSelect.value = defaults.level;
            if (window.jQuery) window.jQuery(levelSelect).trigger('change');
        }

        if (judulInput) judulInput.value = defaults.judul || '';
        if (keteranganInput) keteranganInput.value = defaults.keterangan || '';
        if (pointInput) pointInput.value = defaults.point != null ? String(defaults.point) : '';
        if (tanggalInput) {
            tanggalInput.value = new Date().toISOString().slice(0, 10);
        }

        form.dataset.pendingMainForm = options.pendingMainForm ? '1' : '0';
        modal.classList.remove('hidden');
    }

    function closeLateModal() {
        var modal = document.getElementById(LATE_MODAL_ID);
        if (modal) modal.classList.add('hidden');
    }

    function postCheckin(checkinUrl, payload, onSuccess) {
        $.ajax({
            url: checkinUrl,
            type: 'POST',
            data: Object.assign({ _token: csrfToken() }, payload),
            success: function (res) {
                if (res.success) {
                    if (typeof window.showToast === 'function') {
                        window.showToast(res.message || 'Berhasil dicatat.', 'success');
                    }
                    closeLateModal();
                    reloadTable();
                    if (typeof onSuccess === 'function') onSuccess(res);
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Gagal mencatat kembali.';
                var errors = xhr.responseJSON?.errors;
                if (errors && typeof window.showAlert === 'function') {
                    var lines = Object.values(errors).flat().join('\n');
                    window.showAlert({ title: 'Validasi gagal', message: lines || msg, tone: 'danger' });
                } else if (typeof window.showAlert === 'function') {
                    window.showAlert({ title: 'Gagal', message: msg, tone: 'danger' });
                }
            }
        });
    }

    function fetchPreview(previewUrl, returnAt) {
        return $.getJSON(previewUrl, returnAt ? { return_at: returnAt } : {});
    }

    function pelanggaranPayloadFromLateForm(form) {
        return {
            pelanggaran_jenis_pelanggaran_id: form.querySelector('[name="pelanggaran_jenis_pelanggaran_id"]')?.value,
            pelanggaran_judul: form.querySelector('[name="pelanggaran_judul"]')?.value,
            pelanggaran_keterangan: form.querySelector('[name="pelanggaran_keterangan"]')?.value,
            pelanggaran_point: form.querySelector('[name="pelanggaran_point"]')?.value,
            pelanggaran_tanggal: form.querySelector('[name="pelanggaran_tanggal"]')?.value,
        };
    }

    function appendPelanggaranToForm(targetForm, payload) {
        Object.keys(payload).forEach(function (key) {
            var existing = targetForm.querySelector('input[name="' + key + '"]');
            if (existing) existing.remove();
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = payload[key] || '';
            targetForm.appendChild(input);
        });
    }

    function isReturnStatus(status) {
        return status === 'kembali' || status === 'terlambat';
    }

    // Populate edit record modal
    document.addEventListener('edit-record-populated', function (e) {
        var record = e.detail?.record;
        var form = e.detail?.form;
        if (!record || !form) return;

        var inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function (input) {
            var name = input.getAttribute('name');
            if (name && name !== 'file' && record[name] !== undefined) {
                input.value = record[name];
            }
        });

        var fileInput = form.querySelector('input[type="file"]');
        if (fileInput) fileInput.value = '';

        var fileContainer = document.getElementById('file-current-container');
        var fileLink = document.getElementById('file-current-link');
        if (fileContainer && fileLink) {
            if (record.file_url && record.file_name) {
                fileLink.href = record.file_url;
                fileLink.textContent = record.file_name;
                fileContainer.classList.remove('hidden');
            } else {
                fileContainer.classList.add('hidden');
                fileLink.href = '#';
                fileLink.textContent = '';
            }
        }

        if (record.siswa_id && record.siswa_name) {
            var $siswaSelect = $(form).find('[name="siswa_id"]');
            if ($siswaSelect.length) {
                var option = new Option(record.siswa_name, record.siswa_id, true, true);
                $siswaSelect.append(option).trigger('change');
            }
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-open-modal="perizinan-modal"]');
        if (!btn) return;
        var fileContainer = document.getElementById('file-current-container');
        if (fileContainer) fileContainer.classList.add('hidden');
        var fileInput = document.getElementById('perizinan-file');
        if (fileInput) fileInput.value = '';
        var pemberiInput = document.getElementById('pemberi_izin');
        if (pemberiInput) {
            var defaultName = pemberiInput.dataset.defaultPemberiIzin || '';
            pemberiInput.value = defaultName;
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-view-url]');
        if (!btn) return;

        var modalId = btn.dataset.viewModal;
        if (modalId !== 'perizinan-detail-modal') return;

        e.preventDefault();

        $.getJSON(btn.dataset.viewUrl, function (res) {
            if (!res.success) return;
            var d = res.data;
            var modal = $('#' + modalId);

            modal.find('.detail-siswa-name').text(d.siswa_name || '-');
            modal.find('.detail-siswa-sub').text('NIS ' + (d.siswa_nis || '-') + ' · ' + (d.siswa_kelas || '-') + ' (' + (d.sekolah_name || '-') + ')');
            modal.find('.detail-status-badge').html(d.status_badge || '-');
            modal.find('.detail-jenis').text(d.jenis_label || '-');
            modal.find('.detail-pj').text(d.penanggung_jawab || '-');
            modal.find('.detail-tgl-mulai').text(d.tgl_mulai || '-');
            modal.find('.detail-tgl-sampai').text(d.tgl_sampai || '-');
            modal.find('.detail-tgl-kembali-aktual').text(d.tgl_kembali_aktual || '-');
            modal.find('.detail-alasan').text(d.alasan || '-');
            modal.find('.detail-catatan').text(d.catatan || '-');
            modal.find('.detail-approved-by').text(d.approved_by_name || '-');
            modal.find('.detail-created-by').text(d.created_by_name || '-');

            var fileContent = modal.find('#detail-file-content');
            if (d.file_url && d.file_name) {
                fileContent.html(
                    '<a href="' + d.file_url + '" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 font-medium text-primary-600 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-primary-400 dark:hover:bg-slate-700/50">' +
                    '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>' +
                    '<span>Lihat / Download File: <strong>' + d.file_name + '</strong></span></a>'
                );
            } else {
                fileContent.html('<span class="text-xs italic text-slate-400">Tidak ada file lampiran</span>');
            }

            modal.removeClass('hidden');
        });
    });

    // Quick check-in return
    document.addEventListener('click', async function (e) {
        var btn = e.target.closest('[data-action="checkin-perizinan"]');
        if (!btn) return;
        e.preventDefault();

        var checkinUrl = btn.dataset.url;
        var siswaName = btn.dataset.siswaName || 'Santri';
        var previewUrl = previewUrlFromCheckin(checkinUrl);

        try {
            var previewRes = await fetchPreview(previewUrl);
            if (!previewRes.success) return;

            var preview = previewRes.data;
            if (preview.is_late) {
                openLateModal(preview, { submitUrl: checkinUrl, mode: 'checkin' });
                return;
            }

            var confirmed = false;
            if (typeof window.showConfirm === 'function') {
                confirmed = await window.showConfirm({
                    title: 'Konfirmasi Santri Kembali',
                    message: 'Apakah Anda yakin santri/siswa ' + siswaName + ' sudah kembali ke pondok/sekolah?',
                    tone: 'primary',
                    confirmText: 'Ya, Catat Kembali',
                });
            } else {
                confirmed = confirm('Apakah Anda yakin santri/siswa ' + siswaName + ' sudah kembali?');
            }

            if (!confirmed) return;
            postCheckin(checkinUrl, {});
        } catch (err) {
            if (typeof window.showAlert === 'function') {
                window.showAlert({ title: 'Gagal', message: 'Tidak dapat memeriksa status keterlambatan.', tone: 'danger' });
            }
        }
    });

    // Late modal submit (checkin or main form update)
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.id !== LATE_FORM_ID) return;
        e.preventDefault();

        var mode = document.getElementById('perizinan-checkin-late-mode')?.value || 'checkin';
        var payload = pelanggaranPayloadFromLateForm(form);

        if (mode === 'update') {
            var mainForm = document.getElementById(MAIN_FORM_ID);
            if (!mainForm) return;
            appendPelanggaranToForm(mainForm, payload);
            mainForm.dataset.latePelanggaranHandled = '1';
            closeLateModal();
            mainForm.requestSubmit();
            return;
        }

        var checkinUrl = document.getElementById('perizinan-checkin-late-url')?.value;
        if (!checkinUrl) return;
        postCheckin(checkinUrl, payload);
    });

    // Intercept main perizinan form when marking return late via edit
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.id !== MAIN_FORM_ID || form.dataset.latePelanggaranHandled === '1') {
            if (form.id === MAIN_FORM_ID) {
                delete form.dataset.latePelanggaranHandled;
            }
            return;
        }

        var methodInput = form.querySelector('[name="_method"]');
        var isUpdate = methodInput && methodInput.value && methodInput.value.toUpperCase() !== 'POST';
        if (!isUpdate) return;

        var status = form.querySelector('[name="status"]')?.value;
        if (!isReturnStatus(status)) return;

        var tglKembali = form.querySelector('[name="tgl_kembali_aktual"]')?.value;
        var tglSampai = form.querySelector('[name="tgl_sampai"]')?.value;
        if (!tglSampai) return;

        var returnAt = tglKembali || new Date().toISOString().slice(0, 16);
        if (new Date(returnAt) <= new Date(tglSampai)) return;

        e.preventDefault();
        e.stopPropagation();

        var updateUrl = form.getAttribute('action');
        var previewUrl = previewUrlFromUpdate(updateUrl);

        fetchPreview(previewUrl, returnAt.replace('T', ' ') + ':00').then(function (previewRes) {
            if (!previewRes.success) {
                form.dataset.latePelanggaranHandled = '1';
                form.requestSubmit();
                return;
            }
            if (previewRes.data.is_late) {
                openLateModal(previewRes.data, { submitUrl: updateUrl, mode: 'update' });
            } else {
                form.dataset.latePelanggaranHandled = '1';
                form.requestSubmit();
            }
        });
    }, true);

})();
