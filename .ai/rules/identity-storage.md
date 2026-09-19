# Face and RFID identity storage

- Face blobs never live on parent rows. Student reference photos use `siswa_wajah`; library visit snapshots use `pengunjung_perpustakaan_wajah`.
- List, filter, and reference queries use the indexed parent `has_foto_wajah` flag and must not join or select face blobs. Only single-photo endpoints load `wajah`.
- Keep face row creation/deletion and the parent flag update in one database transaction.
- RFID identity lives only in the global `rfid` registry. Keep HTTP/form field names `rfid_uid`, and keep `pengunjung_perpustakaan.rfid_uid` as an audit snapshot.
- Resolve scanned cards through `RfidResolver`. Use `Siswa::assignRfid()` / `Guru::assignRfid()` for assignments and the model helpers for UID/block state.
- An RFID row has exactly one holder. Unassignment and holder soft deletion hard-delete the registry row so its globally unique UID can be reused.
