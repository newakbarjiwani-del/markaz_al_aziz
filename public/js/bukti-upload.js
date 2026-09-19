(function () {
    var BUKTI_RULES = {
        imageTypes: ['image/jpeg', 'image/png', 'image/webp'],
        pdfTypes: ['application/pdf'],
        maxImageBytes: 1 * 1024 * 1024,
        maxPdfBytes: 2 * 1024 * 1024,
        maxFiles: 10,
    };

    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) return '0 B';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function isImage(file) {
        return BUKTI_RULES.imageTypes.indexOf(file.type) !== -1;
    }

    function isPdf(file) {
        return file.type === 'application/pdf';
    }

    function validateFile(file) {
        if (!file) return 'Pilih file.';
        if (isImage(file)) {
            if (file.size > BUKTI_RULES.maxImageBytes) return file.name + ' melebihi 1 MB.';
        } else if (isPdf(file)) {
            if (file.size > BUKTI_RULES.maxPdfBytes) return file.name + ' melebihi 2 MB.';
        } else {
            return file.name + ' format tidak didukung (hanya JPG/PNG/WebP/PDF).';
        }
        return null;
    }

    function syncZone(zone) {
        var input = zone.querySelector('input[type="file"]');
        var placeholder = zone.querySelector('.bukti-upload-zone__placeholder');
        var preview = zone.querySelector('.bukti-upload-zone__preview');
        var files = input.files;
        var hasFiles = files && files.length > 0;

        if (hasFiles) {
            placeholder.classList.add('hidden');
            preview.classList.remove('hidden');
            renderPreview(preview, files);
        } else {
            placeholder.classList.remove('hidden');
            preview.classList.add('hidden');
            preview.innerHTML = '';
        }
    }

    function renderPreview(container, files) {
        container.innerHTML = '';
        var grid = document.createElement('div');
        grid.className = 'grid grid-cols-3 gap-2 sm:grid-cols-4';

        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var card = document.createElement('div');
            card.className = 'relative group overflow-hidden rounded border border-slate-200 dark:border-slate-700';

            if (isImage(file)) {
                var img = document.createElement('img');
                img.className = 'h-24 w-full object-cover';
                img.src = URL.createObjectURL(file);
                card.appendChild(img);
            } else {
                var pdfIcon = document.createElement('div');
                pdfIcon.className = 'flex h-24 items-center justify-center bg-red-50 dark:bg-red-900/20';
                pdfIcon.innerHTML = '<i class="ti ti-file-pdf text-3xl text-red-400"></i>';
                card.appendChild(pdfIcon);
            }

            var info = document.createElement('div');
            info.className = 'px-2 py-1';
            info.innerHTML = '<p class="truncate text-xs text-slate-600 dark:text-slate-400" title="' + file.name.replace(/"/g, '&quot;') + '">' + file.name + '</p><p class="text-xs text-slate-400">' + formatBytes(file.size) + '</p>';
            card.appendChild(info);

            grid.appendChild(card);
        }

        container.appendChild(grid);
    }

    function createErrorToast(message) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'error');
        }
    }

    function initZone(zone) {
        if (zone.dataset.buktiInit) return;
        zone.dataset.buktiInit = '1';

        var input = zone.querySelector('input[type="file"]');
        var maxFiles = parseInt(zone.dataset.maxFiles, 10) || BUKTI_RULES.maxFiles;

        zone.addEventListener('click', function (e) {
            if (e.target.closest('.bukti-remove-btn')) return;
            if (e.target === input) return;
            input.click();
        });

        input.addEventListener('change', function () {
            var files = input.files;
            if (!files || files.length === 0) return;

            if (files.length > maxFiles) {
                createErrorToast('Maksimal ' + maxFiles + ' file.');
                input.value = '';
                return;
            }

            for (var i = 0; i < files.length; i++) {
                var error = validateFile(files[i]);
                if (error) {
                    createErrorToast(error);
                    input.value = '';
                    return;
                }
            }

            syncZone(zone);
        });

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/10');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/10');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/10');

            var droppedFiles = e.dataTransfer.files;
            if (!droppedFiles || droppedFiles.length === 0) return;

            if (droppedFiles.length > maxFiles) {
                createErrorToast('Maksimal ' + maxFiles + ' file.');
                return;
            }

            for (var i = 0; i < droppedFiles.length; i++) {
                var error = validateFile(droppedFiles[i]);
                if (error) {
                    createErrorToast(error);
                    return;
                }
            }

            input.files = droppedFiles;
            syncZone(zone);
        });
    }

    function showExistingBukti(modalId, buktiArray) {
        var container = document.getElementById(modalId + '-existing-bukti');
        if (!container) return;

        if (!buktiArray || buktiArray.length === 0) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        var list = container.querySelector('.bukti-existing-list');
        list.innerHTML = '';

        var grid = document.createElement('div');
        grid.className = 'bukti-existing-grid';

        buktiArray.forEach(function (b) {
            var card = document.createElement('div');
            card.className = 'bukti-existing-card';

            var thumb = document.createElement('a');
            thumb.href = b.url;
            thumb.target = '_blank';
            thumb.className = 'bukti-existing-card__thumb';

            if (b.is_image) {
                var img = document.createElement('img');
                img.src = b.url;
                img.alt = b.original_name || '';
                img.className = 'bukti-existing-card__img';
                thumb.appendChild(img);
            } else {
                thumb.innerHTML = '<i class="ti ti-file-pdf"></i>';
                thumb.classList.add('bukti-existing-card__thumb--pdf');
            }

            var info = document.createElement('div');
            info.className = 'bukti-existing-card__info';
            info.innerHTML = '<p class="bukti-existing-card__name" title="' + (b.original_name || '').replace(/"/g, '&quot;') + '">' + (b.original_name || 'File') + '</p>';

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'bukti-existing-card__remove';
            removeBtn.title = 'Hapus file';
            removeBtn.innerHTML = '<i class="ti ti-trash"></i>';
            removeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                card.remove();
                var hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'bukti_delete[]';
                hiddenInput.value = b.id;
                container.closest('form')?.appendChild(hiddenInput);

                if (grid.children.length === 0) {
                    container.classList.add('hidden');
                }
            });

            card.appendChild(thumb);
            card.appendChild(info);
            card.appendChild(removeBtn);
            grid.appendChild(card);
        });

        list.appendChild(grid);
    }

    window.showExistingBukti = showExistingBukti;

    function resetUploadZone(form) {
        var zone = form?.querySelector('[data-bukti-upload]');
        if (!zone) return;

        var input = zone.querySelector('input[type="file"]');
        if (input) input.value = '';

        var placeholder = zone.querySelector('.bukti-upload-zone__placeholder');
        var preview = zone.querySelector('.bukti-upload-zone__preview');
        if (placeholder) placeholder.classList.remove('hidden');
        if (preview) {
            preview.classList.add('hidden');
            preview.innerHTML = '';
        }

        var existingContainer = form.querySelector('[id$="-existing-bukti"]');
        if (existingContainer) {
            existingContainer.classList.add('hidden');
            var list = existingContainer.querySelector('.bukti-existing-list');
            if (list) list.innerHTML = '';
        }
    }

    window.resetBuktiUploadZone = resetUploadZone;

    function initAll() {
        document.querySelectorAll('[data-bukti-upload]').forEach(initZone);
    }

    document.addEventListener('DOMContentLoaded', initAll);

    document.addEventListener('modalReset', function (e) {
        var formId = e.detail?.formId;
        if (!formId) return;
        var form = document.getElementById(formId);
        if (form) resetUploadZone(form);
    });
})();
