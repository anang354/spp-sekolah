<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Spp</title>
    <style>
        @font-face {
        font-family: 'Open Sans';
        font-style: normal;
        font-weight: normal;
        src: url(http://themes.googleusercontent.com/static/fonts/opensans/v8/cJZKeOuBrn4kERxqtaUH3aCWcynf_cDxXwCLxiixG1c.ttf) format('truetype');
        }
        body {
            box-sizing: border-box;
            font-family: "Open Sans", Calibri,Candara,Segoe,Segoe UI,Optima,Arial,sans-serif;
        }
        .kop-surat {
            width: 100%;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
            display: inline-block;
        }
        .kop-surat img {
            float: left;
        }
        .kop-surat .header{
            display: block;
            padding:15px 0px;
        }
        .kop-surat h1 {
            font-size: 16pt;
            margin:0;
            padding: 0;
        }
        .kop-surat p {
            font-size: 11pt;
            margin:0;
            padding: 0;
        }
        div.center-paragraph {
            width: 100%;
            text-align: center;
        }
        p {
            font-size: 10pt;
        }
        .text-bold {
            font-weight: 500;
        }
        .tb-tagihan {
            width: 100%;
            border-collapse: collapse;
        }
        .tb-tagihan thead, .tb-tagihan tbody tr.summarize {
            background-color: rgb(13, 98, 137);
            border: 1px solid rgb(37, 133, 188);
        }
        .tb-tagihan tbody tr.summarize td {
            font-weight: bold;
            font-size: 12pt;
            color: #FFF;
        }
        .tb-tagihan thead tr th {
            text-align: left;
            color: #FFF;
            font-size: 11pt;
            padding: 5px;
            border: 1px solid rgb(37, 133, 188);
        }
        .tb-tagihan thead tr, .tb-tagihan tbody tr {
            border: 1px solid rgb(37, 133, 188);
            margin:0;
            padding: 0;
            font-size: 11pt;
        }
        .tb-tagihan tbody td {
            padding: 5px;
            font-size: 10pt;
            border: 1px solid rgb(37, 133, 188);
        }
        .mt-2 {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .tb-summarize {
            width: 100%;
            border-collapse: collapse;
        }
        .tb-summarize tr {
            border-top: 1px solid rgb(37, 133, 188);
            border-bottom: 1px solid rgb(37, 133, 188);
        }
        .tb-summarize tr th {
            padding: 10px;
            color: #FFF;
            background: #123524;
        }
        .qrcode {
            width: 100%;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="kop-surat">
        <img src="{{ $logo }}" alt="" width="90px"/>
       <div class="header">
           <h1>YAYASAN BUDI LUHUR BOARDING SCHOOL</h1>
        <p>Komplek Patam Asri Blok T No.01 Kel. Patam Lestari,Kec. Sekupang, Kota Batam</p>
        <p>Telp. 0778-354-0541 www.blbs-batam.sch.id</p>
       </div>
    </div>
    <div class="center-paragraph">
        <h4>KWITANSI PEMBAYARAN SISWA</h4>
    </div>
    <table style="width: 100%; font-size: 10pt; margin-top: 10px;">
        <tbody>
            <tr>
                <td>Nama : {{ $pembayaran[0]['siswa']['nama'] }}</td>
                <td style="text-align: right;">Nomor Bayar : <b>{{ $pembayaran[0]['nomor_bayar'] }}</b></td>
            </tr>
            <tr>
                <td>Kelas : {{ \App\Models\Kelas::find($pembayaran[0]['siswa']['kelas_id'])->nama_kelas }}</td>
                <td style="text-align: right;">Tanggal Pembayaran : {{ date('d F Y', strtotime($pembayaran[0]['tanggal_pembayaran'])) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="mt-2"></div>
    <table class="tb-tagihan">
        <thead style="background: #133053; color: #FFF;">
            <tr>
                <th colspan="3">RINCIAN PEMBAYARAN</th>
            </tr>
        </thead>
        <thead>
            <tr>
                <th>Tagihan</th>
                <th>Jumlah DIbayar</th>
                <th>Metode Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPembayaran = 0;
            @endphp
            @foreach($pembayaran as $itemPembayaran)
            @php
                $totalPembayaran += $itemPembayaran['jumlah_dibayar'];
            @endphp
            <tr>
                <td> {{$itemPembayaran['tagihan']['daftar_biaya']}} {{ \App\Models\Tagihan::BULAN[$itemPembayaran['tagihan']['periode_bulan']].' '.$itemPembayaran['tagihan']['periode_tahun'] }}</td>
                <td>{{ number_format($itemPembayaran['jumlah_dibayar'], 0, '', '.') }}</td>
                <td>{{ $itemPembayaran['metode_pembayaran'] }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2"><b>Total</b></td>
                <td><b>{{ number_format($totalPembayaran, 0, '', '.') }}</b></td>
            </tr>
        </tbody>
    </table>
<div class="mt-2"></div>

    <p style="font-style: italic;">Terbilang: {{ \App\Helpers\Terbilang::make((int) $totalPembayaran) }}</p>
    <div class="qrcode">
        <p>Dicetak pada {{ now()->format('d-m-Y H:i:s') }}</p>
        <img src="data:image/svg+xml;base64,{{ $qrcode }}" alt="QR Code"/>
        <p>{{ $pembayaran[0]['user']['name'] }}</p>
    </div>
</body>
</html>
