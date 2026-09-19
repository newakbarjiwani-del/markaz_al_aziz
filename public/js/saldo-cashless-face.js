/**
 * Deteksi wajah untuk "Tarik Saldo Cashless" (admin/dompet-digital/saldo-cashless).
 *
 * Alur: load model face-api.js -> ambil referensi wajah siswa (foto_wajah, scope sekolah
 * operator, opsional filter kelas) -> bangun FaceMatcher di browser -> nyalakan kamera ->
 * saat wajah cocok, panggil window.setCashlessWithdrawFaceStudent() agar siswa terisi di
 * form. Admin lalu mengisi nominal & keterangan seperti biasa.
 *
 * Deskriptor wajah tidak pernah dikirim ke server — hanya id siswa hasil kecocokan.
 */
(function () {
    var page = document.getElementById('saldo-cashless-page');
    if (!page) return;

    var toggleBtn = document.getElementById('withdraw-face-toggle');
    var cameraModeSelect = document.getElementById('withdraw-face-camera-mode');
    var kelasSelect = document.getElementById('withdraw-face-kelas');
    var video = document.getElementById('withdraw-face-video');
    var overlay = document.getElementById('withdraw-face-overlay');
    var statusEl = document.getElementById('withdraw-face-status');

    // Panel wajah hanya ada saat top-up/tarik saldo manual aktif.
    if (!toggleBtn || !video || !overlay) return;

    var refsUrl = page.getAttribute('data-face-references-url') || '';
    var modelUrl = page.getAttribute('data-face-model-url') || '';

    var faceState = {
        stream: null,
        detectActive: false,
        rafId: null,
        lastDetectAt: 0,
        matcher: null,
        references: [],
        modelsReady: false,
        matcherReady: false,
        matcherScopeKey: null,
        lastMatchAt: 0,
        intervalMs: 480,
        threshold: 0.5,
    };

    function setStatus(text) {
        if (statusEl) statusEl.textContent = text;
    }

    function scopeKey() {
        return 'kelas:' + (kelasSelect?.value || '');
    }

    function getFacingMode() {
        return cameraModeSelect?.value === 'environment' ? 'environment' : 'user';
    }

    function applyVideoMirror() {
        if (!video) return;
        video.style.transform = getFacingMode() === 'user' ? 'scaleX(-1)' : 'none';
    }

    function setToggleActive(active) {
        if (!toggleBtn) return;
        toggleBtn.classList.toggle('btn-secondary', active);
        toggleBtn.classList.toggle('btn-primary', !active);
        toggleBtn.innerHTML = active
            ? '<i class="ti ti-camera-off text-base leading-none mr-1"></i>Stop Kamera'
            : '<i class="ti ti-camera text-base leading-none mr-1"></i>Mulai Kamera';
    }

    async function fetchReferences() {
        var url = new URL(refsUrl, window.location.origin);
        if (kelasSelect?.value) {
            url.searchParams.set('kelas_id', kelasSelect.value);
        }
        var res = await fetch(url.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        var json = await res.json();
        if (!res.ok || json.success === false) {
            throw new Error(json.message || 'Gagal memuat referensi wajah.');
        }
        faceState.references = json.data?.students || [];
        return faceState.references;
    }

    async function loadModels() {
        setStatus('Memuat model deteksi wajah...');
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(modelUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl),
        ]);
    }

    async function buildMatcher() {
        var labeled = [];
        var total = faceState.references.length;

        if (!total) {
            faceState.matcher = null;
            setStatus('Belum ada referensi wajah untuk kelas ini. Rekam wajah siswa dulu.');
            return;
        }

        for (var i = 0; i < total; i += 1) {
            var row = faceState.references[i];
            setStatus('Memproses referensi wajah ' + (i + 1) + '/' + total + '...');
            try {
                var img = await faceapi.fetchImage(row.photo_url);
                var det = await faceapi.detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.4 }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                if (det) {
                    labeled.push(new faceapi.LabeledFaceDescriptors('siswa-' + row.id, [det.descriptor]));
                }
            } catch (e) { /* lewati referensi yang gagal */ }
        }

        faceState.matcher = labeled.length ? new faceapi.FaceMatcher(labeled, faceState.threshold) : null;
        setStatus(labeled.length
            ? 'Deteksi aktif — arahkan wajah siswa ke kamera.'
            : 'Referensi wajah gagal diproses. Rekam ulang foto wajah siswa.');
    }

    function findReference(studentId) {
        return faceState.references.find(function (r) { return String(r.id) === String(studentId); });
    }

    async function detectFrame() {
        if (!video || !faceState.matcher || !faceState.detectActive) return;
        faceState.rafId = requestAnimationFrame(detectFrame);
        if ((Date.now() - faceState.lastDetectAt) < faceState.intervalMs) return;
        faceState.lastDetectAt = Date.now();
        if (!video.videoWidth) return;

        var detections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.45 }))
            .withFaceLandmarks()
            .withFaceDescriptors();

        faceapi.matchDimensions(overlay, { width: video.videoWidth, height: video.videoHeight });
        var resized = faceapi.resizeResults(detections, { width: video.videoWidth, height: video.videoHeight });
        var ctx = overlay.getContext('2d');
        ctx.clearRect(0, 0, overlay.width, overlay.height);

        resized.forEach(function (det) {
            var best = faceState.matcher.findBestMatch(det.descriptor);
            new faceapi.draw.DrawBox(det.detection.box, {
                label: best.toString(),
                boxColor: best.distance <= faceState.threshold ? '#16a34a' : '#ef4444',
            }).draw(overlay);
        });

        for (var i = 0; i < detections.length; i += 1) {
            var best = faceState.matcher.findBestMatch(detections[i].descriptor);
            if (best.label === 'unknown' || best.distance > faceState.threshold) continue;

            var studentId = best.label.replace('siswa-', '');
            var student = findReference(studentId);
            if (!student) continue;

            // Satu deteksi = satu pengisian. Hentikan kamera agar admin isi nominal.
            faceState.lastMatchAt = Date.now();
            stopCamera();

            if (typeof window.setCashlessWithdrawFaceStudent === 'function') {
                window.setCashlessWithdrawFaceStudent({
                    id: student.id,
                    nis: student.nis,
                    name: student.name,
                    kelas: student.kelas,
                });
            }

            setStatus('Terdeteksi: ' + (student.name || '-') + ' (NIS ' + (student.nis || '-') + '). Isi nominal & keterangan.');
            window.showToast?.('Wajah terdeteksi: ' + (student.name || 'siswa') + '.', 'success');
            return;
        }
    }

    async function openCameraStream() {
        if (faceState.stream) {
            faceState.stream.getTracks().forEach(function (t) { t.stop(); });
        }
        faceState.stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: getFacingMode() }, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false,
        });
        video.srcObject = faceState.stream;
        applyVideoMirror();
        await video.play();
    }

    function stopCamera() {
        faceState.detectActive = false;
        if (faceState.rafId) cancelAnimationFrame(faceState.rafId);
        faceState.rafId = null;
        if (faceState.stream) {
            faceState.stream.getTracks().forEach(function (t) { t.stop(); });
            faceState.stream = null;
        }
        if (video) video.srcObject = null;
        if (overlay) overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);
        setToggleActive(false);
    }

    async function startDetection() {
        if (!window.faceapi) {
            window.showToast?.('face-api.js gagal dimuat. Cek koneksi internet.', 'error');
            return;
        }

        toggleBtn.disabled = true;
        try {
            if (!faceState.modelsReady) {
                await loadModels();
                faceState.modelsReady = true;
            }

            var key = scopeKey();
            if (!faceState.matcherReady || faceState.matcherScopeKey !== key) {
                setStatus('Mengambil referensi wajah...');
                await fetchReferences();
                await buildMatcher();
                faceState.matcherReady = true;
                faceState.matcherScopeKey = key;
            }

            if (!faceState.matcher) {
                // Status sudah dijelaskan oleh buildMatcher().
                return;
            }

            await openCameraStream();
            faceState.detectActive = true;
            setToggleActive(true);
            detectFrame();
        } catch (e) {
            stopCamera();
            window.showToast?.('Gagal membuka kamera atau memuat model wajah.', 'error');
            setStatus('Gagal memulai deteksi wajah. Coba lagi.');
        } finally {
            toggleBtn.disabled = false;
        }
    }

    toggleBtn.addEventListener('click', function () {
        if (faceState.detectActive) {
            stopCamera();
            setStatus('Kamera dihentikan.');
            return;
        }
        startDetection();
    });

    cameraModeSelect?.addEventListener('change', function () {
        if (!faceState.stream) return;
        openCameraStream().catch(function () {
            window.showToast?.('Gagal mengganti kamera.', 'error');
        });
    });

    kelasSelect?.addEventListener('change', function () {
        // Referensi tergantung kelas — paksa bangun ulang matcher saat kamera berikutnya dinyalakan.
        faceState.matcherReady = false;
        if (faceState.detectActive) {
            stopCamera();
            setStatus('Filter kelas berubah. Klik "Mulai Kamera" untuk memuat ulang referensi.');
        }
    });

    // Koordinasi dengan topup-cashless.js: hentikan kamera saat pindah dari metode wajah.
    window.cashlessWithdrawFace = {
        onMethodChange: function (method) {
            if (method !== 'face') {
                stopCamera();
                setStatus('Arahkan wajah siswa ke kamera. Nominal & keterangan diisi setelah siswa terdeteksi.');
            }
        },
    };

    // Hentikan kamera saat modal ditutup.
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-modal-close="withdraw-cashless-modal"]')) {
            stopCamera();
        }
    });

    window.addEventListener('beforeunload', stopCamera);
})();
