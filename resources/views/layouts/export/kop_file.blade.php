@php use Carbon\Carbon; @endphp
    <!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>

    <style type="text/css">
        @page {
            margin: 30px;
        }

        * {
            font-family: "Times New Roman", Times, serif;
            font-weight: 400;
        }

        table {
            font-size: small;
        }

        tfoot tr td {
            font-weight: bold;
            font-size: small;
        }

        .table-border {
            border-collapse: collapse;
        }

        .table-border td, th {
            border: 2px solid gray;
            padding: 8px;
        }

        .border-bottom {
            border-collapse: collapse;
            border-bottom: 0.3rem double #000;
            text-align: left;
            padding: 8px;
        }

        .border-top-0 {
            border-top: 0 !important
        }

        .border-bottom-0 {
            border-bottom: 0 !important
        }

        .border-left-0 {
            border-left: 0 !important
        }

        .border-right-0 {
            border-right: 0 !important
        }

        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
            margin-top: 0;
            margin-bottom: 0;
        }

        .kartu-title {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        .double-hr-alt {
            margin: 10px 0;
        }

        .line {
            width: 100%;
            height: 2px;
            background: black;
        }

        .line.bold {
            height: 4px; /* Bolder line */
            margin-bottom: 2px;
        }

        .line.thin {
            height: 1px; /* Thinner line */
            background: gray;
        }

        .breakable {
            display: inline-block;
            max-width: 70ch; /* Limit to 20 characters */
            overflow-wrap: break-word; /* Break long words */
            word-break: break-word; /* Ensure text wraps nicely */
            white-space: normal;
        }

        .subheader {
            font-size: 10pt;
        }

        table.main-table {
            font-size: 10pt;
        }

        table.main-table td,
        table.main-table th {
            font-size: inherit;
        }

        .text-end {
            text-align: right !important;
        }
    </style>
    @yield('style')
</head>
<body>

@php
    $logoPath = public_path('logo.png');
    $logoBase64 = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
@endphp
<table width="100%" class="">
    <tr class="">
        <td style="text-align: center; vertical-align: middle;">
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}"
                     style="max-height: 5rem; display: block; margin: auto;" alt="logo"/>
            @endif
        </td>
        <td>
            <table style="width: 100%;">
                <tr>
                    <td align="center">
                        @if(config('app.nama_sub_instansi_1'))
                            <h3>{{config('app.nama_sub_instansi_1')}}</h3>
                        @endif
                        @if(config('app.nama_sub_instansi_2'))
                            <h3>{{config('app.nama_sub_instansi_2')}}</h3>
                        @endif
                        <h2>{{config('app.nama_instansi')}}</h2>
                    </td>
                </tr>
                @if(config('app.akreditasi'))
                    <tr>
                        <td align="center">
                            <h3> {{config('app.akreditasi')}} </h3>
                        </td>
                    </tr>
                @endif
                @if(config('app.NDS') || config('app.NPSN') || config('app.NSS'))
                    <tr style="font-size: 10pt;">
                        <td align="center">
                            @if(config('app.NDS'))
                                NDS: {{config('app.NDS')}}@if(config('app.NPSN') || config('app.NSS'))
                                    ,
                                @endif
                            @endif
                            @if(config('app.NPSN'))
                                NPSN: {{config('app.NPSN')}}@if(config('app.NSS'))
                                    ,
                                @endif
                            @endif
                            @if(config('app.NSS'))
                                NSS: {{config('app.NSS') }}
                            @endif
                        </td>
                    </tr>
                @endif
                <tr style="font-size: 10pt;">
                    <td align="center">
                        {{config('app.alamat')}}
                    </td>
                </tr>
                @if(config('app.telepon') || config('app.email') || config('app.website'))
                    <tr style="font-size: 10pt;">
                        <td align="center">
                            @if(config('app.telepon'))
                                No. Telp: {{config('app.telepon')}}@if(config('app.email') || config('app.website'))
                                    ,
                                @endif
                            @endif
                            @if(config('app.email'))
                                E-mail: {{config('app.email')}}@if(config('app.website'))
                                    ,
                                @endif
                            @endif
                            @if(config('app.website'))
                                Website: {{config('app.website') }}
                            @endif
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
<div class="double-hr-alt">
    <div class="line bold"></div>
    <div class="line thin"></div>
</div>
@yield('content')
@php
    $user = Auth::user();
    $tandaTanganBase64 = null;
@endphp
<table class="main-table" style="width: 100%; padding-top: 0.425rem">
    <tfoot>
    <tr>
        <td rowspan="3" style="width:65%; color: #fff;"></td>
        <td align="center" style="width:35%;">{{config('app.domisili')}}
            , {{Carbon::now()->isoFormat('dddd, D MMMM YYYY')}}</td>
    </tr>
    @if($tandaTanganBase64)
        <tr>
            <td align="center">
                <img
                    src="data:image/png;base64,{{ $tandaTanganBase64 }}"
                    style="max-height: 5rem; display: block; margin: auto;" alt="tanda tangan"/>
            </td>
        </tr>
    @endif
    <tr>
        <td align="center">{{ $user?->name }}</td>
    </tr>
    </tfoot>
</table>

</body>
</html>
