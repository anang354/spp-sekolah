<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Tagihan ALumni</title>
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
            background-color: rgba(38,137,13,1);
            border: 1px solid rgba(134,188,37,1);
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
            border: 1px solid rgba(134,188,37,1);
        }
        .tb-tagihan thead tr, .tb-tagihan tbody tr {
            border: 1px solid rgba(134,188,37,1);
            margin:0;
            padding: 0;
            font-size: 11pt;
        }
        .tb-tagihan tbody td {
            padding: 5px;
            font-size: 10pt;
            border: 1px solid rgba(134,188,37,1);
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
            border-top: 1px solid rgba(134,188,37,1);
            border-bottom: 1px solid rgba(134,188,37,1);
        }
        .tb-summarize tr th {
            padding: 10px;
            color: #FFF;
            background: #123524;
        }
    </style>
</head>
<body>
    <div class="kop-surat">
        <h1>SMAS BUDI LUHUR BOARDING SCHOOL</h1>
        <p>Komplek Patam Asri Blok T No.01 Sekupang, Batam Kota</p>
    </div>
    <div class="center-paragraph">
        <h4>Kartu Rincian Tagihan & Pembayaran Siswa</h4>
    </div>
    <div class="bio">
        <p>Nama : Anang Egga Ursula Huda</p>
        <p>Alamat Sambung: Patam 2, Sekupang, Batam</p>
    </div>
    <table class="tb-tagihan">
        <thead style="background: #123524; color: #FFF;">
            <tr>
                <th colspan="6">RINCIAN TAGIHAN</th>
            </tr>
        </thead>
        <thead>
            <tr>
                <th>Periode</th>
                <th>Item</th>
                <th>Jumlah Tagihan</th>
                <th>Jumlah Diskon</th>
                <th>Tagihan Netto</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i <= 12; $i++)
            <tr>
                <td>Januari 2025</td>
                <td>SPP, Uang Makan</td>
                <td>900,000</td>
                <td>0</td>
                <td>900,000</td>
                <td>baru</td>
            </tr>
            @endfor
            <tr class="summarize">
                <td colspan="2">Total</td>
                <td>1,800,000</td>
                <td>0</td>
                <td>1,800,000</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <div class="mt-2"></div>
    <table class="tb-tagihan">
        <thead style="background: #123524; color: #FFF;">
            <tr>
                <th colspan="5">RINCIAN PEMBAYARAN</th>
            </tr>
        </thead>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tagihan</th>
                <th>Jumlah DIbayar</th>
                <th>Metode Pembayaran</th>
                <th>Operator</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i <= 7; $i++)
            <tr>
                <td>12-01-2025</td>
                <td>Januari 2025</td>
                <td>900,000</td>
                <td>cash</td>
                <td>Intan</td>
            </tr>
            @endfor
            <tr class="summarize">
                <td colspan="2">Total</td>
                <td>1,800,000</td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>
<div class="mt-2"></div>
<div class="mt-2"></div>
    <table class="tb-summarize">
        <thead>
            <tr>
                <th>Jumlah Tagihan</th>
                <th>1,800,000</th>
                <th>Jumlah Dibayar</th>
                <th>900,000</th>
            </tr>
        </thead>
    </table>
<div class="mt-2"></div>

    <table class="tb-summarize">
        <thead>
            <tr>
                <th style="font-size: 16pt;">Total Kekurangan</th>
                <th style="font-size: 16pt;">1,800,000</th>
            </tr>
        </thead>
    </table>
    <p style="text-decoration: italic;">Terbilang: Satu Juta Delapan Ratus Ribu Rupiah</p>
</body>
</html>