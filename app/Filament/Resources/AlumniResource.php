<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Alumni;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AlumniResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AlumniResource\RelationManagers;
use App\Filament\Resources\AlumniResource\Widgets\TagihanAlumni;

class AlumniResource extends Resource
{
    protected static ?string $model = Alumni::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Alumni';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 12,
                ])
                ->schema([
                    TextInput::make('nama')->required()->columnSpan(6),
                    Radio::make('jenjang')->required()->options([
                        'smp' => 'SMP',
                        'sma' => 'SMA',
                    ])->columnSpan(2),
                    TextInput::make('tahun_lulus')->required()->columnSpan(4)->numeric(),
                ]),
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 2,
                ])
                ->schema([
                    TextInput::make('jumlah_tagihan')
                    ->required()
                    ->numeric()
                    ->label('Jumlah Tagihan')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $tagihan = (int) $get('jumlah_tagihan');
                        $diskon = (int) $get('jumlah_diskon');
                        $set('jumlah_netto', max($tagihan - $diskon, 0));
                    }),

                    TextInput::make('jumlah_diskon')
                    ->required()
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
                ->columnSpan('full')
                ->dehydrated() // agar tetap disimpan walau disabled
                ->hint(fn ($get) => 'Terbilang : ' . \App\Helpers\Terbilang::make((int) $get('jumlah_netto')))
                ->hintColor('gray'),
            TextInput::make('keterangan'),
            Radio::make('jenis_keuangan')
                ->options(\App\Models\Tagihan::JENIS_KEUANGAN),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->query(
            Alumni::query()
                ->withSum('pembayaranAlumni as total_pembayaran', 'jumlah_dibayar')
        )
            ->columns([
                TextColumn::make('nama')->searchable(),
                TextColumn::make('tahun_lulus')->sortable()->toggleable(),
                TextColumn::make('jenjang'),
                TextColumn::make('alamat')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('jumlah_tagihan')
                ->sortable()
                ->numeric(decimalPlaces: 0),
                TextColumn::make('jumlah_diskon')
                ->sortable()
                ->numeric(decimalPlaces: 0),
                TextColumn::make('jumlah_netto')
                ->sortable()
                ->numeric(decimalPlaces: 0),
                TextColumn::make('total_pembayaran')
                ->sortable()
                ->numeric(decimalPlaces: 0),
                TextColumn::make('sisa_tagihan')
                ->label('Sisa Tagihan')
                ->getStateUsing(fn ($record) => $record->jumlah_netto - ($record->total_pembayaran ?? 0))
                ->numeric(decimalPlaces: 0)
                ->prefix('Rp '),
                TextColumn::make('jenis_keuangan')
                    ->label('Keuangan'),
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
                SelectFilter::make('jenjang')
                    ->options([
                        'smp' => 'SMP',
                        'sma' => 'SMA',
                    ]),
                SelectFilter::make('jenis_keuangan')
                    ->options([
                        'sekolah' => 'sekolah',
                        'pondok' => 'pondok'
                    ]),
                SelectFilter::make('tahun_lulus')
                ->options(function () {
                    return Alumni::select('tahun_lulus')
                        ->distinct()
                        ->orderBy('tahun_lulus', 'desc')
                        ->pluck('tahun_lulus', 'tahun_lulus')
                        ->toArray();
                })->multiple()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->status === 'lunas'),
                Tables\Actions\Action::make('download-pdf')
                    ->color('success')
                    ->label('Rincian Tagihan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(function($record) {
                        $relativePath = $record->file;
                        // Periksa apakah file benar-benar ada di storage sebelum membuat URL
                        if (Storage::disk('public')->exists($relativePath)) {
                            return Storage::disk('public')->url($relativePath);
                        }
                        return null; // Jika file tidak ada, tombol tidak akan berfungsi atau bisa disembunyikan
                    })
                    ->openUrlInNewTab() // Membuka link di tab baru (biasanya akan memicu download untuk PDF)
                    // Opsional: Sembunyikan tombol jika file path kosong atau file tidak ditemukan
                    ->hidden(fn ($record): bool => empty($record->file) || !Storage::disk('public')->exists($record->file)),
                // <<< AKHIR TAMBAHAN AKSI >>>
                Tables\Actions\Action::make('riwayat-bayar')
                ->color('success')
                ->label('Riwayat Bayar')
                ->icon('heroicon-o-banknotes')
                ->url(function($record) {
                        return url('/admin/kartu-alumni/'.$record->id);
                    })
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\ImportAction::make()
                    ->importer(\App\Filament\Imports\AlumniImporter::class)
                    ->color('success')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn()=> auth()->user()->role === 'admin'),
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
            'index' => Pages\ListAlumnis::route('/'),
            'create' => Pages\CreateAlumni::route('/create'),
            'edit' => Pages\EditAlumni::route('/{record}/edit'),
            'activities' => Pages\ListAlumniActivities::route('/{record}/activities'),
        ];
    }
}
