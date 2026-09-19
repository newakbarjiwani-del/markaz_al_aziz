@props(['schools' => []])

<div>
    <label class="form-label" for="teacher-sekolah-id">Sekolah <span class="text-muted text-xs font-normal">(opsional)</span></label>
    <select name="sekolah_id" id="teacher-sekolah-id" class="form-input" data-s2>
        <option value="">Tanpa sekolah / lintas sekolah</option>
        @foreach($schools as $school)
            <option value="{{ $school->id }}">{{ $school->name }}</option>
        @endforeach
    </select>
</div>
<div><label class="form-label" for="teacher-nip">NIP</label><input name="nip" id="teacher-nip" class="form-input" required></div>
<div><label class="form-label" for="teacher-name">Nama</label><input name="name" id="teacher-name" class="form-input" required></div>
<div><label class="form-label" for="teacher-jabatan">Jabatan</label><input name="jabatan" id="teacher-jabatan" class="form-input"></div>
<div><label class="form-label" for="teacher-jenis-guru">Jenis Guru</label><input name="jenis_guru" id="teacher-jenis-guru" class="form-input"></div>
<div><label class="form-label" for="teacher-golongan">Golongan</label><input name="golongan" id="teacher-golongan" class="form-input"></div>
<div><label class="form-label" for="teacher-phone">Telepon</label><input name="phone" id="teacher-phone" class="form-input"></div>
<div>
    <label class="form-label" for="teacher-rfid-input">Kartu RFID</label>
    <input name="rfid_uid" id="teacher-rfid-input" class="form-input font-mono" maxlength="64" placeholder="UID kartu RFID (opsional)" autocomplete="off">
    <p class="text-muted mt-1 text-xs">Digunakan untuk absensi guru, kunjungan perpustakaan, dan modul RFID lainnya.</p>
</div>
<div><label class="form-label" for="teacher-status">Status</label>
    <select name="status" id="teacher-status" class="form-input"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select>
</div>
<div><x-profile-photo-upload /></div>
