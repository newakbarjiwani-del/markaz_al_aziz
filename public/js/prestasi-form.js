/**
 * Prestasi forms: selecting a catalog jenis auto-fills Judul + Point.
 * Point stays editable for prestasi (unlike pelanggaran).
 */
(function () {
    function applyCatalogToForm($select, data) {
        var $form = $select.closest('form');
        if (!$form.length) return;

        if (data && data.nama) {
            var $judul = $form.find('[name="judul"]');
            if ($judul.length) {
                $judul.val(data.nama);
            }
        }

        if (data && data.point !== undefined && data.point !== null && data.point !== '') {
            var $point = $form.find('[name="point"]');
            if ($point.length) {
                $point.val(data.point);
            }
        }
    }

    function bindForm(form) {
        if (!form || form.dataset.prestasiFormBound) return;
        form.dataset.prestasiFormBound = '1';

        var $jenis = window.jQuery ? window.jQuery(form).find('[data-prestasi-jenis], select[name="jenis_prestasi_id"]') : null;
        if (!$jenis || !$jenis.length) return;

        $jenis.on('select2:select', function (e) {
            var data = e.params && e.params.data ? e.params.data : null;
            if (!data) return;

            var el = e.params.data.element;
            if (el) {
                data = {
                    nama: el.getAttribute('data-nama') || data.nama || data.text,
                    point: el.getAttribute('data-point') != null ? el.getAttribute('data-point') : data.point,
                };
            }

            applyCatalogToForm($jenis, data);
        });
    }

    function initAll() {
        document.querySelectorAll('form[id*="prestasi"]').forEach(bindForm);
    }

    document.addEventListener('DOMContentLoaded', initAll);
    document.addEventListener('modalReset', function () {
        setTimeout(initAll, 0);
    });

    window.setPrestasiJenisSelection = function (form, jenisId) {
        if (!form) return;
        var jenis = form.querySelector('[data-prestasi-jenis], select[name="jenis_prestasi_id"]');
        if (!jenis) return;
        jenis.value = jenisId ? String(jenisId) : '';
        if (window.jQuery) {
            window.jQuery(jenis).trigger('change');
        }
    };
})();
