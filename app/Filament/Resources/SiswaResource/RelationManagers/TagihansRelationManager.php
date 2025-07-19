<?php

namespace App\Filament\Resources\SiswaResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Tagihan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class TagihansRelationManager extends RelationManager
{
    protected static string $relationship = 'tagihans';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('periode_bulan')
                ->options(Tagihan::BULAN),
                Select::make('periode_tahun')
                ->options(Tagihan::TAHUN),
                DatePicker::make('jatuh_tempo')->label('Tanggal Jatuh Tempo')->required()->columnSpan('full'),
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
                TextInput::make('daftar_biaya'),
                TextInput::make('daftar_diskon'),
                Select::make('status')->required()
                ->options([
                    'baru' => 'baru',
                    'lunas' => 'lunas',
                    'angsur' => 'angsur',
                ]),
                TextInput::make('keterangan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
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
                TextColumn::make('jatuh_tempo')->date('d F Y'),
                TextColumn::make('jumlah_tagihan')
                    ->description(fn (\App\Models\Tagihan $record): string => $record->daftar_biaya)
                    ->label('Jumlah Tagihan')
                    ->prefix('Rp. ')
                    ->numeric(decimalPlaces: 0)
                    ->summarize(Sum::make()),
                TextColumn::make('jumlah_diskon')
                ->description(fn (\App\Models\Tagihan $record): string => $record->daftar_diskon)
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
                    ->getStateUsing(fn ($record) => 'Rp ' . number_format($record->total_pembayaran, 0, ',', '.')),
                TextColumn::make('sisa_tagihan')
                    ->label('Sisa Tagihan')
                    ->getStateUsing(fn ($record) => 'Rp ' . number_format($record->sisa_tagihan, 0, ',', '.')),
                TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'baru' => 'info',
                    'lunas' => 'success',
                    'angsur' => 'warning',
                }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
