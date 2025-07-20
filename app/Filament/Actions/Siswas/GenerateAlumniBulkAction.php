<?php

namespace App\Filament\Actions\Siswas;

use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class GenerateAlumniBulkAction 
{
    public static function make() : BulkAction
    {
        return BulkAction::make('generate_alumni')
            ->icon('heroicon-o-academic-cap')
            ->color('primary')
            ->label('Pindahkan ke Alumni')
            ->action(function (Collection $records) {
                foreach ($records as $siswa) {
        // Hitung total tagihan netto (semua tagihan siswa)
        $totalTagihan = $siswa->tagihans()->sum('jumlah_netto');

        // Hitung total dibayar (semua pembayaran siswa)
        $totalDibayar = $siswa->pembayaran()->sum('jumlah_dibayar');

        // Hitung sisa tagihan
        $sisaTagihan = max($totalTagihan - $totalDibayar, 0); // Hindari negatif

        // Simpan ke tabel alumnis
        try {
            \App\Models\Alumni::create([
                'nama' => $siswa->nama,
                'jenjang' => $siswa->kelas->jenjang ?? '-',
                'tahun_lulus' => now()->year,
                'jumlah_tagihan' => $sisaTagihan,
                'jumlah_diskon' => 0,
                'jumlah_netto' => $sisaTagihan,
                'status' => 'baru',
                'keterangan' => 'Dipindahkan dari siswa aktif',
            ]);
        } catch(\Exception $e) {
            Notification::make()
                ->title('Generate Alumni Gagal!')
                ->danger()
                ->send();
        }
    }

    // (Opsional) Kirim notifikasi
    Notification::make()
        ->title('Data Alumni berhasil dibuat')
        ->success()
        ->send();
            });
    }
}