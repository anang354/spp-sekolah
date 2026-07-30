<?php


namespace App\Filament\Actions\Tagihans;

use App\Models\Biaya;
use App\Models\Siswa;
use App\Models\Tagihan;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CreateTagihanMultiple
{
    public static function make(): Action
    {
        return Action::make('create-tagihan-multiple')->label('Buat Tagihan Multiple')->icon('heroicon-o-plus')->color('info')
            ->form([
                Grid::make([
                    'md' => 1,
                    'lg' => 2,
                ])
                    ->schema([
                        Placeholder::make('')->content('Tagihan akan dibuat berdasarkan jenjang, kelas, dan biaya yang dipilih')->columnSpan('full'),
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
                        Select::make('biaya_id')
                        ->label('Biaya')
                        ->options(function (Get $get): array {
                            $jenjang = $get('jenjang');
                            $biaya = Biaya::where('jenjang', $jenjang)->pluck('nama_biaya', 'id')->toArray();
                            return $biaya;
                        })
                        ->multiple()->required(),
                    ])
            ])
            ->action(function (array $data){
                $jenjang = $data['jenjang'];
                $kelasIds = $data['kelas'];
                $biaya = Biaya::find($data['biaya_id']);
                $getSiswa = Siswa::where('is_active', true)
                    ->whereHas('kelas', function (Builder $query) use ($jenjang, $kelasIds) {
                        $query->where('jenjang', $jenjang)->whereIn('id', $kelasIds);
                    })->get();
                DB::beginTransaction();
                try {
                    $createdTagihanCount = 0;
                    $affectedStudentsCount = 0;
                    foreach ($getSiswa as $siswa) {
                        $isBoarding = $siswa->is_boarding ? 'boarding' : 'non-boarding';
                        $biayaItems = Biaya::where('jenjang', $jenjang)
                            ->whereIn('jenis_siswa', [$isBoarding, 'semua'])
                            ->whereIn('id', $data['biaya_id'])
                            ->get();

                        $createdForThisStudent = 0;
                        foreach ($biayaItems as $biayaItem) {
                            $check = \App\Models\Tagihan::where('siswa_id', $siswa->id)
                                ->where('daftar_biaya', $biayaItem->nama_biaya)
                                ->where('periode_bulan', $data['periode_bulan'])
                                ->where('periode_tahun', $data['periode_tahun'])
                                ->count();

                            if ($check === 0) {
                                $totalBiaya = $biayaItem->nominal;
                                $totalDiskon = 0;
                                $idsDiskon = [];

                                foreach ($siswa->diskon as $diskon) {
                                    if ($diskon->biaya->id === $biayaItem->id) {
                                        if ($diskon->tipe === 'nominal') {
                                            $totalDiskon += $diskon->nominal;
                                        } elseif ($diskon->tipe === 'persentase') {
                                            $diskonIs = $totalBiaya * ($diskon->persentase / 100);
                                            $totalDiskon += intval($diskonIs);
                                        }
                                        $idsDiskon[] = $diskon->id;
                                    }
                                }

                                $saveIdsDiskon = json_encode($idsDiskon);
                                $jumlahNetto = $totalBiaya - $totalDiskon;
                                $tanggal = Carbon::now()->addDays(7)->format('Y-m-d');
                                Tagihan::create([
                                    'siswa_id' => $siswa->id,
                                    'periode_bulan' => $data['periode_bulan'],
                                    'periode_tahun' => $data['periode_tahun'],
                                    'jatuh_tempo' => $tanggal,
                                    'jumlah_tagihan' => $totalBiaya,
                                    'jumlah_diskon' => $totalDiskon,
                                    'daftar_biaya' => $biayaItem->nama_biaya,
                                    'daftar_diskon' => $saveIdsDiskon,
                                    'jumlah_netto' => $jumlahNetto,
                                    'jenis_keuangan' => $biayaItem->jenis_keuangan,
                                    'status' => 'baru',
                                ]);

                                $createdTagihanCount++;
                                $createdForThisStudent++;
                            }
                        }

                        if ($createdForThisStudent > 0) {
                            $affectedStudentsCount++;
                        }
                    }
                    DB::commit();
                    $notif = 'Berhasil membuat '.$createdTagihanCount.' tagihan untuk '.$affectedStudentsCount.' siswa';
                    Notification::make()
                        ->title('Berhasil!')
                        ->body($notif)
                        ->success()
                        ->send();
                } catch(\Exception $e) {
                    DB::rollBack();
                    Notification::make()
                    ->title('Gagal!')
                    ->body('Gagal membuat Tagihan')
                    ->danger()
                    ->send();
                }
            });
    }
}
