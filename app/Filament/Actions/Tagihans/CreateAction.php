<?php


namespace App\Filament\Actions\Tagihans;

use App\Models\Biaya;
use App\Models\Siswa;
use App\Models\Tagihan;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;

class CreateAction 
{
    public static function make(): Action 
    {
        return Action::make('create')->label('Buat Tagihan')->icon('heroicon-o-plus')
            ->form([
                Placeholder::make('')->content('Tagihan akan dibuat otomatis untuk seluruh siswa berdasarkan biaya dan diskon yang telah ditentukan'),
                Radio::make('jenjang')->options([
                    'smp' => 'SMP',
                    'sma' => 'SMA',
                ])->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('kelas', null)),
                Select::make('kelas')->options(function (Get $get): array {
                    $jenjang = $get('jenjang');
                       return \App\Models\Kelas::where('jenjang', $jenjang)->pluck('nama_kelas', 'id')->toArray();
                })->required()
                ->multiple(),
                Select::make('periode_bulan')->options([
                    Tagihan::BULAN
                ])->required(),
                Select::make('periode_tahun')->options([
                    Tagihan::TAHUN
                ])->required(),
                DatePicker::make('jatuh_tempo')->label('Tanggal Jatuh Tempo')->required()
            ])
            ->action(function (array $data){
                $jenjang = $data['jenjang'];
                $kelasIds = $data['kelas'];
                $getSiswa = Siswa::where('is_active', true)
                ->whereHas('kelas', function (Builder $query) use ($jenjang, $kelasIds) {
                    $query->where('jenjang', $jenjang)->whereIn('id', $kelasIds);
                })->get();
                try {
                    $hitung = 0;
                    foreach($getSiswa as $siswa) {
                        $isBoarding = $siswa->is_boarding ? 'boarding' : 'non-boarding';
                        $biaya = Biaya::where('jenjang', $jenjang)
                                ->whereIn('jenis_siswa', [$isBoarding, 'semua'])
                                ->get();
                        $totalBiaya = 0;
                        $idsBiaya = [];
                        foreach ($biaya as $item) {
                            // Menjumlahkan total biaya
                            $totalBiaya += $item->nominal;
                            // Menyimpan id ke dalam array string
                            $idsBiaya[] = (string) $item->id; // Menggunakan (string) untuk memastikan tipe string
                        }
                        $saveIdsBiaya = implode(', ', $idsBiaya);
                        $totalDiskon = 0;
                        $idsDiskon = [];
                        foreach ($siswa->diskon as $diskon) {
                            if($diskon->berlaku_tagihan === 'sebelum') {
                                if($diskon->tipe === 'nominal') 
                                {
                                    $totalDiskon += $diskon->nominal;
                                } elseif($diskon->tipe === 'persentase')
                                {
                                    $diskonIs = $totalBiaya * ($diskon->persentase / 100);
                                    $totalDiskon += intval($diskonIs);
                                }
                                $idsDiskon[] = (string) $diskon->id;
                            }
                        }
                        $saveIdsDiskon = implode(', ', $idsDiskon);
                        $jumlahNetto = $totalBiaya - $totalDiskon;

                        Tagihan::create([
                            'siswa_id' => $siswa->id,
                            'periode_bulan' => $data['periode_bulan'],
                            'periode_tahun' => $data['periode_tahun'],
                            'jatuh_tempo' => $data['jatuh_tempo'],
                            'jumlah_tagihan' => $totalBiaya,
                            'jumlah_diskon' => $totalDiskon,
                            'daftar_biaya' => $saveIdsBiaya,
                            'daftar_diskon' => $saveIdsDiskon,
                            'jumlah_netto' => $jumlahNetto,
                            'status' => 'baru'
                        ]);
                        $hitung++;
                    }
                    $notif = 'Berhasil membuat tagihan untuk '.$hitung.' siswa';
                    Notification::make()
                    ->title('Berhasil!')
                    ->body($notif)
                    ->success()
                    ->send();
                } catch(\Exception $e) {
                    Notification::make()
                    ->title('Gagal!')
                    ->body('Gagal membuat Tagihan')
                    ->danger()
                    ->send();
                }
            });
    }
}