<?php

namespace App\Filament\Actions\Siswas;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
                    //$totalTagihan = $siswa->tagihans()->sum('jumlah_netto');;

                    // Hitung total dibayar (semua pembayaran siswa)
                    //$totalDibayar = $siswa->pembayaran()->sum('jumlah_dibayar');

                    // Hitung sisa tagihan
                    //$sisaTagihan = max($totalTagihan - $totalDibayar, 0); // Hindari negatif
                    
                    $filePath =$folderPath.'/data-pembayaran-'.$siswa->nama.'-'.$randomString.'.pdf';

                    $totalTagihanSekolah = $siswa->tagihans()
                        ->where('jenis_keuangan', 'sekolah')
                        ->sum('jumlah_netto');

                    $totalDibayarSekolah = $siswa->pembayaran()
                        ->whereHas('tagihan', fn ($query) =>
                            $query->where('jenis_keuangan', 'sekolah')
                        )
                        ->sum('jumlah_dibayar');

                    $sisaSekolah = max($totalTagihanSekolah - $totalDibayarSekolah, 0);

                    // 2. Sisa Tagihan Jenis 'pondok'
                    $totalTagihanPondok = $siswa->tagihans()
                        ->where('jenis_keuangan', 'pondok')
                        ->sum('jumlah_netto');

                    $totalDibayarPondok = $siswa->pembayaran()
                        ->whereHas('tagihan', fn ($query) =>
                            $query->where('jenis_keuangan', 'pondok')
                        )
                        ->sum('jumlah_dibayar');

                    $sisaPondok = max($totalTagihanPondok - $totalDibayarPondok, 0);
                    // Simpan ke tabel alumnis
                    DB::beginTransaction();
                    try {
                        if($sisaSekolah !== 0) {
                            \App\Models\Alumni::create([
                                'nama' => $siswa->nama,
                                'jenjang' => $siswa->kelas->jenjang ?? '-',
                                'tahun_lulus' => now()->year,
                                'jumlah_tagihan' => $sisaSekolah,
                                'jumlah_diskon' => 0,
                                'jumlah_netto' => $sisaSekolah,
                                'status' => 'baru',
                                'file' => $filePath,
                                'keterangan' => 'Dipindahkan dari siswa aktif',
                                'alamat' => $siswa->alamatSambung->kelompok.'/'.$siswa->alamatSambung->desa.'/'.$siswa->alamatSambung->daerah,
                                'jenis_keuangan' => 'sekolah'
                            ]);
                            DB::commit();
                        }
                        if($sisaPondok !== 0)
                        {
                            \App\Models\Alumni::create([
                                'nama' => $siswa->nama,
                                'jenjang' => $siswa->kelas->jenjang ?? '-',
                                'tahun_lulus' => now()->year,
                                'jumlah_tagihan' => $sisaPondok,
                                'jumlah_diskon' => 0,
                                'jumlah_netto' => $sisaPondok,
                                'status' => 'baru',
                                'file' => $filePath,
                                'keterangan' => 'Dipindahkan dari siswa aktif',
                                'alamat' => $siswa->alamatSambung->kelompok.'/'.$siswa->alamatSambung->desa.'/'.$siswa->alamatSambung->daerah,
                                'jenis_keuangan' => 'pondok'
                            ]);
                        DB::commit();
                        }
                        $dataSiswa = \App\Models\Siswa::where('id',$siswa->id)->with(['tagihans', 'pembayaran', 'kelas', 'alamatSambung'])->first()->toArray();
                        $path = public_path().'/images/logo-sma.jpg';
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_get_contents($path);
                        $image = 'data:image/'.$type.';base64,'.base64_encode($data);
                        $pdf = Pdf::loadView('templates.kartu-tagihan-alumni',[
                            'siswa' => $dataSiswa,
                            'logo' => $image
                        ])->save(Storage::disk('public')->path($filePath));
                    } catch(\Exception $e) {
                        DB::rollBack();
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
