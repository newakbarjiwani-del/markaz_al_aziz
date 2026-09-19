(function () {
    function resolveUrl(template, siswaId) {
        if (!template) return '';
        return template.indexOf('__SISWA__') >= 0
            ? template.replace('__SISWA__', siswaId || '')
            : template;
    }

    window.initFaceCapture = function (config) {
        config = config || {};
        var modalId = config.modalId || 'face-capture-modal';
        var modal = document.getElementById(modalId);
        var stageEmpty = document.getElementById('face-stage-empty');
        var stageLabel = document.getElementById('face-stage-label');
        var video = document.getElementById('face-capture-video');
        var cameraWrap = document.getElementById('face-camera-wrap');
        var canvas = document.getElementById('face-capture-canvas');
        var preview = document.getElementById('face-capture-preview');
        var existingPhoto = document.getElementById('face-existing-photo');
        var statusBadge = document.getElementById('face-modal-status');
        var siswaName = document.getElementById('face-student-name');
        var siswaNis = document.getElementById('face-student-nis');
        var btnStart = document.getElementById('face-start-camera');
        var btnCapture = document.getElementById('face-capture-shot');
        var btnRetake = document.getElementById('face-retake-shot');
        var btnSave = document.getElementById('face-save-shot');
        var btnDelete = document.getElementById('face-delete-shot');
        var cameraModeSelect = document.getElementById('face-capture-camera-mode');
        var activeStream = null;
        var capturedDataUrl = '';
        var activeSiswaId = '';
        var hasExistingPhoto = false;

        var stageLabels = {
            empty: 'Belum ada foto wajah',
            existing: 'Foto tersimpan',
            camera: 'Kamera aktif',
            preview: 'Preview hasil rekam',
        };

        function syncStudentHasFoto(hasFoto) {
            hasExistingPhoto = !!hasFoto;
            if (config.student) {
                config.student.hasFoto = hasExistingPhoto;
            }
        }

        function markNoExistingPhoto() {
            syncStudentHasFoto(false);
            if (btnDelete) btnDelete.disabled = true;
            setStatusBadge(false);
            clearExistingPhoto();
            setStage('empty');
        }

        function closeStream() {
            if (!activeStream) return;
            activeStream.getTracks().forEach(function (track) { track.stop(); });
            activeStream = null;
            if (btnCapture) btnCapture.disabled = true;
        }

        function setStatusBadge(hasFoto) {
            if (!statusBadge) return;
            statusBadge.textContent = hasFoto ? 'Sudah' : 'Belum';
            statusBadge.className = 'face-status-badge ' + (hasFoto ? 'face-status-badge--done' : 'face-status-badge--pending');
        }

        function setStage(mode) {
            stageEmpty?.classList.toggle('hidden', mode !== 'empty');
            existingPhoto?.classList.toggle('hidden', mode !== 'existing');
            cameraWrap?.classList.toggle('hidden', mode !== 'camera');
            preview?.classList.toggle('hidden', mode !== 'preview');
            if (stageLabel) stageLabel.textContent = stageLabels[mode] || '';
            btnRetake?.classList.toggle('hidden', mode !== 'preview');
            btnCapture?.classList.toggle('hidden', mode === 'preview');
        }

        function resetCaptureState() {
            capturedDataUrl = '';
            if (btnCapture) btnCapture.disabled = !activeStream;
            if (btnSave) btnSave.disabled = true;
            preview?.removeAttribute('src');
        }

        function loadExistingPhoto(siswaId, onReady) {
            if (!existingPhoto) {
                onReady?.(false);
                return;
            }

            var url = resolveUrl(config.fotoUrl, siswaId);
            url = url + (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + Date.now();

            existingPhoto.onload = function () {
                onReady?.(true);
            };
            existingPhoto.onerror = function () {
                onReady?.(false);
            };
            existingPhoto.src = url;
        }

        function clearExistingPhoto() {
            existingPhoto?.removeAttribute('src');
        }

        function syncProfilePhoto(hasFoto) {
            if (!config.profilePhotoSelector) return;
            var img = document.querySelector(config.profilePhotoSelector);
            var placeholder = config.profilePlaceholderSelector
                ? document.querySelector(config.profilePlaceholderSelector)
                : null;
            var statusEl = config.profileStatusSelector
                ? document.querySelector(config.profileStatusSelector)
                : null;

            if (!img) return;

            if (hasFoto) {
                img.src = resolveUrl(config.fotoUrl, activeSiswaId) + '?v=' + Date.now();
                img.classList.remove('hidden');
                placeholder?.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                img.removeAttribute('src');
                placeholder?.classList.remove('hidden');
            }

            if (statusEl) {
                statusEl.className = 'face-status-badge ' + (hasFoto ? 'face-status-badge--done' : 'face-status-badge--pending');
                var statusText = statusEl.querySelector('[data-face-status-text]');
                if (statusText) {
                    statusText.textContent = hasFoto ? 'Sudah direkam' : 'Belum direkam';
                }
            }
        }

        function fotoSizeApprox(dataUrl) {
            var base64 = (dataUrl || '').split(',')[1] || '';
            return Math.floor((base64.length * 3) / 4);
        }

        function getFacingMode() {
            return cameraModeSelect && cameraModeSelect.value === 'environment' ? 'environment' : 'user';
        }

        function applyVideoMirror() {
            if (!video) return;
            video.style.transform = getFacingMode() === 'user' ? 'scaleX(-1)' : 'none';
        }

        function setCameraModeDisabled(disabled) {
            if (cameraModeSelect) cameraModeSelect.disabled = !!disabled;
        }

        async function openCameraStream() {
            if (!video) return;
            closeStream();
            activeStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: getFacingMode() },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });
            video.srcObject = activeStream;
            applyVideoMirror();
            await video.play();
            if (btnCapture) btnCapture.disabled = false;
            setStage('camera');
        }

        async function switchCameraMode() {
            if (!activeStream) return;

            var previousFacing = getFacingMode() === 'environment' ? 'user' : 'environment';
            setCameraModeDisabled(true);
            try {
                await openCameraStream();
            } catch (error) {
                if (cameraModeSelect) cameraModeSelect.value = previousFacing;
                try {
                    await openCameraStream();
                } catch (retryError) {
                    closeStream();
                    setStage(hasExistingPhoto ? 'existing' : 'empty');
                    window.showToast?.('Gagal mengganti kamera. Mulai ulang kamera dan coba lagi.', 'error');
                    return;
                }
                window.showToast?.('Gagal mengganti kamera. Kembali ke mode ' + (previousFacing === 'environment' ? 'belakang' : 'depan') + '.', 'warning');
            } finally {
                setCameraModeDisabled(false);
            }
        }

        async function startCamera() {
            await openCameraStream();
        }

        async function postFaceData(method, url, payload) {
            var response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json',
                },
                body: payload ? JSON.stringify(payload) : null,
            });

            var raw = await response.text();
            var data = null;
            try {
                data = raw ? JSON.parse(raw) : null;
            } catch (e) {
                throw new Error('Server mengembalikan respons tidak valid. Coba muat ulang halaman dan login ulang.');
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal memproses data rekam wajah.');
            }
            return data;
        }

        function openModalFromStudent(student) {
            activeSiswaId = String(student.id || student.siswaId || '');
            syncStudentHasFoto(student.hasFoto === true || student.hasFoto === '1' || student.hasFoto === 1);
            if (siswaName) siswaName.textContent = student.name || '-';
            if (siswaNis) siswaNis.textContent = student.nis || '-';
            if (btnDelete) btnDelete.disabled = !hasExistingPhoto;
            setStatusBadge(hasExistingPhoto);
            closeStream();
            resetCaptureState();
            clearExistingPhoto();

            if (hasExistingPhoto) {
                setStage('empty');
                loadExistingPhoto(activeSiswaId, function (loaded) {
                    if (loaded) {
                        setStage('existing');
                        return;
                    }
                    markNoExistingPhoto();
                });
            } else {
                setStage('empty');
            }

            modal?.classList.remove('hidden');
        }

        function openModalFromTrigger(trigger) {
            openModalFromStudent({
                id: trigger.dataset.siswaId,
                name: trigger.dataset.siswaName,
                nis: trigger.dataset.siswaNis,
                hasFoto: trigger.dataset.hasFoto === '1',
            });
        }

        function closeModal() {
            closeStream();
            resetCaptureState();
            clearExistingPhoto();
            setStage('empty');
            activeSiswaId = '';
            hasExistingPhoto = false;
        }

        if (config.openTriggerSelector) {
            document.addEventListener('click', function (event) {
                var trigger = event.target.closest(config.openTriggerSelector);
                if (!trigger) return;
                openModalFromTrigger(trigger);
            });
        }

        if (config.portalOpenSelector) {
            document.querySelectorAll(config.portalOpenSelector).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openModalFromStudent(config.student || {});
                });
            });
        }

        btnStart?.addEventListener('click', function () {
            setCameraModeDisabled(true);
            startCamera()
                .catch(function () {
                    window.showToast?.('Gagal mengakses kamera. Pastikan izin kamera di browser sudah diaktifkan.', 'error');
                })
                .finally(function () {
                    setCameraModeDisabled(false);
                });
        });

        cameraModeSelect?.addEventListener('change', function () {
            if (!activeStream) return;
            switchCameraMode().catch(function () {
                window.showToast?.('Gagal mengganti kamera.', 'error');
            });
        });

        btnCapture?.addEventListener('click', function () {
            if (!video || !canvas || !activeStream) return;
            var width = video.videoWidth || 0;
            var height = video.videoHeight || 0;
            if (!width || !height) return;

            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(video, 0, 0, width, height);

            capturedDataUrl = canvas.toDataURL('image/jpeg', 0.88);
            if (fotoSizeApprox(capturedDataUrl) > (750 * 1024)) {
                capturedDataUrl = '';
                window.showToast?.('Foto terlalu besar. Coba ambil ulang dengan jarak kamera lebih dekat.', 'error');
                return;
            }

            preview.src = capturedDataUrl;
            if (btnSave) btnSave.disabled = false;
            setStage('preview');
        });

        btnRetake?.addEventListener('click', function () {
            resetCaptureState();
            if (activeStream) {
                setStage('camera');
                return;
            }
            setStage(hasExistingPhoto ? 'existing' : 'empty');
        });

        btnSave?.addEventListener('click', function () {
            if (!capturedDataUrl) return;
            if (btnSave) btnSave.disabled = true;

            var storeUrl = resolveUrl(config.storeUrl, activeSiswaId);
            postFaceData('POST', storeUrl, { foto_wajah: capturedDataUrl })
                .then(function (result) {
                    window.showToast?.(result.message || 'Foto wajah berhasil disimpan.', 'success');
                    syncStudentHasFoto(true);
                    if (btnDelete) btnDelete.disabled = false;
                    setStatusBadge(true);
                    closeStream();
                    resetCaptureState();
                    loadExistingPhoto(activeSiswaId, function (loaded) {
                        setStage(loaded ? 'existing' : 'empty');
                    });
                    syncProfilePhoto(true);
                    if (typeof config.onSaved === 'function') config.onSaved();
                })
                .catch(function (err) {
                    window.showToast?.(err.message, 'error');
                    if (btnSave) btnSave.disabled = false;
                });
        });

        btnDelete?.addEventListener('click', function () {
            var runDelete = function () {
                var deleteUrl = resolveUrl(config.deleteUrl, activeSiswaId);
                postFaceData('DELETE', deleteUrl)
                    .then(function (result) {
                        window.showToast?.(result.message || 'Foto wajah berhasil dihapus.', 'success');
                        markNoExistingPhoto();
                        closeStream();
                        resetCaptureState();
                        syncProfilePhoto(false);
                        if (typeof config.onDeleted === 'function') config.onDeleted();
                    })
                    .catch(function (err) {
                        window.showToast?.(err.message, 'error');
                    });
            };

            if (window.showConfirm) {
                window.showConfirm({
                    title: 'Hapus Foto Wajah',
                    message: 'Foto wajah absensi akan dihapus dari database.',
                    detail: siswaName?.textContent
                        ? [{ label: 'Siswa', value: siswaName.textContent }]
                        : '',
                    tone: 'danger',
                    confirmText: 'Ya, Hapus',
                    confirmIcon: 'ti-camera-off',
                    headerIcon: 'ti-camera-off',
                    footnote: 'Rekam wajah dapat dilakukan lagi kapan saja.',
                }).then(function (ok) {
                    if (ok) runDelete();
                });
                return;
            }

            runDelete();
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-modal-close="' + modalId + '"]')) return;
            closeModal();
        });
    };
})();
