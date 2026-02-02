<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\KasTransaksi;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\KasTransaksiResource\Pages;
use App\Filament\Resources\KasTransaksiResource\RelationManagers;

class KasTransaksiResource extends Resource
{
    protected static ?string $model = KasTransaksi::class;

    protected static ?string $navigationGroup = 'Buku Kas';
    protected static ?string $navigationLabel = 'Transaksi Kas';

    protected static ?string $navigationIcon = 'heroicon-o-fire';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                Forms\Components\Section::make('Model Transaksi Kas')->schema([
                    Forms\Components\TextInput::make('nomor_referensi')
                    ->default(function() {
                        $prefix = date('mY');
                        $random = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        return $prefix . $random;
                    })
                    ->readonly()
                    ->required()
                    ->unique(ignoreRecord: true),
                    Forms\Components\Radio::make('jenis_transaksi')
                    ->options([
                        'masuk' => 'Masuk',
                        'keluar' => 'Keluar',
                    ])
                    ->required(),
                    Forms\Components\Radio::make('metode')
                    ->options([
                        'tunai' => 'Tunai',
                        'non-tunai' => 'Non-Tunai',
                    ])
                    ->required(),
                ])->columns(3),
                Forms\Components\Select::make('kas_kategori_id')
                    ->relationship('kategori', 'nama_kategori')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nama_kategori')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_transaksi')
                    ->default(now())
                    ->required(),
                
                Forms\Components\TextInput::make('jumlah')
                    ->live(onBlur: true) // agar update terbilang secara live
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('terbilang', \App\Helpers\Terbilang::make((int) $state));
                    })
                    ->required()
                    ->numeric(),
                Forms\Components\Placeholder::make('terbilang')
                            ->label('Terbilang')
                            ->content(fn ($get) => $get('terbilang')),
                Forms\Components\TextInput::make('keterangan')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(function (Builder $query) use ($table) {
                // 1. Ambil Filter LANGSUNG dari Component Livewire (Pasti Akurat)
                $livewire = $table->getLivewire();
                
                // Mengakses properti public $tableFilters milik component
                $filters = $livewire->tableFilters; 
                
                $filterMetode = $filters['metode']['value'] ?? null;
                $filterDariTanggal = $filters['rentang_tanggal']['dari_tanggal'] ?? null;
                $filterSampaiTanggal = $filters['rentang_tanggal']['sampai_tanggal'] ?? null;

                // 2. Bangun Subquery Saldo
                $subQuery = DB::table('kas_transaksis as sub')
                    ->selectRaw("SUM(
                        CASE 
                            WHEN sub.jenis_transaksi = 'masuk' THEN sub.jumlah 
                            ELSE -sub.jumlah 
                        END
                    )")
                    // Logika dasar: Hitung history ke belakang
                    ->where(function ($q) {
                        $q->whereColumn('sub.tanggal_transaksi', '<', 'kas_transaksis.tanggal_transaksi')
                        ->orWhere(function ($q2) {
                            $q2->whereColumn('sub.tanggal_transaksi', '=', 'kas_transaksis.tanggal_transaksi')
                                ->whereColumn('sub.id', '<=', 'kas_transaksis.id');
                        });
                    });

                // 3. Terapkan Filter ke Subquery
                
                // A. Filter Metode
                if ($filterMetode) {
                    $subQuery->where('sub.metode', $filterMetode);
                }

                // B. Filter Tanggal (Start from 0 logic)
                if ($filterDariTanggal) {
                    $subQuery->whereDate('sub.tanggal_transaksi', '>=', $filterDariTanggal);
                }
                // Filter Sampai Tanggal (Opsional, demi konsistensi)
                if ($filterSampaiTanggal) {
                    $subQuery->whereDate('sub.tanggal_transaksi', '<=', $filterSampaiTanggal);
                }

                // 4. Inject Subquery ke Query Utama
                $query->select('kas_transaksis.*') // Pastikan select all dari tabel utama
                    ->selectSub($subQuery, 'saldo_berjalan');
                
                return $query;
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kategori.nama_kategori'),
                Tables\Columns\TextColumn::make('metode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tunai' => 'primary',
                        'non-tunai' => 'info',
                    }),
                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->date('d F Y'),
                Tables\Columns\TextColumn::make('nomor_referensi')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                // Tables\Columns\TextColumn::make('jenis_transaksi')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'masuk' => 'success',
                //         'keluar' => 'danger',
                //     }),
                // Tables\Columns\TextColumn::make('jumlah')
                //     ->numeric()
                //     ->sortable(),
                // Kolom Uang Masuk
            Tables\Columns\TextColumn::make('masuk')
                ->label('Masuk (Rp)')
                ->color('success')
                ->state(function (\Illuminate\Database\Eloquent\Model $record) {
                    return $record->jenis_transaksi === 'masuk' ? $record->jumlah : 0;
                })
                ->numeric(decimalPlaces: 0)
                ->summarize(
                    Summarizer::make()
                    ->label('Total Masuk')
                    ->numeric(decimalPlaces: 0)
                    ->using(fn ($query) => $query->where('jenis_transaksi', 'masuk')->sum('jumlah'))
                ),

            // Kolom Uang Keluar
            Tables\Columns\TextColumn::make('keluar')
                ->label('Keluar (Rp)')
                ->state(function (\Illuminate\Database\Eloquent\Model $record) {
                    return $record->jenis_transaksi === 'keluar' ? $record->jumlah : 0;
                })
                ->numeric(decimalPlaces: 0)
                ->color('danger')
                ->summarize(
                    Summarizer::make()
                        ->label('Total Keluar')
                        ->numeric(decimalPlaces: 0)
                        ->using(fn ($query) => $query->where('jenis_transaksi', 'keluar')->sum('jumlah')),
                        
                ),
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo_berjalan')
                ->label('Saldo')
                ->money('IDR')
                ->weight('bold') // Tebalkan agar terlihat seperti saldo akhir
                // Opsional: Matikan sorting agar user tidak mengacak urutan saldo
                ->sortable(false),
                Tables\Columns\TextColumn::make('user.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('metode')
                    ->options([
                        'tunai' => 'Tunai',
                        'non-tunai' => 'Non-Tunai',
                    ])
                    // PENTING: Saat filter aktif, query utama otomatis terfilter oleh Filament.
                    // Subquery di getEloquentQuery() akan menangkap nilai ini via request().
                    ->indicator('Metode'),
                \Filament\Tables\Filters\Filter::make('rentang_tanggal')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date) => $query->whereDate('tanggal_transaksi', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date) => $query->whereDate('tanggal_transaksi', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->toFormattedDateString();
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListKasTransaksis::route('/'),
            'create' => Pages\CreateKasTransaksi::route('/create'),
            'edit' => Pages\EditKasTransaksi::route('/{record}/edit'),
        ];
    }
}
