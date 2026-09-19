<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 4px; }
    </style>
</head>
<body>
@include('layouts.export.kop_file')
<h2>Rekap Absensi</h2>
<table>
    <thead><tr><th>Tanggal</th><th>Unit</th><th>Jumlah</th></tr></thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row->tanggal }}</td>
            <td>{{ $row->DESC01 ?: $row->CODE01 }}</td>
            <td>{{ $row->jumlah }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
