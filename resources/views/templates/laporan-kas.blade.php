<!DOCTYPE html>
<html>
<head>
    <title>Laporan Buku Kas</title>
    <style>
        body { font-family: sans-serif; font-size: 9pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 5px; font-size: 11pt; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #e6e6e6; }
        .badge-tunai { color: green; }
        .badge-non-tunai { color: blue; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $laporan->nama_laporan }}</h2>
        <p>Periode: {{ \Carbon\Carbon::parse($laporan->tanggal_mulai)->format('d M Y') }} - {{ \Carbon\Carbon::parse($laporan->tanggal_tutup)->format('d M Y') }}</p>
    </div>

    <div class="section-title">1. Riwayat Transaksi Tunai</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-saldo-awal">
                <td colspan="4" class="text-right fw-bold">Saldo Awal (Pindahan)</td>
                <td class="text-right fw-bold">{{ number_format($saldoAwalTunai, 0, ',', '.') }}</td>
            </tr>
            @foreach($transaksiTunai as $trx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y') }}</td>
                <td>{{ $trx->keterangan }}</td>
                <td class="text-right">{{ $trx->jenis_transaksi == 'masuk' ? number_format($trx->jumlah, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $trx->jenis_transaksi == 'keluar' ? number_format($trx->jumlah, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($trx->saldo_berjalan_pdf, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">2. Riwayat Transaksi Non-Tunai</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-saldo-awal">
                <td colspan="4" class="text-right fw-bold">Saldo Awal (Pindahan)</td>
                <td class="text-right fw-bold">{{ number_format($saldoAwalNonTunai, 0, ',', '.') }}</td>
            </tr>
            @foreach($transaksiNonTunai as $trx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y') }}</td>
                <td>{{ $trx->keterangan }}</td>
                <td class="text-right">{{ $trx->jenis_transaksi == 'masuk' ? number_format($trx->jumlah, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $trx->jenis_transaksi == 'keluar' ? number_format($trx->jumlah, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($trx->saldo_berjalan_pdf, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">3. Riwayat Gabungan (Semua Transaksi)</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Metode</th>
                <th>Keterangan</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th style="width: 18%">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-saldo-awal">
                <td colspan="5" class="text-right fw-bold">Saldo Awal (Pindahan)</td>
                <td class="text-right fw-bold">{{ number_format($saldoAwalTotal, 0, ',', '.') }}</td>
            </tr>
            @foreach($transaksiSemua as $trx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y') }}</td>
                <td class="text-center">
                    <span class="{{ $trx->metode == 'tunai' ? 'badge-tunai' : 'badge-non-tunai' }}">
                        {{ ucfirst($trx->metode) }}
                    </span>
                </td>
                <td>{{ $trx->keterangan }}</td>
                <td class="text-right">{{ $trx->jenis_transaksi == 'masuk' ? number_format($trx->jumlah, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $trx->jenis_transaksi == 'keluar' ? number_format($trx->jumlah, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($trx->saldo_berjalan_pdf, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="text-right">Total Saldo Akhir</td>
                <td class="text-right">{{ number_format($laporan->total_saldo, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>