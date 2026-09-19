<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pendapatan Kantin</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 4px; }
    </style>
</head>
<body>
@include('layouts.export.kop_file')
<h2>Pendapatan Kantin</h2>
<table>
    <thead><tr><th>Kode</th><th>Kantin</th><th>Transaksi</th><th>Total</th></tr></thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row->KDKANTIN ?: $row->KANTIN }}</td>
            <td>{{ $row->NamaKantin }}</td>
            <td>{{ $row->jumlah }}</td>
            <td>{{ number_format((int) $row->total, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
