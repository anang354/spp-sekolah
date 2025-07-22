<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\Tagihan;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\TagihanResource\Widgets\TagihanBelumDibayarWidget;

class TagihanSiswa extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static string $view = 'filament.pages.tagihan';

    protected static ?int $navigationSort = 6;

    protected function getHeaderWidgets(): array
    {
        return [
            TagihanBelumDibayarWidget::class, // Add your widget class here
        ];
    }

    public function table(Table $table): Table
    {
    return $table
        ->paginated([10, 25, 50, 100, 'all'])
        ->defaultPaginationPageOption(25)
        ->query(
            Tagihan::query()
            ->with('pembayaran')
            ->selectRaw('
            tagihans.*, 
            (SELECT COALESCE(SUM(jumlah_dibayar), 0) FROM pembayarans WHERE pembayarans.tagihan_id = tagihans.id) as total_dibayar,
            (jumlah_netto - (SELECT COALESCE(SUM(jumlah_dibayar), 0) FROM pembayarans WHERE pembayarans.tagihan_id = tagihans.id)) as sisa_tagihan
        ')
            ->orderByDesc('created_at')
            )
         ->groups([
            'status',
            'periode_bulan',
            'periode_tahun',
        ])
        ->columns([
            // Kolom Periode
            TextColumn::make('periode') // Anda bisa memberi nama kolom ini 'periode' atau apa pun
                ->label('Periode Tagihan')
                ->getStateUsing(function (Tagihan $record): string {
                    // Buat objek Carbon dari periode_tahun dan periode_bulan
                    // Asumsi periode_bulan adalah 1-12
                    $date = Carbon::createFromDate($record->periode_tahun, $record->periode_bulan, 1);
                    // Format ke 'Nama Bulan Tahun' (misal: Januari 2025)
                    // 'F' untuk nama bulan lengkap, 'Y' untuk tahun 4 digit
                    return $date->translatedFormat('F Y');
                }),
            TextColumn::make('siswa.nama')->searchable(),
            TextColumn::make('siswa.kelas.nama_kelas')->toggleable(),
            TextColumn::make('siswa.kelas.jenjang')->label('Jenjang')->toggleable(),
            TextColumn::make('jatuh_tempo')->date('d F Y')->toggleable(),
            TextColumn::make('jumlah_tagihan')
                ->label('Jumlah Tagihan')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
            TextColumn::make('jumlah_diskon')
                ->label('Diskon')
                ->prefix('- Rp. ')
                ->color('danger')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
            TextColumn::make('jumlah_netto')
                ->label('Tagihan setelah diskon')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
            TextColumn::make('total_dibayar')
                ->label('Total Dibayar')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
            TextColumn::make('sisa_tagihan')
                ->label('Sisa Tagihan')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
            TextColumn::make('status')
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'baru' => 'info',
                'lunas' => 'success',
                'angsur' => 'warning',
            })
            ->icons([
                'heroicon-m-check-badge' => 'lunas',
                'heroicon-m-arrow-path' => 'angsur',
                'heroicon-m-clock' => 'baru',
            ]),

        ])
        ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Filter Berdasarkan Kelas')
                    ->relationship('siswa.kelas', 'nama_kelas', fn (Builder $query) => $query->orderBy('nama_kelas')) // Relasi nested
                    ->preload()
                    ->searchable(),
                SelectFilter::make('jenjang')
                    ->label('Filter Berdasarkan Jenjang')
                    ->relationship('siswa.kelas', 'jenjang', fn (Builder $query) => $query->orderBy('nama_kelas')) // Relasi nested
                    ->preload()
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        'angsur' => 'angsur',
                        'lunas' => 'lunas',
                        'baru' => 'baru',
                    ])
                    ->multiple(),

                // <<< FILTER BERDASARKAN PERIODE BULAN >>>
                SelectFilter::make('periode_bulan')
                    ->label('Filter Berdasarkan Bulan')
                    ->multiple()
                    ->options(Tagihan::BULAN)
                    ->attribute('periode_bulan'), // Kolom database yang difilter
                // <<< AKHIR FILTER BULAN >>>

                // <<< FILTER BERDASARKAN PERIODE TAHUN >>>
                SelectFilter::make('periode_tahun')
                    ->label('Filter Berdasarkan Tahun')
                    ->options(Tagihan::TAHUN)
                    ->attribute('periode_tahun'), // Kolom database yang difilter
                // <<< AKHIR FILTER TAHUN >>>
            ])
        ->headerActions([
            \App\Filament\Actions\Tagihans\CreateAction::make()
                ->visible(fn()=> auth()->user()->level === 'admin' || auth()->user()->level === 'editor'),
        ])
        ->actions([
            //
            \Filament\Tables\Actions\EditAction::make()
            ->visible(fn($record) => 
            $record->status !== 'lunas' &&
            auth()->user()->level === 'admin' || auth()->user()->level === 'editor'
            )
            ->form([
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 3,
                ])
                ->schema([
                    Placeholder::make('siswa')
                    ->content(fn ($record): string => $record->siswa->nama),
                    Placeholder::make('kelas')
                    ->content(fn ($record): string => $record->siswa->kelas->nama_kelas),
                    Placeholder::make('periode')
                    ->content(function ($record): string {
                        $date = Carbon::createFromDate($record->periode_tahun, $record->periode_bulan, 1);
                        // Format ke 'Nama Bulan Tahun' (misal: Januari 2025)
                        // 'F' untuk nama bulan lengkap, 'Y' untuk tahun 4 digit
                        return $date->translatedFormat('F Y');
                    }),
                ]),
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 2,
                ])
                ->schema([
                    TextInput::make('jumlah_tagihan')
                    ->numeric()
                    ->label('Jumlah Tagihan')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $tagihan = (int) $get('jumlah_tagihan');
                        $diskon = (int) $get('jumlah_diskon');
                        $set('jumlah_netto', max($tagihan - $diskon, 0));
                    }),

                    TextInput::make('jumlah_diskon')
                    ->numeric()
                    ->label('Jumlah Diskon')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $tagihan = (int) $get('jumlah_tagihan');
                        $diskon = (int) $get('jumlah_diskon');
                        $set('jumlah_netto', max($tagihan - $diskon, 0));
                    }),
                ]),
            TextInput::make('jumlah_netto')
                ->numeric()
                ->label('Jumlah Netto')
                ->disabled()
                ->dehydrated() // agar tetap disimpan walau disabled
                ->hint(fn ($get) => 'Terbilang : ' . \App\Helpers\Terbilang::make((int) $get('jumlah_netto')))
                ->hintColor('gray'),
                DatePicker::make('jatuh_tempo')->required(),
                Select::make('status')
                ->options([
                    'baru' => 'baru',
                    'angsur' => 'angsur',
                    'lunas' => 'lunas',
                ]),
            ]),
        ])
        ->bulkActions([
            \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn()=> auth()->user()->level === 'admin'),
                ]),
        ]);
    }

    
}
