<?php

namespace App\Filament\Resources\TagihanResource\Widgets;

use Carbon\Carbon;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TagihanBelumDibayarWidget extends BaseWidget
{
    
    protected function getStats(): array 
    {
        $now = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // 🔹 Total Pembayaran Bulan Ini untuk Sekolah
        $totalSekolah = DB::table('pembayarans')
            ->join('tagihans', 'pembayarans.tagihan_id', '=', 'tagihans.id')
            ->whereBetween('pembayarans.tanggal_pembayaran', [$startOfMonth, $endOfMonth])
            ->where('tagihans.jenis_keuangan', 'sekolah')
            ->sum('pembayarans.jumlah_dibayar');

        // 🔸 Total Pembayaran Bulan Ini untuk Pondok
        $totalPondok = DB::table('pembayarans')
            ->join('tagihans', 'pembayarans.tagihan_id', '=', 'tagihans.id')
            ->whereBetween('pembayarans.tanggal_pembayaran', [$startOfMonth, $endOfMonth])
            ->where('tagihans.jenis_keuangan', 'pondok')
            ->sum('pembayarans.jumlah_dibayar');


        $bulan = $now->month;
        $tahun = $now->year;
        $tagihan  = Tagihan::query()
            ->where(function ($query) use ($bulan, $tahun) {
                $query->where('periode_tahun', '<', $tahun)
                      ->orWhere(function ($q) use ($bulan, $tahun) {
                          $q->where('periode_tahun', $tahun)
                            ->where('periode_bulan', '<=', $bulan);
                      });
            })
            ->with('pembayaran')
            ->get();
        // $totalTagihan = $tagihan->sum('jumlah_netto');
        // $totalDibayar = $tagihan->flatMap->pembayaran->sum('jumlah_dibayar');
        // $sisa = $totalTagihan - $totalDibayar;
        $totalTagihanPerKeuangan = $tagihan->groupBy('jenis_keuangan') // Kelompokkan koleksi berdasarkan kolom 'jenjang'
                                 ->map(function ($tagihanGroup) {
                                     // Untuk setiap grup (misal grup SMP, grup SMA), jumlahkan 'jumlah_tagihan'
                                     return $tagihanGroup->sum('jumlah_netto');
                                 });
        $totalPembayaranPerKeuangan = $tagihan->groupBy('jenis_keuangan') // Kelompokkan koleksi berdasarkan kolom 'jenjang'
                                 ->map(function ($tagihanGroup) {
                                     // Untuk setiap grup (misal grup SMP, grup SMA), jumlahkan 'jumlah_tagihan'
                                     return $tagihanGroup->flatMap->pembayaran->sum('jumlah_dibayar');
                                 });
        $totalTagihanSekolah = $totalTagihanPerKeuangan['sekolah'] ?? 0; // Menggunakan null coalescing operator jika jenjang tidak ada
        $totalTagihanPondok = $totalTagihanPerKeuangan['pondok'] ?? 0;
        $totalPembayaranSekolah = $totalPembayaranPerKeuangan['sekolah'] ?? 0;
        $totalPembayaranPondok = $totalPembayaranPerKeuangan['pondok'] ?? 0;

        $sisaTagihanSekolah = $totalTagihanSekolah - $totalPembayaranSekolah;
        $sisaTagihanPondok = $totalTagihanPondok - $totalPembayaranPondok;

        return [
            Stat::make("Total Pembayaran Bulan {$now->translatedFormat('F Y')}", 'Rp ' . number_format($totalSekolah, 0, ',', '.'))
            ->description('Pembayaran masuk sekolah')
            ->color('success'),
            Stat::make("Total Pembayaran Bulan {$now->translatedFormat('F Y')}", 'Rp ' . number_format($totalPondok, 0, ',', '.'))
            ->description('Pembayaran masuk pondok')
            ->color('info'),
            Stat::make("Total Tagihan s.d {$now->translatedFormat('F Y')}", 'Rp ' . number_format($sisaTagihanSekolah, 0, ',', '.'))
            ->description('Total Tunggakan / tagihan sekolah')
            ->color('danger'),
            Stat::make("Total Tagihan s.d {$now->translatedFormat('F Y')}", 'Rp ' . number_format($sisaTagihanPondok, 0, ',', '.'))
            ->description('Total Tunggakan / tagihan pondok')
            ->color('warning'),
        ];
    }
}
