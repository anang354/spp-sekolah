<?php

namespace App\Filament\Actions\Siswas;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Facades\Storage;
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

                $folderPath = 'data-alumni';
                if (!Storage::disk('public')->exists($folderPath)) {
                    Storage::disk('public')->makeDirectory($folderPath, 0775, true, true);
                }
                foreach ($records as $siswa) {
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $charactersLength = strlen($characters);
                    $randomString = '';

                    for ($i = 0; $i < 6; $i++) {
                        $randomString .= $characters[random_int(0, $charactersLength - 1)];
                    }
        // Hitung total tagihan netto (semua tagihan siswa)
        $totalTagihan = $siswa->tagihans()->sum('jumlah_netto');

        // Hitung total dibayar (semua pembayaran siswa)
        $totalDibayar = $siswa->pembayaran()->sum('jumlah_dibayar');

        // Hitung sisa tagihan
        $sisaTagihan = max($totalTagihan - $totalDibayar, 0); // Hindari negatif
        $filePath =$folderPath.'/data-pembayaran-'.$siswa->nama.'-'.$randomString.'.pdf';

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
                'file' => $filePath,
                'keterangan' => 'Dipindahkan dari siswa aktif',
            ]);
            $dataSiswa = \App\Models\Siswa::where('id',$siswa->id)->with(['tagihans', 'pembayaran', 'kelas'])->first()->toArray();
            $path = public_path().'/images/logo-sekolah.jpg';
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $image = 'data:image/'.$type.';base64,'.base64_encode($data);
            $pdf = Pdf::loadView('templates.kartu-tagihan-alumni',[
                'siswa' => $dataSiswa,
                'logo' => $image
            ])->save(Storage::disk('public')->path($filePath));
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
