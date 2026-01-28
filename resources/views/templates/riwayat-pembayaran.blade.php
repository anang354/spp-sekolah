<!DOCTYPE html>
<html>
<head>
    <title>Laporan Tunggakan Per Desa</title>
    <style>
        .kop-surat {
            width: 100%;
            text-align: center;
            border-bottom: 2px solid #333;
            display: inline-block;
        }
        .kop-surat img {
            float: left;
        }
        .kop-surat .header{
            display: block;
        }
        .kop-surat h1 {
            font-size: 14pt;
            margin:0;
            padding: 0;
        }
        .kop-surat p {
            font-size: 11pt;
            margin:0;
            padding: 0;
        }
        body { font-family: sans-serif; font-size: 8pt; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #333; padding: 5px; vertical-align: top; }
        
        /* Styling Khusus Grouping */
        .group-header { background-color: #e0e0e0; font-weight: bold; }
        .sub-total { background-color: #f9f9f9; font-weight: bold; text-align: right; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Agar tabel tidak terpotong jelek saat pindah halaman */
        .desa-block { page-break-inside: avoid; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="kop-surat">
    <img src="{{ $logo }}" alt="" width="70px"/>
    <div class="header">
        <h1>YAYASAN BUDI LUHUR BATAM</h1>
    <p>Komplek Patam Asri Blok T No.01 Kel. Patam Lestari,Kec. Sekupang, Kota Batam</p>
    <p>Telp. 0778-354-0541 www.blbs-batam.sch.id</p>
    </div>
</div>
    <div class="header">
        <h2>Riwayat Pembayaran Siswa</h2>
        <p>Tanggal Cetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }} oleh {{ auth()->user()->name }}</p>
    </div>
    <table>
        <thead>
            <tr style="background-color: #333; color: white;">
                <th>No</th>
                <th>Tanggal</th>
                <th>Nomor Bayar</th>
                <th>Nama Siswa</th>
                <th>Tagihan Dibayar</th>
                <th>Nominal</th>
                <th>Metode</th>
                <th>Jenis</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBayar = 0;
            @endphp
            @foreach($records as $record)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $record['tanggal_pembayaran'] }}</td>
                <td>{{ $record['nomor_bayar'] }}</td>
                <td>{{ $record['siswa']['nama'] }}</td>
                <td>{{ $record['tagihan']['daftar_biaya'].' '.\App\Models\Tagihan::BULAN[$record['tagihan']['periode_bulan']].' '.$record['tagihan']['periode_tahun'] }}</td>
                <td>{{ number_format($record['jumlah_dibayar'], 0, ',', '.') }}</td>
                <td>{{ $record['metode_pembayaran'] }}</td>
                <td>{{ $record['tagihan']['jenis_keuangan'] }}</td>
            </tr>
            @php
                $totalBayar += $record['jumlah_dibayar'];
            @endphp
            @endforeach
            <tr style="font-size: 10pt; background-color: #f5e870;">
                <td colspan="5" class="text-right"><strong>TOTAL PEMBAYARAN</strong></td>
                <td colspan="3"  class="text-left"><strong>Rp {{ number_format($totalBayar, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
   

</body>
</html>