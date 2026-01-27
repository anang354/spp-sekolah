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
        <h2>Laporan Rekapitulasi Tunggakan Siswa Per Desa</h2>
        <p>Tanggal Cetak: {{ $tanggalCetak }} oleh {{ auth()->user()->name }}</p>
    </div>

    {{-- LOOP UTAMA: GROUP BY DESA --}}
    @foreach($groupedData as $namaDesa => $siswas)
    
    <div class="desa-block">
        <h3 style="margin-bottom: 5px; text-transform: uppercase;">DESA: {{ $namaDesa }}</h3>

        <table>
            <thead>
                <tr style="background-color: #333; color: white;">
                    <th width="5%">No</th>
                    <th width="25%">Nama Siswa</th>
                    <th>Kelompok</th>
                    <th width="40%">Rincian Tagihan Belum Lunas</th>
                    <th width="30%">Sisa Tunggakan</th>
                </tr>
            </thead>
            <tbody>
                {{-- LOOP KEDUA: LIST SISWA DALAM DESA --}}
                @foreach($siswas as $index => $siswa)
                    @if($siswa->sisa_tagihan_total > 0) <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $siswa->nama }}</strong><br>
                            <span style="font-size: 8pt; color: #555;">{{ $siswa->nomor_hp }}</span><br>
                            <span style="font-size: 8pt; color: #555;">{{ $siswa->nama_wali }}</span><br>
                        </td>
                        <td>{{ $siswa->alamatSambung->kelompok }}</td>
                        <td>
                            <ul style="margin: 0; padding-left: 15px; font-size: 9pt;">
                                @foreach($siswa->tagihans as $tagihan)
                                    @php
                                        $dibayar = $tagihan->pembayaran->sum('jumlah_dibayar');
                                        $sisaItem = $tagihan->jumlah_netto - $dibayar;
                                    @endphp
                                    
                                    @if($sisaItem > 0)
                                        <li>
                                            {{ $tagihan->daftar_biaya }} 
                                            ({{ \Carbon\Carbon::createFromDate(null, $tagihan->periode_bulan, 1)->translatedFormat('M') }} {{ $tagihan->periode_tahun }})
                                            : Rp {{ number_format($sisaItem, 0, ',', '.') }}
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </td>
                        <td class="text-right">
                            <strong>Rp {{ number_format($siswa->sisa_tagihan_total, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                    @endif
                @endforeach
                
                {{-- SUB TOTAL PER DESA --}}
                <tr class="sub-total">
                    <td colspan="4">TOTAL TUNGGAKAN DESA {{ strtoupper($namaDesa) }}</td>
                    <td>Rp {{ number_format($summary[$namaDesa], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <hr style="border: 0; border-top: 1px dashed #ccc; margin: 20px 0;">
    
    @endforeach

</body>
</html>