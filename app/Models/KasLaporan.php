<?php

namespace App\Models;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasLaporan extends Model
{
    //
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    protected static function booted()
    {
        // Event saat Data Dibuat (Created) atau Diupdate (Updated)
        static::saved(function ($laporan) {
            
            // Cek kondisi: Harus closed DAN (baru dibuat ATAU is_closed baru saja berubah jadi true)
            if ($laporan->is_closed) {
                
                // Pastikan kita tidak terjebak loop infinite save
                // Kita cek apakah file sudah ada? Jika mau overwrite setiap edit, hapus pengecekan ini.
                // Disini saya buat logika: Selalu generate ulang jika di-save dalam keadaan closed
                
                self::generatePdf($laporan);
            }
        });
    }

    public static function generatePdf($laporan)
    {
        // A. HITUNG SALDO AWAL (Saldo per Tanggal Mulai - 1 Hari)
        // ------------------------------------------------------------------
        // Rumus: Total Masuk - Total Keluar (sebelum tanggal mulai laporan)
        
        $querySaldoAwal = KasTransaksi::where('tanggal_transaksi', '<', $laporan->tanggal_mulai);

        // 1. Saldo Awal Tunai
        $saldoAwalTunai = (clone $querySaldoAwal)->where('metode', 'tunai')
            ->sum(DB::raw("CASE WHEN jenis_transaksi = 'masuk' THEN jumlah ELSE -jumlah END"));

        // 2. Saldo Awal Non-Tunai
        $saldoAwalNonTunai = (clone $querySaldoAwal)->where('metode', 'non-tunai')
            ->sum(DB::raw("CASE WHEN jenis_transaksi = 'masuk' THEN jumlah ELSE -jumlah END"));

        // 3. Saldo Awal Gabungan
        $saldoAwalTotal = $saldoAwalTunai + $saldoAwalNonTunai;


        // B. AMBIL DATA TRANSAKSI PERIODE INI
        // ------------------------------------------------------------------
        $queryPeriode = KasTransaksi::whereBetween('tanggal_transaksi', [
            $laporan->tanggal_mulai, 
            $laporan->tanggal_tutup
        ])->orderBy('tanggal_transaksi', 'asc')->orderBy('id', 'asc'); // Order ID penting agar urutan stabil

        $trxTunai = (clone $queryPeriode)->where('metode', 'tunai')->get();
        $trxNonTunai = (clone $queryPeriode)->where('metode', 'non-tunai')->get();
        $trxSemua = $queryPeriode->get();


        // C. HITUNG SALDO BERJALAN (Inject ke dalam Collection)
        // ------------------------------------------------------------------
        // Kita buat fungsi kecil untuk loop perhitungan
        
        $processRunningBalance = function($transactions, $startingBalance) {
            $currentBalance = $startingBalance;
            foreach ($transactions as $trx) {
                if ($trx->jenis_transaksi == 'masuk') {
                    $currentBalance += $trx->jumlah;
                } else {
                    $currentBalance -= $trx->jumlah;
                }
                // Simpan saldo saat ini ke objek transaksi (temporary attribute)
                $trx->saldo_berjalan_pdf = $currentBalance;
            }
            return $transactions;
        };

        // Proses ketiga tabel
        $trxTunai = $processRunningBalance($trxTunai, $saldoAwalTunai);
        $trxNonTunai = $processRunningBalance($trxNonTunai, $saldoAwalNonTunai);
        $trxSemua = $processRunningBalance($trxSemua, $saldoAwalTotal);


        // D. GENERATE PDF
        // ------------------------------------------------------------------
        $pdf = Pdf::loadView('templates.laporan-kas', [
            'laporan' => $laporan,
            
            // Kirim Data Transaksi
            'transaksiTunai' => $trxTunai,
            'transaksiNonTunai' => $trxNonTunai,
            'transaksiSemua' => $trxSemua,
            
            // Kirim Saldo Awal untuk ditampilkan di baris pertama
            'saldoAwalTunai' => $saldoAwalTunai,
            'saldoAwalNonTunai' => $saldoAwalNonTunai,
            'saldoAwalTotal' => $saldoAwalTotal,
        ]);

        // ... (kode simpan file sama seperti sebelumnya) ...
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $fileName = "laporan-buku-kas-{$timestamp}.pdf";
        $path = "laporan-kas/{$fileName}";

        Storage::disk('public')->put($path, $pdf->output());
        $laporan->updateQuietly(['nama_file' => $path]);
    }
}
