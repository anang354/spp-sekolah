<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Validator;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\AlamatSambung as ModelAlamatSambung;

class AlamatSambung extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public $kelompok = '';
    public $desa = '';
    public $daerah = '';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static string $view = 'filament.pages.alamat-sambung';

    protected static ?int $navigationSort = 30;

    public function mount(): void
    {
        $this->form->fill(); // Opsional
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([
                        TextInput::make('kelompok')->required(),
                        TextInput::make('desa')->required(),
                        TextInput::make('daerah')->required(),
                    ])->columns(3)
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                ModelAlamatSambung::query()
                ->select('alamat_sambungs.*')
                ->selectSub(function ($query) {
                $query->from('siswas')
                    ->selectRaw('count(*)')
                    ->whereColumn('siswas.alamat_sambung_id', 'alamat_sambungs.id');
                }, 'siswas_count')

                // Sisa tagihan
                ->selectRaw('(
                    (select COALESCE(SUM(tagihans.jumlah_netto), 0)
                    from siswas
                    join tagihans on tagihans.siswa_id = siswas.id
                    where siswas.alamat_sambung_id = alamat_sambungs.id)
                    -
                    (select COALESCE(SUM(pembayarans.jumlah_dibayar), 0)
                    from siswas
                    join pembayarans on pembayarans.siswa_id = siswas.id
                    where siswas.alamat_sambung_id = alamat_sambungs.id)
                ) as sisa_tagihan')
                        )
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('kelompok')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('desa')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('daerah'),
                // Total Siswa
                \Filament\Tables\Columns\TextColumn::make('siswas_count')
                    ->label('Total Siswa')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()),
                \Filament\Tables\Columns\TextColumn::make('sisa_tagihan')
                ->label('Sisa Tagihan')
                ->numeric(decimalPlaces: 0)
                ->sortable()
                ->summarize([
                   Sum::make(),
                ]),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('Laporan Tagihan Per Desa')
                    ->url(route('laporan-alamat-sambung'))
                    ->icon('heroicon-o-document-text')
                    ->openUrlInNewTab(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->form([
                        TextInput::make('kelompok'),
                        TextInput::make('desa'),
                        TextInput::make('daerah'),
                    ])
                    ->action(function($record){
                        $record->kelompok = strtoupper($record->kelompok);
                        $record->desa = strtoupper($record->desa);
                        $record->daerah = strtoupper($record->daerah);
                        $record->save();
                    })
                    ->visible(function() {
                        return auth()->user()->role === 'admin' || auth()->user()->role === 'editor';
                    }),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->visible(function() {
                        return auth()->user()->role === 'admin';
                    }),
            ])
            ->filters([
                 SelectFilter::make('desa')
                ->label('Filter Desa')
                ->options(
                    ModelAlamatSambung::query()
                        ->distinct()
                        ->orderBy('desa')
                        ->pluck('desa', 'desa') // ['Desa A' => 'Desa A']
                )
                ->searchable()
                ->placeholder('Semua Desa'),
            ]);
    }
    protected function getFormActions(): array 
    {
        return [
            Action::make('save')->submit('save')
            ->label('Tambahkan')->icon('heroicon-o-plus'),
        ];
    }
    public function save(): void
    {
        $data = $this->form->getState();
        Validator::make(
        $data,
        [
            'kelompok' => 'required|string|max:255|unique:alamat_sambungs,kelompok',
            'desa'     => 'required|string|max:255',
            'daerah'   => 'required|string|max:255',
        ],
        [
            'kelompok.unique' => 'Kelompok tersebut sudah ada di dalam data.',
            'kelompok.required' => 'Field kelompok wajib diisi.',
            'desa.required' => 'Field desa wajib diisi.',
            'daerah.required' => 'Field daerah wajib diisi.',
        ]
    )->validate();
        // Ubah ke huruf kapital
        $data['kelompok'] = strtoupper($data['kelompok']);
        $data['desa']     = strtoupper($data['desa']);
        $data['daerah']   = strtoupper($data['daerah']);

        ModelAlamatSambung::create($data);
        $this->form->fill(); // reset form
        Notification::make() 
            ->success()
            ->title('Berasil menyimpan data')
            ->send(); 
    }
}
