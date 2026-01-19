<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagihanResource\Pages;
use App\Filament\Resources\TagihanResource\RelationManagers;
use App\Models\Tagihan;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TagihanResource extends Resource
{
    protected static ?string $model = Tagihan::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                    return $record->daftar_biaya.' '. $date->translatedFormat('F Y');
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
            ]);
    }

    public static function table(Table $table): Table
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
            'jenis_keuangan',
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
            TextColumn::make('daftar_biaya'),
            TextColumn::make('siswa.nama')->searchable(),
            TextColumn::make('siswa.kelas.nama_kelas')->toggleable(),
            TextColumn::make('siswa.kelas.jenjang')->label('Jenjang')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('jatuh_tempo')->date('d F Y')->toggleable(isToggledHiddenByDefault: true),
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
            TextColumn::make('jenis_keuangan')
                ->badge()
                ->label('Jenis Keuangan')
                ->color(fn (string $state): string => match ($state) {
                    'pondok' => 'warning',
                    'sekolah' => 'success',
                })
                ->toggleable(),
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
                ->options(
                    \App\Models\Kelas::query()
                        ->distinct()
                        ->orderBy('jenjang')
                        ->pluck('jenjang', 'jenjang')
                )
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    if ($data['value']) {
                        return $query->whereHas('siswa.kelas', function ($q) use ($data) {
                            $q->where('jenjang', $data['value']);
                        });
                    }

                    return $query;
                }),
            SelectFilter::make('status')
                ->options([
                    'angsur' => 'angsur',
                    'lunas' => 'lunas',
                    'baru' => 'baru',
                ])
                ->multiple(),
            SelectFilter::make('jenis_keuangan')
                ->options([
                    'sekolah' => 'sekolah',
                    'pondok' => 'pondok',
                ]),

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
                ->visible(function () {
                    return auth()->user()->role === 'admin' || auth()->user()->role === 'editor';
                }),
        ])
        ->actions([
            //
            \Filament\Tables\Actions\EditAction::make()
                ->modal()
                ->modalHeading(fn ($record) => 'Edit Data: ' . $record->name)
                ->modalWidth('xl') // optional: bisa 'lg', 'xl', '4xl'
                ->slideOver()
                ->visible(function ($record) {
                    return $record->status !== 'lunas' && in_array(auth()->user()->role, ['admin', 'editor']);
                }),
        ])
        ->bulkActions([
            \Filament\Tables\Actions\BulkActionGroup::make([
                \Filament\Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn()=> auth()->user()->role === 'admin'),
                \App\Filament\Actions\Tagihans\BroadcastTagihanAction::make(1, 'Broadcast Tagihan'),
                \App\Filament\Actions\Tagihans\BroadcastTagihanAction::make(2, 'Follow Up Tagihan'),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTagihans::route('/'),
            'create' => Pages\CreateTagihan::route('/create'),
            'edit' => Pages\EditTagihan::route('/{record}/edit'),
            'activities' => Pages\ListTagihanActivities::route('/{record}/activities'),
        ];
    }
}
