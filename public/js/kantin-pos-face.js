/**
 * Deteksi wajah untuk POS Belanja Kantin (portal/kantin/pos).
 *
 * Alur: load model face-api.js -> ambil referensi wajah siswa (foto_wajah + RFID cashless
 * aktif, scope sekolah operator, opsional filter kelas) -> bangun FaceMatcher di browser ->
 * nyalakan kamera -> saat wajah cocok, panggil window.kantinPosSetFaceStudent(id) agar siswa
 * ter-lookup & terisi di form. Operator lalu mengisi total belanja & keterangan.
 *
 * Deskriptor wajah tidak pernah dikirim ke server — hanya id siswa hasil kecocokan.
 */
(function () {
    var root = document.getElementById('kantin-pos-root');
    if (!root) return;

    var toggleBtn = document.getElementById('kantin-face-toggle');
    var cameraModeSelect = document.getElementById('kantin-face-camera-mode');
    var kelasSelect = document.getElementById('kantin-face-kelas');
    var video = document.getElementById('kantin-face-video');
    var overlay = document.getElementById('kantin-face-overlay');
    var statusEl = document.getElementById('kantin-face-status');

    if (!toggleBtn || !video || !overlay) return;

    var refsUrl = root.getAttribute('data-face-references-url') || '';
    var modelUrl = root.getAttribute('data-face-model-url') || '';
    var defaultStatus = 'Arahkan wajah siswa ke kamera. Total belanja diisi setelah siswa terdeteksi.';

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
        busy: false,
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
            setStatus('Belum ada referensi wajah (siswa berfoto wajah + RFID) untuk kelas ini.');
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
        if (faceState.busy) return;
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

            // Satu deteksi = satu lookup. Hentikan kamera agar operator isi total belanja.
            faceState.busy = true;
            stopCamera();
            setStatus('Terdeteksi: ' + (student.name || '-') + ' (NIS ' + (student.nis || '-') + '). Memuat data siswa...');

            if (typeof window.kantinPosSetFaceStudent === 'function') {
                Promise.resolve(window.kantinPosSetFaceStudent(student.id))
                    .then(function (ok) {
                        if (ok) {
                            setStatus('Siswa terdeteksi: ' + (student.name || '-') + '. Masukkan total belanja & keterangan.');
                            window.showToast?.('Wajah terdeteksi: ' + (student.name || 'siswa') + '.', 'success');
                        } else {
                            setStatus('Siswa terdeteksi tapi gagal diproses. Coba lagi atau gunakan RFID.');
                        }
                    })
                    .finally(function () {
                        faceState.busy = false;
                    });
            } else {
                faceState.busy = false;
            }
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
                return; // status sudah dijelaskan buildMatcher()
            }

            faceState.busy = false;
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
        faceState.matcherReady = false;
        if (faceState.detectActive) {
            stopCamera();
            setStatus('Filter kelas berubah. Klik "Mulai Kamera" untuk memuat ulang referensi.');
        }
    });

    // Koordinasi dengan kantin-pos.js.
    window.kantinPosFace = {
        onMethodChange: function (method) {
            if (method !== 'face') {
                stopCamera();
                setStatus(defaultStatus);
            }
        },
        onReset: function () {
            setStatus('Transaksi tersimpan. Klik "Mulai Kamera" untuk melayani siswa berikutnya.');
        },
    };

    window.addEventListener('beforeunload', stopCamera);
})();
