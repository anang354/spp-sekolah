<?php


namespace App\Filament\Actions\Tagihans;

use Carbon\Carbon;
use App\Models\Tagihan;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;

class CreateIndividualAction
{

    public static function make(): Action
    {
        return Action::make('create')->label('Buat Tagihan Baru')->icon('heroicon-o-plus')
        ->form([
            Section::make('')
            ->schema([
                Select::make('periode_bulan')
                ->multiple()
                ->options(Tagihan::BULAN),
                Select::make('periode_tahun')
                ->options(Tagihan::TAHUN),
                // DatePicker::make('jatuh_tempo')->label('Tanggal Jatuh Tempo')->required()->columnSpan('full'),
                TextInput::make('jumlah_tagihan')->numeric()->required()
                ->live(debounce: 500)
                ->afterStateUpdated(function (callable $set, callable $get) {
                    $tagihan = (int) $get('jumlah_tagihan');
                    $diskon = (int) $get('jumlah_diskon');
                    $set('jumlah_netto', max($tagihan - $diskon, 0));
                }),
                TextInput::make('jumlah_diskon')->numeric()->required()
                ->live(debounce: 500)
                ->afterStateUpdated(function (callable $set, callable $get) {
                    $tagihan = (int) $get('jumlah_tagihan');
                    $diskon = (int) $get('jumlah_diskon');
                    $set('jumlah_netto', max($tagihan - $diskon, 0));
                }),
                TextInput::make('jumlah_netto')->numeric()->required()->columnSpan('full')
                ->disabled()
                ->dehydrated() // agar tetap disimpan walau disabled
                ->hint(fn ($get) => 'Terbilang : ' . \App\Helpers\Terbilang::make((int) $get('jumlah_netto')))
                ->hintColor('gray'),
                Select::make('daftar_biaya')
                    ->required()
                    ->options(fn (RelationManager $livewire) =>
                    optional($livewire->getOwnerRecord()->kelas)->jenjang
                        ? \App\Models\Biaya::where('jenjang', $livewire->getOwnerRecord()->kelas->jenjang)
                        ->pluck('nama_biaya', 'nama_biaya')
                        ->toArray()
                        : []
                    ),
                TextInput::make('daftar_diskon'),
                Select::make('status')->required()
                ->options([
                    'baru' => 'baru',
                    'lunas' => 'lunas',
                    'angsur' => 'angsur',
                ]),
                Radio::make('jenis_keuangan')
                ->options(Tagihan::JENIS_KEUANGAN)
                ->required(),
                TextInput::make('keterangan')->columnSpan('full'),
            ])
            ->columns(2)
        ])
        ->action(function (array $data, RelationManager $livewire) {

            $siswaId = $livewire->getOwnerRecord()->id;
            // Pastikan tidak ada duplikat (untuk jaga-jaga)
            foreach($data['periode_bulan'] as $bulan) {
                $exists = Tagihan::where('siswa_id', $siswaId)
                ->where('periode_bulan', $bulan)
                ->where('periode_tahun', $data['periode_tahun'])
                ->where('daftar_biaya', $data['daftar_biaya'])
                ->exists();

            if ($exists) {
                Notification::make()
                    ->title('Tagihan gagal dibuat')
                    ->body('Periode yang dipilih sudah memiliki tagihan yang sama.')
                    ->danger()
                    ->send();
                return;
            }
            $tanggal = Carbon::createFromDate($data['periode_tahun'], $bulan, 1)->endOfMonth()->toDateString();
            // Simpan data
            Tagihan::create([
                'siswa_id' => $siswaId,
                'periode_bulan' => $bulan,
                'periode_tahun' => $data['periode_tahun'],
                'jatuh_tempo' => $tanggal,
                'jumlah_tagihan' => $data['jumlah_tagihan'],
                'jumlah_diskon' => $data['jumlah_diskon'],
                'jumlah_netto' => $data['jumlah_tagihan'] - $data['jumlah_diskon'],
                'daftar_biaya' => $data['daftar_biaya'],
                'daftar_diskon' => $data['daftar_diskon'],
                'status' => 'baru',
                'keterangan' => $data['keterangan']
            ]);
            }
            

            Notification::make()
                ->title('Tagihan berhasil dibuat')
                ->success()
                ->send();
        });

    }

}
