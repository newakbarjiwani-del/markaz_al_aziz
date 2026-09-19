# Panduan Deteksi Wajah — Portal Guru

Dokumen ini menjelaskan implementasi **Absensi Siswa - Deteksi Wajah** pada Laravel:

- Halaman: `portal/guru/absensi-siswa`
- Rekap tabel dipisah ke: `portal/guru/rekap-siswa`
- Penyimpanan absensi ke tabel: `absensi_siswa`
- Toast sukses menyertakan nama siswa, NIS, kelas, dan pelajaran (`App\Support\ActionMessage`)

Dokumen ini melengkapi `Rekam_Wajah.md` (foto referensi absensi tersimpan di `siswa_wajah.foto_wajah`, dengan flag daftar `siswa.has_foto_wajah` — **bukan** foto profil kartu pelajar di `profil_siswa.photo_path`).

---

## Ringkasan alur terbaru

```text
[Guru buka /portal/guru/absensi-siswa]
  │
  ├─ Load model face-api.js (SSD + landmark + recognition)
  ├─ GET referensi siswa (foto_wajah) dari endpoint Laravel
  ├─ Build descriptor di browser (memory)
  │
  ▼
[User klik "Mulai Kamera"]
  ├─ Kamera dinyalakan setelah model siap
  ├─ Loop deteksi pakai requestAnimationFrame + throttle
  │
  ▼
[Wajah cocok (distance <= threshold)]
  ├─ POST /portal/guru/absensi-siswa/store
  ├─ Insert ke tabel absensi_siswa (atau respon already_recorded)
  ├─ Update panel "Deteksi Terakhir" + log sesi
  └─ Muncul modal:
     - "Absensi Berhasil" atau
     - "Siswa Sudah Absen"
```

---

## Arsitektur komponen (Laravel saat ini)

### Route portal guru

| Method | Endpoint | Nama route | Fungsi |
|---|---|---|---|
| `GET` | `/portal/guru/absensi-siswa` | `portal.guru.absensi-siswa.index` | Halaman deteksi wajah |
| `GET` | `/portal/guru/absensi-siswa/referensi` | `portal.guru.absensi-siswa.references` | Ambil referensi wajah siswa |
| `GET` | `/portal/guru/absensi-siswa/foto/{siswa}` | `portal.guru.absensi-siswa.photo` | Ambil foto wajah siswa |
| `POST` | `/portal/guru/absensi-siswa/store` | `portal.guru.absensi-siswa.store` | Simpan absensi ke `absensi_siswa` |
| `GET` | `/portal/guru/rekap-siswa` | `portal.guru.rekap-siswa.index` | Halaman tabel rekap |
| `GET` | `/portal/guru/rekap-siswa/data` | `portal.guru.rekap-siswa.data` | Data table rekap |

### File utama

| File | Peran |
|---|---|
| `resources/views/portal/guru/absensi-siswa.blade.php` | UI deteksi wajah + script deteksi + modal |
| `public/js/portal-guru-absensi.js` | Deteksi wajah, RFID, absensi manual |
| `app/Http/Controllers/Portal/Guru/AbsensiSiswaController.php` | Endpoint referensi/foto/store absensi |
| `app/Http/Controllers/Portal/Guru/RekapSiswaController.php` | Data rekap siswa |
| `config/portal-menus/guru.php` | Menu sidebar guru (`Absensi Siswa`, `Rekap Siswa`) |

---

## Prasyarat operasional

| Prasyarat | Keterangan |
|---|---|
| Foto referensi | `siswa.has_foto_wajah = 1`; blob base64 valid berada di `siswa_wajah.foto_wajah` |
| Koneksi internet | Perlu untuk memuat `face-api.js` + weights CDN |
| Izin kamera browser | Wajib diizinkan user |
| Data siswa satu sekolah | Referensi diambil berdasarkan `sekolah_id` guru login |

---

## Sumber data referensi wajah

Referensi diambil dari query `faceReferenceQuery()` di `AbsensiSiswaController`:

- `siswa.sekolah_id` sama dengan sekolah guru login
- `siswa.status` dalam `['aktif', 'pending']`
- `siswa.has_foto_wajah = 1`

Endpoint `references` mengembalikan:

