/**
 * Pelanggaran forms (admin + portal guru):
 * 1) Level filter (Ringan / Sedang / Berat) narrows the jenis catalog list.
 * 2) Selecting a jenis auto-fills Judul + Point (judul editable; point readonly).
 *
 * `select2:select` on jenis only fires on real user interaction, so edit
 * restore (which sets level + jenis programmatically) never overwrites
 * saved judul/point.
 */
(function () {
    var katalogCache = {};

    function catalogForForm(form) {
        if (!form || !form.id) return [];
        if (katalogCache[form.id]) return katalogCache[form.id];

        var node = document.querySelector('[data-pelanggaran-katalog-for="' + form.id + '"]');
        if (!node) {
            katalogCache[form.id] = [];
            return katalogCache[form.id];
        }

        try {
            katalogCache[form.id] = JSON.parse(node.textContent || '[]') || [];
        } catch (e) {
            katalogCache[form.id] = [];
        }

        return katalogCache[form.id];
    }

    function hasSelect2(el) {
        return !!(window.jQuery && el && el.classList.contains('select2-hidden-accessible'));
    }

    function setSelectValue(el, value) {
        if (!el) return;
        el.value = value == null ? '' : String(value);
        if (window.jQuery) {
            window.jQuery(el).trigger('change');
        }
    }

    function rebuildJenisOptions(form, level, selectedId) {
        var jenis = form.querySelector('[data-pelanggaran-jenis], select[name="jenis_pelanggaran_id"], select[name="pelanggaran_jenis_pelanggaran_id"]');
        if (!jenis) return;

        var catalog = catalogForForm(form);
        var rows = level
            ? catalog.filter(function (row) { return row.level === level; })
            : [];

        var wasS2 = hasSelect2(jenis);
        if (wasS2) {
            window.jQuery(jenis).select2('destroy');
        }

        jenis.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = level ? 'Pilih jenis pelanggaran' : 'Pilih tingkat dulu';
        jenis.appendChild(placeholder);

        rows.forEach(function (row) {
            var opt = document.createElement('option');
            opt.value = String(row.id);
            opt.textContent = row.label || row.nama;
            opt.setAttribute('data-nama', row.nama || '');
            opt.setAttribute('data-point', row.point != null ? String(row.point) : '');
            opt.setAttribute('data-level', row.level || '');
            jenis.appendChild(opt);
        });

        jenis.disabled = false;
        jenis.dataset.placeholder = level ? 'Pilih jenis pelanggaran' : 'Pilih tingkat dulu';

        var keepId = selectedId != null && selectedId !== '' ? String(selectedId) : '';
        if (keepId && rows.some(function (row) { return String(row.id) === keepId; })) {
            jenis.value = keepId;
        } else {
            jenis.value = '';
        }

        if (wasS2 || jenis.hasAttribute('data-s2')) {
            window.initOfflineSelect2s?.(form);
        } else if (window.jQuery) {
            window.jQuery(jenis).trigger('change');
        }
    }

    function applyCatalogToForm($select, data) {
        var $form = $select.closest('form');
        if (!$form.length) return;

        if (data && data.nama !== undefined && data.nama !== null && data.nama !== '') {
            var $judul = $form.find('[name="judul"], [name="pelanggaran_judul"]');
            if ($judul.length) {
                $judul.val(data.nama);
            }
        }

        if (data && data.point !== undefined && data.point !== null) {
            var $point = $form.find('[name="point"], [name="pelanggaran_point"]');
            if ($point.length) {
                $point.val(data.point);
            }
        }
    }

    /**
     * Set level + jenis for edit flows (admin DataTable / portal guru).
     * Does not overwrite judul/point.
     */
    window.setPelanggaranJenisSelection = function (form, jenisId) {
        if (!form) return;
        var levelEl = form.querySelector('[data-pelanggaran-level]');
        var id = jenisId != null && jenisId !== '' ? String(jenisId) : '';
        if (!id) {
            form.dataset.pelanggaranSyncing = '1';
            if (levelEl) setSelectValue(levelEl, '');
            rebuildJenisOptions(form, '', '');
            delete form.dataset.pelanggaranSyncing;
            return;
        }

        var row = catalogForForm(form).find(function (item) { return String(item.id) === id; });
        var level = row ? row.level : '';
        form.dataset.pelanggaranSyncing = '1';
        if (levelEl) setSelectValue(levelEl, level || '');
        rebuildJenisOptions(form, level || '', id);
        delete form.dataset.pelanggaranSyncing;
    };

    $(document).on('change', '[data-pelanggaran-level]', function () {
        var form = this.closest('form');
        if (!form || form.dataset.pelanggaranSyncing === '1') return;
        rebuildJenisOptions(form, this.value || '', '');
    });

    $(document).on('select2:select', 'select[name="jenis_pelanggaran_id"], select[name="pelanggaran_jenis_pelanggaran_id"]', function (e) {
        var data = e.params && e.params.data ? e.params.data : {};
        // Offline Select2 (`data-s2`): the option's data-* attributes live on
        // e.params.data.element (the DOM <option>), not on the result object.
        var el = data.element;
        var nama = (el && el.getAttribute) ? el.getAttribute('data-nama') : undefined;
        var rawPoint = (el && el.getAttribute) ? el.getAttribute('data-point') : undefined;
        var point = rawPoint !== null && rawPoint !== '' ? Number(rawPoint) : undefined;

        applyCatalogToForm($(this), {
            nama: nama !== undefined && nama !== null && nama !== '' ? nama : data.nama,
            point: point !== undefined ? point : data.point,
        });
    });

    document.addEventListener('edit-record-populated', function (e) {
        var form = e.detail && e.detail.form;
        var record = e.detail && e.detail.record;
        if (!form || !form.querySelector('[data-pelanggaran-level]')) return;

        var jenisId = (record && record.jenis_pelanggaran_id != null)
            ? record.jenis_pelanggaran_id
            : (form.querySelector('[name="jenis_pelanggaran_id"], [name="pelanggaran_jenis_pelanggaran_id"]') || {}).value;

        window.setPelanggaranJenisSelection(form, jenisId);
    });

    document.addEventListener('modalReset', function (e) {
        var formId = e.detail && e.detail.formId;
        if (!formId) return;
        var form = document.getElementById(formId);
        if (!form || !form.querySelector('[data-pelanggaran-level]')) return;

        window.setPelanggaranJenisSelection(form, '');
    });
})();
