(function () {
    var config = window.portalRekapPengunjungConfig || {};
    var checkinMethod = 'manual';
    var visitorType = 'siswa';
    var capturedPhotos = {};
    var rfidResolved = null;

    var todayCountEl = document.getElementById('today-visitor-count');
    var siswaFaceState = createFaceState();
    var pendingVerify = false;
    var verifyingStudentIds = new Set();

    var facePrefixes = ['guru', 'karyawan', 'non'];
    var faceStates = {};

    function createFaceState() {
        return {
            stream: null,
            detectActive: false,
            rafId: null,
            lastDetectAt: 0,
            matcher: null,
            references: [],
            cooldown: new Map(),
            modelsReady: false,
            matcherReady: false,
            intervalMs: 480,
            threshold: 0.5,
        };
    }

    function getFacingMode(selectEl) {
        return selectEl?.value === 'environment' ? 'environment' : 'user';
    }

    function applyVideoMirror(video, selectEl) {
        if (!video) return;
        video.style.transform = getFacingMode(selectEl) === 'user' ? 'scaleX(-1)' : 'none';
    }

    function captureVideoFrame(video, selectEl) {
        if (!video || !video.videoWidth) return null;
        var canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var ctx = canvas.getContext('2d');
        if (getFacingMode(selectEl) === 'user') {
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
        }
        ctx.drawImage(video, 0, 0);
        return canvas.toDataURL('image/jpeg', 0.85);
    }

    function stopFaceSession(state, prefix) {
        state.detectActive = false;
        if (state.rafId) cancelAnimationFrame(state.rafId);
        if (state.stream) {
            state.stream.getTracks().forEach(function (t) { t.stop(); });
            state.stream = null;
        }
        var video = document.getElementById(prefix + '-face-video');
        var overlay = document.getElementById(prefix + '-face-overlay');
        if (video) video.srcObject = null;
        if (overlay) overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);
        var toggleBtn = document.getElementById(prefix + '-face-toggle');
        if (toggleBtn) {
            toggleBtn.classList.remove('btn-secondary');
            toggleBtn.classList.add('btn-primary');
            toggleBtn.innerHTML = '<i class="ti ti-camera text-base leading-none mr-1"></i>Mulai Kamera';
        }
        var captureBtn = document.getElementById(prefix + '-face-capture');
        captureBtn?.setAttribute('disabled', 'disabled');
    }

    function setMethod(method) {
        checkinMethod = method;
        document.querySelectorAll('.method-btn').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.dataset.method === method);
        });

        document.getElementById('panel-manual')?.classList.toggle('hidden', method !== 'manual');
        document.getElementById('panel-face')?.classList.toggle('hidden', method !== 'face');
        document.getElementById('panel-rfid')?.classList.toggle('hidden', method !== 'rfid');
        document.getElementById('type-switcher-wrap')?.classList.toggle('hidden', method === 'rfid');

        if (method !== 'face') stopFaceSession(siswaFaceState, 'siswa');
        facePrefixes.forEach(function (prefix) {
            if (method !== 'face') stopFaceSession(faceStates[prefix] || createFaceState(), prefix);
        });
        if (method === 'rfid') {
            document.getElementById('rfid-input')?.focus();
        } else {
            updateManualPanels();
            updateFacePanels();
        }
    }

    function setVisitorType(type) {
        visitorType = type;
        document.querySelectorAll('.visitor-type-btn').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.dataset.type === type);
        });
        updateManualPanels();
        updateFacePanels();
    }

    function updateManualPanels() {
        ['siswa', 'guru', 'karyawan', 'non_siswa'].forEach(function (type) {
            var id = type === 'non_siswa' ? 'manual-non-siswa' : 'manual-' + type;
            document.getElementById(id)?.classList.toggle('hidden', !(checkinMethod === 'manual' && visitorType === type));
        });
    }

    function updateFacePanels() {
        document.getElementById('face-siswa')?.classList.toggle('hidden', !(checkinMethod === 'face' && visitorType === 'siswa'));
        document.getElementById('face-guru')?.classList.toggle('hidden', !(checkinMethod === 'face' && visitorType === 'guru'));
        document.getElementById('face-karyawan')?.classList.toggle('hidden', !(checkinMethod === 'face' && visitorType === 'karyawan'));
        document.getElementById('face-non-siswa')?.classList.toggle('hidden', !(checkinMethod === 'face' && visitorType === 'non_siswa'));
    }

    function readFreeform(prefix) {
        return {
            nama: (document.getElementById(prefix + '-nama')?.value || '').trim(),
            asal: document.getElementById(prefix + '-asal')?.value || null,
            telepon: document.getElementById(prefix + '-telepon')?.value || null,
            catatan: document.getElementById(prefix + '-catatan')?.value || null,
        };
    }

    function clearFreeform(prefix) {
        [prefix + '-nama', prefix + '-asal', prefix + '-telepon', prefix + '-catatan'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
    }

    async function submitVisit(payload) {
        var res = await fetch(config.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken || '',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });
        return res.json();
    }

    function handleVisitResult(result) {
        if (!result.success) {
            window.showToast?.(result.message || 'Gagal mencatat kunjungan.', 'error');
            return;
        }
        window.showToast?.(result.message || 'Kunjungan berhasil dicatat.', 'success');
        if (todayCountEl) {
            todayCountEl.textContent = String(parseInt(todayCountEl.textContent || '0', 10) + 1);
        }
        window.reloadMainTable?.();
    }

    async function submitManualSiswa() {
        var id = document.getElementById('manual_siswa_id')?.value;
        if (!id) {
            window.showToast?.('Pilih siswa terlebih dahulu.', 'warning');
            return;
        }
        var result = await submitVisit({
            visitor_type: 'siswa',
            method: 'manual',
            siswa_id: parseInt(id, 10),
            catatan: document.getElementById('manual-siswa-catatan')?.value || null,
        });
        handleVisitResult(result);
        if (result.success) {
            window.resetAjaxSelects?.(document.getElementById('manual-siswa'));
            var catatan = document.getElementById('manual-siswa-catatan');
            if (catatan) catatan.value = '';
        }
    }

    async function submitManualGuru() {
        var id = document.getElementById('manual_guru_id')?.value;
        if (!id) {
            window.showToast?.('Pilih guru terlebih dahulu.', 'warning');
            return;
        }
        var result = await submitVisit({
            visitor_type: 'guru',
            method: 'manual',
            guru_id: parseInt(id, 10),
            catatan: document.getElementById('manual-guru-catatan')?.value || null,
        });
        handleVisitResult(result);
        if (result.success) {
            window.resetAjaxSelects?.(document.getElementById('manual-guru'));
            var catatan = document.getElementById('manual-guru-catatan');
            if (catatan) catatan.value = '';
        }
    }

    async function submitManualFreeform(type, prefix) {
        var data = readFreeform(prefix);
        if (!data.nama) {
            window.showToast?.('Nama wajib diisi.', 'warning');
            return;
        }
        var result = await submitVisit({
            visitor_type: type,
            method: 'manual',
            nama: data.nama,
            asal: data.asal,
            telepon: data.telepon,
            catatan: data.catatan,
        });
        handleVisitResult(result);
        if (result.success) clearFreeform(prefix);
    }

    async function submitFaceGuru() {
        var id = document.getElementById('face_guru_id')?.value;
        if (!id) {
            window.showToast?.('Pilih guru terlebih dahulu.', 'warning');
            return;
        }
        var result = await submitVisit({
            visitor_type: 'guru',
            method: 'face',
            guru_id: parseInt(id, 10),
            foto_wajah: capturedPhotos.guru || null,
            catatan: document.getElementById('face-guru-catatan')?.value || null,
        });
        handleVisitResult(result);
        if (result.success) {
            capturedPhotos.guru = null;
            window.resetAjaxSelects?.(document.getElementById('face-guru'));
            document.getElementById('face-guru-catatan').value = '';
            resetCapturePreview('guru');
        }
    }

    async function submitFaceFreeform(type, prefix, photoKey) {
        var data = readFreeform(prefix);
        if (!data.nama) {
            window.showToast?.('Nama wajib diisi.', 'warning');
            return;
        }
        var result = await submitVisit({
            visitor_type: type,
            method: 'face',
            nama: data.nama,
            asal: data.asal,
            telepon: data.telepon,
            catatan: data.catatan,
            foto_wajah: capturedPhotos[photoKey] || null,
        });
        handleVisitResult(result);
        if (result.success) {
            capturedPhotos[photoKey] = null;
            clearFreeform(prefix);
            resetCapturePreview(photoKey);
        }
    }

    function resetCapturePreview(prefix) {
        var preview = document.getElementById(prefix + '-face-preview');
        if (preview) preview.innerHTML = 'Foto belum diambil.';
    }

    async function resolveRfidUid(uid) {
        var res = await fetch(config.resolveRfidUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ rfid_uid: uid }),
        });
        return res.json();
    }

    function renderRfidPreview(visitor) {
        var el = document.getElementById('rfid-preview');
        if (!el || !visitor) return;
        var typeLabel = visitor.visitor_type === 'guru' ? 'Guru' : 'Siswa';
        el.classList.add('is-ready');
        el.innerHTML = '<p class="rfid-scan-card__preview-name">' + visitor.nama + '</p>'
            + '<p class="rfid-scan-card__preview-meta">' + typeLabel + ' · ' + (visitor.nis || '-') + '</p>'
            + '<p class="rfid-scan-card__preview-meta">' + (visitor.asal || '-') + '</p>';
        document.getElementById('rfid-submit')?.removeAttribute('disabled');
    }

    async function handleRfidInput() {
        var uid = (document.getElementById('rfid-input')?.value || '').trim();
        rfidResolved = null;
        document.getElementById('rfid-submit')?.setAttribute('disabled', 'disabled');
        var preview = document.getElementById('rfid-preview');
        if (!uid) {
            if (preview) {
                preview.classList.remove('is-ready');
                preview.textContent = 'Belum ada kartu terbaca.';
            }
            return;
        }
        try {
            var result = await resolveRfidUid(uid);
            if (!result.success) {
                if (preview) {
                    preview.classList.remove('is-ready');
                    preview.textContent = result.message || 'Kartu tidak dikenali.';
                }
                return;
            }
            rfidResolved = { uid: uid, visitor: result.data.visitor };
            renderRfidPreview(rfidResolved.visitor);
        } catch (e) {
            if (preview) {
                preview.classList.remove('is-ready');
                preview.textContent = 'Gagal membaca kartu RFID.';
            }
        }
    }

    async function submitRfid() {
        if (!rfidResolved) {
            window.showToast?.('Scan kartu RFID terlebih dahulu.', 'warning');
            return;
        }
        var result = await submitVisit({
            visitor_type: rfidResolved.visitor.visitor_type,
            method: 'rfid',
            rfid_uid: rfidResolved.uid,
            catatan: document.getElementById('rfid-catatan')?.value || null,
        });
        handleVisitResult(result);
        if (result.success) {
            document.getElementById('rfid-input').value = '';
            document.getElementById('rfid-catatan').value = '';
            rfidResolved = null;
            var preview = document.getElementById('rfid-preview');
            if (preview) {
                preview.classList.remove('is-ready');
                preview.textContent = 'Belum ada kartu terbaca.';
            }
            document.getElementById('rfid-submit')?.setAttribute('disabled', 'disabled');
            document.getElementById('rfid-input')?.focus();
        }
    }

    async function loadModels() {
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(config.modelUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(config.modelUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(config.modelUrl),
        ]);
    }

    async function fetchReferences(state) {
        var res = await fetch(config.refsUrl, { headers: { Accept: 'application/json' } });
        var json = await res.json();
        state.references = json.data?.students || [];
        return state.references;
    }

    async function buildMatcher(state) {
        var labeled = [];
        for (var i = 0; i < state.references.length; i += 1) {
            var row = state.references[i];
            try {
                var img = await faceapi.fetchImage(row.photo_url);
                var det = await faceapi.detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.4 }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                if (det) labeled.push(new faceapi.LabeledFaceDescriptors('siswa-' + row.id, [det.descriptor]));
            } catch (e) { /* skip */ }
        }
        state.matcher = labeled.length ? new faceapi.FaceMatcher(labeled, state.threshold) : null;
        return labeled.length;
    }

    async function confirmSiswaVisit(student, foto) {
        var studentKey = String(student.id);
        if (pendingVerify || verifyingStudentIds.has(studentKey)) {
            return false;
        }

        pendingVerify = true;
        verifyingStudentIds.add(studentKey);

        var confirmed = false;
        try {
            if (window.showConfirm) {
                confirmed = await window.showConfirm({
                    title: 'Verifikasi Kunjungan',
                    message: 'Apakah identitas siswa berikut sudah benar?',
                    tone: 'primary',
                    confirmText: 'OK, Benar',
                    confirmIcon: 'ti-user-check',
                    headerIcon: 'ti-user-check',
                    detail: [
                        { label: 'Nama', value: student.name },
                        { label: 'NIS', value: student.nis },
                        { label: 'Kelas', value: student.kelas || '-' },
                    ],
                });
            } else {
                confirmed = window.confirm('Konfirmasi kunjungan ' + student.name + '?');
            }

            if (!confirmed) {
                return false;
            }

            return await submitVisit({
                visitor_type: 'siswa',
                method: 'face',
                siswa_id: parseInt(student.id, 10),
                foto_wajah: foto,
            });
        } finally {
            pendingVerify = false;
            verifyingStudentIds.delete(studentKey);
        }
    }

    function bindSiswaFaceDetection() {
        var toggleBtn = document.getElementById('siswa-face-toggle');
        var cameraModeSelect = document.getElementById('siswa-face-camera-mode');
        var video = document.getElementById('siswa-face-video');
        var overlay = document.getElementById('siswa-face-overlay');
        var matchCard = document.getElementById('siswa-face-match-card');
        var logList = document.getElementById('siswa-face-log');
        var engineStatus = document.getElementById('siswa-face-status');

        function appendLog(message, success) {
            if (!logList) return;
            var li = document.createElement('li');
            li.className = 'rounded-lg border px-3 py-2 text-sm ' + (success
                ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300'
                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300');
            li.textContent = message;
            logList.prepend(li);
        }

        async function openCameraStream() {
            if (siswaFaceState.stream) {
                siswaFaceState.stream.getTracks().forEach(function (t) { t.stop(); });
            }
            siswaFaceState.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: getFacingMode(cameraModeSelect) }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
            video.srcObject = siswaFaceState.stream;
            applyVideoMirror(video, cameraModeSelect);
            await video.play();
        }

        async function detectFrame() {
            if (!siswaFaceState.detectActive) return;
            siswaFaceState.rafId = requestAnimationFrame(detectFrame);

            if (!video || !siswaFaceState.matcher || pendingVerify) return;
            if ((Date.now() - siswaFaceState.lastDetectAt) < siswaFaceState.intervalMs) return;
            siswaFaceState.lastDetectAt = Date.now();
            if (!video.videoWidth) return;

            var detections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.45 }))
                .withFaceLandmarks()
                .withFaceDescriptors();
            faceapi.matchDimensions(overlay, { width: video.videoWidth, height: video.videoHeight });
            var resized = faceapi.resizeResults(detections, { width: video.videoWidth, height: video.videoHeight });
            overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);

            resized.forEach(function (det) {
                var best = siswaFaceState.matcher.findBestMatch(det.descriptor);
                new faceapi.draw.DrawBox(det.detection.box, {
                    label: best.toString(),
                    boxColor: best.distance <= siswaFaceState.threshold ? '#16a34a' : '#ef4444',
                }).draw(overlay);
            });

            for (var i = 0; i < detections.length; i += 1) {
                if (pendingVerify) break;
                var best = siswaFaceState.matcher.findBestMatch(detections[i].descriptor);
                if (best.label === 'unknown' || best.distance > siswaFaceState.threshold) continue;
                var studentId = best.label.replace('siswa-', '');
                var student = siswaFaceState.references.find(function (r) { return String(r.id) === studentId; });
                if (!student) continue;
                var key = String(student.id);
                if (verifyingStudentIds.has(key)) continue;
                var nowTs = Date.now();
                if (siswaFaceState.cooldown.has(key) && (nowTs - siswaFaceState.cooldown.get(key)) < 15000) continue;

                if (matchCard) {
                    matchCard.innerHTML = '<p class="font-semibold text-slate-900 dark:text-white">' + student.name + '</p><p class="text-sm text-slate-500">NIS ' + student.nis + '</p>';
                }

                try {
                    var foto = captureVideoFrame(video, cameraModeSelect);
                    var result = await confirmSiswaVisit(student, foto);
                    if (result && result.success) {
                        siswaFaceState.cooldown.set(key, Date.now());
                        appendLog(student.name + ' tercatat', true);
                        handleVisitResult(result);
                    } else if (result && !result.success) {
                        appendLog(result.message || 'Gagal ' + student.name, false);
                    } else {
                        appendLog('Verifikasi dibatalkan: ' + student.name, false);
                    }
                } catch (e) {
                    appendLog('Gagal simpan ' + student.name, false);
                }
            }
        }

        toggleBtn?.addEventListener('click', async function () {
            if (siswaFaceState.detectActive) {
                stopFaceSession(siswaFaceState, 'siswa');
                return;
            }
            if (!window.faceapi) {
                window.showToast?.('face-api.js gagal dimuat.', 'error');
                return;
            }
            try {
                if (!siswaFaceState.modelsReady) {
                    if (engineStatus) engineStatus.textContent = 'Memuat model deteksi wajah...';
                    await loadModels();
                    siswaFaceState.modelsReady = true;
                }
                if (!siswaFaceState.matcherReady) {
                    if (engineStatus) engineStatus.textContent = 'Memuat referensi wajah siswa...';
                    await fetchReferences(siswaFaceState);
                    var count = await buildMatcher(siswaFaceState);
                    siswaFaceState.matcherReady = true;
                    if (engineStatus) engineStatus.textContent = count ? 'Deteksi aktif — konfirmasi modal akan muncul jika cocok.' : 'Belum ada referensi wajah siswa';
                }
                await openCameraStream();
                siswaFaceState.detectActive = true;
                toggleBtn.classList.replace('btn-primary', 'btn-secondary');
                toggleBtn.innerHTML = '<i class="ti ti-camera-off text-base leading-none mr-1"></i>Stop Kamera';
                detectFrame();
            } catch (e) {
                stopFaceSession(siswaFaceState, 'siswa');
                window.showToast?.('Gagal membuka kamera atau memuat model.', 'error');
            }
        });

        cameraModeSelect?.addEventListener('change', async function () {
            if (!siswaFaceState.stream) return;
            try { await openCameraStream(); } catch (e) {
                window.showToast?.('Gagal mengganti kamera.', 'error');
            }
        });
    }

    function bindOptionalFaceCapture(prefix) {
        faceStates[prefix] = createFaceState();
        var state = faceStates[prefix];
        var toggleBtn = document.getElementById(prefix + '-face-toggle');
        var cameraModeSelect = document.getElementById(prefix + '-face-camera-mode');
        var captureBtn = document.getElementById(prefix + '-face-capture');
        var video = document.getElementById(prefix + '-face-video');
        var preview = document.getElementById(prefix + '-face-preview');
        var engineStatus = document.getElementById(prefix + '-face-status');

        async function openCameraStream() {
            if (state.stream) state.stream.getTracks().forEach(function (t) { t.stop(); });
            state.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: getFacingMode(cameraModeSelect) }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
            video.srcObject = state.stream;
            applyVideoMirror(video, cameraModeSelect);
            await video.play();
            captureBtn?.removeAttribute('disabled');
        }

        toggleBtn?.addEventListener('click', async function () {
            if (state.detectActive) {
                stopFaceSession(state, prefix);
                return;
            }
            try {
                await openCameraStream();
                state.detectActive = true;
                toggleBtn.classList.replace('btn-primary', 'btn-secondary');
                toggleBtn.innerHTML = '<i class="ti ti-camera-off text-base leading-none mr-1"></i>Stop Kamera';
                if (engineStatus) engineStatus.textContent = 'Kamera aktif — ambil foto dokumentasi jika diperlukan.';
            } catch (e) {
                stopFaceSession(state, prefix);
                window.showToast?.('Gagal membuka kamera.', 'error');
            }
        });

        cameraModeSelect?.addEventListener('change', async function () {
            if (!state.stream) return;
            try { await openCameraStream(); } catch (e) {
                window.showToast?.('Gagal mengganti kamera.', 'error');
            }
        });

        captureBtn?.addEventListener('click', function () {
            if (!video || !state.detectActive) return;

            var photo = captureVideoFrame(video, cameraModeSelect);
            if (!photo) {
                window.showToast?.('Gagal mengambil foto.', 'error');
                return;
            }
            capturedPhotos[prefix] = photo;
            if (preview) preview.innerHTML = '<img src="' + photo + '" alt="Pratinjau foto">';
            if (engineStatus) engineStatus.textContent = 'Foto dokumentasi siap. Lengkapi data lalu simpan kunjungan.';
        });
    }

    function bindPhotoPreview() {
        var modal = document.getElementById('photo-preview-modal');
        var img = document.getElementById('photo-preview-img');

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('.visitor-photo-btn');
            if (!btn) return;
            var visitId = btn.dataset.visitId;
            if (!visitId || !config.photoUrlTemplate) return;
            if (img) img.src = config.photoUrlTemplate.replace('__ID__', visitId) + '?t=' + Date.now();
            modal?.classList.remove('hidden');
        });

        document.getElementById('photo-preview-close')?.addEventListener('click', function () {
            modal?.classList.add('hidden');
            if (img) img.src = '';
        });
        modal?.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.classList.add('hidden');
                if (img) img.src = '';
            }
        });
    }

    document.querySelectorAll('.method-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { setMethod(btn.dataset.method); });
    });
    document.querySelectorAll('.visitor-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { setVisitorType(btn.dataset.type); });
    });

    document.getElementById('manual-siswa-submit')?.addEventListener('click', submitManualSiswa);
    document.getElementById('manual-guru-submit')?.addEventListener('click', submitManualGuru);
    document.getElementById('manual-karyawan-submit')?.addEventListener('click', function () {
        submitManualFreeform('karyawan', 'manual-karyawan');
    });
    document.getElementById('manual-non-submit')?.addEventListener('click', function () {
        submitManualFreeform('non_siswa', 'manual-non');
    });
    document.getElementById('face-guru-submit')?.addEventListener('click', submitFaceGuru);
    document.getElementById('face-karyawan-submit')?.addEventListener('click', function () {
        submitFaceFreeform('karyawan', 'face-karyawan', 'karyawan');
    });
    document.getElementById('face-non-submit')?.addEventListener('click', function () {
        submitFaceFreeform('non_siswa', 'face-non', 'non');
    });

    var rfidInput = document.getElementById('rfid-input');
    var rfidTimer = null;
    rfidInput?.addEventListener('input', function () {
        clearTimeout(rfidTimer);
        rfidTimer = setTimeout(handleRfidInput, 300);
    });
    rfidInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            handleRfidInput();
        }
    });
    document.getElementById('rfid-submit')?.addEventListener('click', submitRfid);

    setMethod('manual');
    setVisitorType('siswa');
    bindSiswaFaceDetection();
    facePrefixes.forEach(bindOptionalFaceCapture);
    bindPhotoPreview();

    window.addEventListener('beforeunload', function () {
        stopFaceSession(siswaFaceState, 'siswa');
        facePrefixes.forEach(function (prefix) {
            stopFaceSession(faceStates[prefix] || createFaceState(), prefix);
        });
    });
})();