```json
{
  "success": true,
  "data": {
    "students": [
      {
        "id": 1,
        "nis": "3000001",
        "name": "Dasa Jailani",
        "kelas": "XIPA1",
        "photo_url": "..."
      }
    ]
  }
}
```

---

## Model & deteksi (versi terbaru)

### Model yang dimuat

```html
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
```

Weights:

```text
https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights
```

Model:
- `ssdMobilenetv1`
- `faceLandmark68Net`
- `faceRecognitionNet`

### Urutan start

1. `loadModels()`
2. `fetchReferences()`
3. `buildMatcher()`
4. Baru `getUserMedia()` (kamera ON)

> Jadi kamera memang dinyalakan **setelah model siap**.

### Loop deteksi

Implementasi terbaru memakai:
- `requestAnimationFrame` untuk loop halus
- throttle internal (`intervalMs`) agar hemat CPU

Bukan `setInterval` murni.

---

## Konstanta penting (current)

| Item | Nilai | Fungsi |
|---|---|---|
| `threshold` | `0.5` | Ambang kecocokan (`FaceMatcher`) |
| `intervalMs` | `480` ms | Throttle antar proses deteksi |
| `minConfidence` live | `0.45` | Confidence deteksi wajah dari video |
| `minConfidence` referensi | `0.4` | Confidence saat ekstraksi descriptor referensi |
| Cooldown siswa | `20000` ms | Hindari spam save untuk siswa yang sama |

---

## Penyimpanan absensi ke database

Tabel tujuan: `absensi_siswa`

Kolom yang diisi saat insert:
- `sekolah_id`
- `siswa_id`
- `date` (`Y-m-d`)
- `status` (`hadir|terlambat|izin|sakit`)
- `time_in` (`H:i`)

Aturan backend:
- Jika siswa **sudah ada absensi hari ini** → tidak insert ulang, return `already_recorded: true`
- Jika belum ada → insert baru, return `already_recorded: false`

Contoh respons:

```json
{
  "success": true,
  "message": "Siswa sudah tercatat absensi hari ini.",
  "data": {
    "already_recorded": true,
    "attendance": {
      "nis": "3000001",
      "name": "Dasa Jailani",
      "kelas": "XIPA1",
      "status": "Hadir",
      "time_in": "07:42",
      "date": "02/07/2026"
    }
  }
}
```

---

## UI/UX behavior terbaru

- Area kamera menggunakan frame modern (mobile-like) dengan panduan oval.
- Panel kanan:
  - `Deteksi Terakhir` (nama, NIS, kelas, jarak)
  - `Log Absensi Sesi Ini` (hanya log frontend sesi aktif)
- Modal absensi:
  - `Absensi Berhasil`
  - `Siswa Sudah Absen`
- Modal `already recorded` diberi cooldown agar tidak spam tiap frame.

> Catatan: list log di panel kanan **bukan table database**, hanya state frontend sesi berjalan.

---

## Troubleshooting terbaru

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Referensi wajah `0` | Foto wajah belum ada / filter sekolah tidak cocok | Cek `siswa.foto_wajah`, pastikan user guru sekolah yang sama |
| Kamera tidak aktif | Izin kamera browser ditolak | Izinkan permission kamera, refresh halaman |
| `face-api.js gagal dimuat` | CDN tidak terjangkau | Cek internet/firewall |
| Wajah terdeteksi tapi tidak match | Foto referensi kurang bagus / cahaya buruk | Rekam ulang foto wajah dengan pencahayaan baik |
| Modal sudah absen terlalu sering | Wajah tetap berada di frame | Sistem sudah pakai cooldown, tunggu jeda sebelum scan lagi |

---

## Catatan migrasi dari dokumen lama

Dokumen lama membahas:
- `admin/modules/face.html`
- API `rekam-data.php` / `presensi-log.php`
- tabel `presensi_log`

Semua itu **bukan alur aktif** di implementasi Laravel portal guru saat ini.
Implementasi aktif sekarang menggunakan route/controller Laravel dan tabel `absensi_siswa`.

---

*Dokumen ini sudah disesuaikan dengan perubahan terakhir pada portal guru (deteksi wajah + rekap siswa) per Juli 2026.*
