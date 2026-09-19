(function () {
    var PROFILE_PHOTO_RULES = {
        acceptedTypes: ['image/jpeg', 'image/png', 'image/webp'],
        maxFileSize: '2MB',
        minWidth: 200,
        minHeight: 200,
        maxWidth: 2000,
        maxHeight: 2000,
    };

    var pluginsRegistered = false;

    window.syncProfilePhotoForForm = function (form, photoUrl) {
        if (!form) return;

        var input = form.querySelector('[data-profile-photo]');
        if (!input) return;

        if (photoUrl) {
            input.dataset.existingUrl = photoUrl;
        } else {
            delete input.dataset.existingUrl;
        }

        if (input._filepond) {
            input._filepond.removeFiles();
            if (photoUrl) {
                input._filepond.addFile(photoUrl, { type: 'local' }).catch(function () {});
            }
        }
    };

    window.initProfilePhotoUploads = function () {
        if (typeof FilePond === 'undefined') return;

        if (!pluginsRegistered) {
            FilePond.registerPlugin(
                FilePondPluginFileValidateType,
                FilePondPluginFileValidateSize,
                FilePondPluginImagePreview,
                FilePondPluginImageValidateSize
            );
            pluginsRegistered = true;
        }

        document.querySelectorAll('[data-profile-photo]').forEach(function (input) {
            if (input.dataset.filepondBound) return;
            input.dataset.filepondBound = '1';

            var existingUrl = input.dataset.existingUrl || '';
            var files = [];

            if (existingUrl) {
                files.push({
                    source: existingUrl,
                    options: { type: 'local' },
                });
            }

            var pond = FilePond.create(input, {
                credits: false,
                files: files,
                allowMultiple: false,
                storeAsFile: true,
                acceptedFileTypes: PROFILE_PHOTO_RULES.acceptedTypes,
                maxFileSize: PROFILE_PHOTO_RULES.maxFileSize,
                imageValidateSizeMinWidth: PROFILE_PHOTO_RULES.minWidth,
                imageValidateSizeMinHeight: PROFILE_PHOTO_RULES.minHeight,
                imageValidateSizeMaxWidth: PROFILE_PHOTO_RULES.maxWidth,
                imageValidateSizeMaxHeight: PROFILE_PHOTO_RULES.maxHeight,
                imagePreviewHeight: 140,
                stylePanelLayout: 'compact',
                labelIdle: 'Seret & lepas foto atau <span class="filepond--label-action">pilih file</span>',
                labelFileProcessing: 'Memuat',
                labelFileProcessingComplete: 'Siap diunggah',
                labelFileProcessingAborted: 'Dibatalkan',
                labelFileProcessingError: 'Gagal memuat',
                labelTapToCancel: 'ketuk untuk batal',
                labelTapToRetry: 'ketuk untuk coba lagi',
                labelTapToUndo: 'ketuk untuk urungkan',
                labelFileTypeNotAllowed: 'Format tidak didukung',
                fileValidateTypeLabelExpectedTypes: 'Gunakan JPEG, PNG, atau WebP',
                labelMaxFileSizeExceeded: 'Ukuran file terlalu besar',
                labelMaxFileSize: 'Maksimal {filesize}',
                imageValidateSizeLabelFormatError: 'Tipe gambar tidak didukung',
                imageValidateSizeLabelImageSizeTooSmall: 'Gambar terlalu kecil',
                imageValidateSizeLabelImageSizeTooBig: 'Gambar terlalu besar',
                imageValidateSizeLabelExpectedMinSize: 'Minimal {minWidth}×{minHeight} px',
                imageValidateSizeLabelExpectedMaxSize: 'Maksimal {maxWidth}×{maxHeight} px',
            });

            pond.on('error', function () {
                window.showToast?.('Periksa format dan ukuran foto profil.', 'error');
            });

            input._filepond = pond;
        });
    };

    document.addEventListener('click', function (e) {
        var editBtn = e.target.closest('[data-edit-record]');
        if (editBtn) {
            setTimeout(function () {
                var form = document.getElementById(editBtn.dataset.formTarget);
                var record = window.parseEditRecord
                    ? window.parseEditRecord(editBtn.dataset.editRecord)
                    : JSON.parse(editBtn.dataset.editRecord || '{}');
                if (!record) return;
                window.syncProfilePhotoForForm?.(form, record.photo_url || '');
            }, 80);
            return;
        }

        if (e.target.closest('[data-open-modal]')) {
            setTimeout(function () {
                window.initProfilePhotoUploads?.();
            }, 50);
        }
    });

    document.addEventListener('fetch-success', function () {
        document.querySelectorAll('[data-profile-photo]').forEach(function (input) {
            if (input._filepond) {
                input._filepond.removeFiles();
            }
            delete input.dataset.existingUrl;
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        window.initProfilePhotoUploads?.();
    });
})();
