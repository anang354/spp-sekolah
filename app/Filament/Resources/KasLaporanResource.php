<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasLaporanResource\Pages;
use App\Filament\Resources\KasLaporanResource\RelationManagers;
use App\Models\KasLaporan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KasLaporanResource extends Resource
{
    protected static ?string $model = KasLaporan::class;

    protected static ?string $navigationGroup = 'Buku Kas';

    protected static ?string $navigationLabel = 'Laporan Kas';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')->default(fn() => auth()->id()),
                Forms\Components\TextInput::make('nama_laporan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('catatan')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('tanggal_mulai')
                    ->helperText('Transaksi kas akan dicatat mulai dari tanggal ini.')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_tutup')
                ->afterOrEqual(fn () => \App\Models\KasLaporan::max('tanggal_tutup') ?? '2000-01-01')
                ->helperText('Semua transaksi sebelum dan pada tanggal ini akan dikunci permanen.')
                ->required(),
                Forms\Components\Hidden::make('saldo_akhir_tunai'),
                Forms\Components\Hidden::make('saldo_akhir_bank'),
                Forms\Components\Hidden::make('total_saldo'),
                Forms\Components\Toggle::make('is_closed')
                    ->label('Tutup Laporan')
                    ->helperText('Menandai laporan ini sebagai tutup buku. Setelah ditutup, laporan tidak dapat diubah lagi.')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_laporan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_tutup')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo_akhir_tunai')
                    ->prefix('Rp ')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo_akhir_bank')
                    ->prefix('Rp ')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_saldo')
                    ->prefix('Rp ')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_closed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListKasLaporans::route('/'),
            'create' => Pages\CreateKasLaporan::route('/create'),
            'edit' => Pages\EditKasLaporan::route('/{record}/edit'),
        ];
    }
}
