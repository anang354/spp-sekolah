<?php

namespace App\Filament\Pages;

use App\Models\Pembayaran;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use \Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MultiplePembayaran extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $model = Pembayaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static string $view = 'filament.pages.multiple-pembayaran';
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([]); // Or with specific data: $this->form->fill($this->record->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make()
                    ->columns([
                        'sm' => 4,
                        'xl' => 6,
                        '2xl' => 7,
                    ])
                    ->schema([
                        Radio::make('metode_pembayaran')
                            ->required()
                            ->options([
                                'tunai' => 'Tunai',
                                'transfer' => 'Transfer',
                            ])
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 1,
                            ]),
                        DatePicker::make('tanggal_pembayaran')
                            ->default(now())
                            ->required()
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 3,
                            ]),
                        TextInput::make('keterangan')
                            ->columnSpan([
                                'sm' => 'full',
                                'xl' => 2,
                                '2xl' => 3,
                            ]),
                    ]),
                Select::make('siswa_id')
                    ->label('Pilih Siswa')
                    ->options(\App\Models\Siswa::all()->pluck('nama', 'id')->toArray())
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('Tagihan', [])),

                Repeater::make('Tagihan')
                    ->addActionLabel('Tambah Tagihan yang akan dibayar')
                    ->label('Daftar Tagihan')
                    ->default([])
                    ->schema([
                        Select::make('tagihan_id')
                            ->label('Tagihan')
                            ->options(function (callable $get, callable $set, $state) {
                                $siswaId = $get('../../siswa_id');
                                if (!$siswaId) return [];

                                return \App\Models\Tagihan::where('siswa_id', $siswaId)
                                    ->whereColumn('jumlah_netto', '>', DB::raw('(SELECT COALESCE(SUM(jumlah_dibayar), 0) FROM pembayarans WHERE pembayarans.tagihan_id = tagihans.id)'))
                                    ->get()
                                    ->mapWithKeys(function ($tagihan) {
                                        $bulan = Carbon::createFromDate(null, $tagihan->periode_bulan, 1)->translatedFormat('F Y');
                                        $label = "{$tagihan->daftar_biaya} - {$bulan} - Rp. " . number_format($tagihan->sisa_tagihan, 0, ",", ".");
                                        return [$tagihan->id => $label];
                                    });
                            })
                            ->required(),

                        TextInput::make('jumlah_dibayar')
                            ->numeric()
                            ->live(debounce: 500)
                            ->hint(fn ($state) => 'Terbilang : ' . \App\Helpers\Terbilang::make((int) $state))
                            ->hintColor('gray')
                            ->required(),
                    ])
                    ->minItems(1)
                    ->columns(2),
                FileUpload::make('bukti_bayar')
                    ->disk('local')
                    ->directory('bukti-bayar')
                    ->downloadable() // <<< Penting: Mengizinkan file didownload dari Filament
                    ->previewable() // <<< Opsional: Memungkinkan pratinjau gambar atau PDF (jika didukung browser)
                    ->visibility('private')
            ]);
    }
    protected function getFormActions(): array
    {
        return [
            Action::make('save')->submit('save')
                ->label('Buat Pembayaran')->icon('heroicon-o-plus'),
        ];
    }
    public function simpan()
    {
        $nomorBayar = Pembayaran::generateNomorBayar();
        $data = $this->form->getState();
        foreach ($data['Tagihan'] as $bayar) {
            Pembayaran::create([
                'siswa_id' => $data['siswa_id'],
                'user_id' => auth()->user()->id,
                'tagihan_id' => $bayar['tagihan_id'],
                'jumlah_dibayar' => $bayar['jumlah_dibayar'],
                'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                'metode_pembayaran' => $data['metode_pembayaran'], // atau ambil dari input tambahan
                'keterangan' => $data['keterangan'] ?? null,
                'bukti_bayar' => $data['bukti_bayar'] ?? null,
                'nomor_bayar' => $nomorBayar,
            ]);
        }

        $this->form->fill([]);
        Notification::make('')
            ->title('Pembayaran Massal Berhasil')
            ->success()
            ->send();
        $this->redirect('/admin/pembayarans');

    }


}
