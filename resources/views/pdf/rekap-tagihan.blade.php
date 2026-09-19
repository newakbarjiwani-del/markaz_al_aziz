<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 4px; }
        th { background: #eee; }
    </style>
</head>
<body>
@include('layouts.export.kop_file')
<h2>Rekap</h2>
<table>
    <thead><tr><th>Unit</th><th>BTA</th><th>Tagihan</th><th>Jumlah</th><th>Total</th></tr></thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row->DESC01 ?: $row->CODE01 }}</td>
            <td>{{ $row->BTA }}</td>
            <td>{{ $row->BILLNM }}</td>
            <td>{{ $row->jumlah }}</td>
            <td>{{ number_format((int) $row->total, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
