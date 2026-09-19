<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kuitansi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 4px 0; }
    </style>
</head>
<body>
@include('layouts.export.kop_file')
<h1>Kuitansi Pembayaran</h1>
<table>
    <tr><td>NIS</td><td>: {{ $siswa->NOCUST }}</td></tr>
    <tr><td>Nama</td><td>: {{ $siswa->NMCUST }}</td></tr>
    <tr><td>Tagihan</td><td>: {{ $bill->BILLNM }}</td></tr>
    <tr><td>Nominal</td><td>: Rp {{ number_format((int) $bill->BILLAM, 0, ',', '.') }}</td></tr>
    <tr><td>Tanggal bayar</td><td>: {{ $bill->PAIDDT }}</td></tr>
    <tr><td>No. Ref</td><td>: {{ $bill->NOREFF ?: '-' }}</td></tr>
</table>
</body>
</html>
