<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Siswa;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\SiswaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SiswaResource\RelationManagers;
use App\Filament\Resources\SiswaResource\RelationManagers\TagihansRelationManager;
use App\Filament\Resources\SiswaResource\RelationManagers\PembayaranRelationManager;

class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nisn')->numeric(),
                Select::make('kelas_id')->required()->options(\App\Models\Kelas::all()->pluck('nama_kelas', 'id')),
                TextInput::make('nama')->required(),
                Radio::make('jenis_kelamin')->required()
                ->options([
                    'laki-laki'  => 'Laki-laki',
                    'perempuan'  => 'Perempuan',
                ]),
                TextInput::make('nomor_hp')->numeric(),
                Radio::make('is_boarding')
                    ->label('Apakah siswa boarding?')
                    ->required()
                    ->boolean(),
                TextInput::make('nama_wali'),
                Radio::make('is_active')->boolean(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nisn')->searchable(),
                TextColumn::make('nama')->searchable(),
                TextColumn::make('jenis_kelamin')
                   ->formatStateUsing(fn ($state) => ucwords($state)),
                TextColumn::make('kelas.nama_kelas'),
                TextColumn::make('kelas.jenjang')
                ->badge()
                ->label('Jenjang')
                ->color(fn (string $state): string => match ($state) {
                    'smp' => 'info',
                    'sma' => 'primary',
                })
                ->formatStateUsing(fn ($state) => strtoupper($state)),
                TextColumn::make('nama_wali')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nomor_hp')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_boarding')
                    ->label('Siswa Boarding')
                    ->boolean(),
                TextColumn::make('total_tagihan_belum_lunas')
                ->label('Tagihan Belum Dibayar')
                ->getStateUsing(function ($record) {
                    if($record->tagihans
                        ->where('status', '!=', 'lunas')
                        ->sum('jumlah_netto') !== 0 ) {
                             return $record->tagihans
                            ->where('status', '!=', 'lunas')
                            ->sum('jumlah_netto') - 
                            $record->pembayaran->sum('jumlah_dibayar');
                        } else {
                            return 0;
                        }
                   
                })
                ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis_kelamin')->options([
                    'laki-laki' => 'Laki-laki',
                    'perempuan' => 'Perempuan'
                ]),
                SelectFilter::make('kelas')
                ->multiple()
                ->preload()
                ->relationship('kelas','nama_kelas'),
                SelectFilter::make('jenjang')
                ->multiple()
                ->preload()
                ->relationship('kelas','jenjang'),
                TernaryFilter::make('is_boarding')->label('Siswa Boarding')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Hapus Data Siswa') // Judul modal konfirmasi
                    ->modalDescription('Apakah Anda yakin ingin menghapus data siswa ini? Tindakan ini tidak dapat dibatalkan. Semua data terkait seperti Tagihan dan Pembayaran juga akan dihapus.') // Pesan konfirmasi kustom
                    ->modalSubmitActionLabel('Ya, Hapus Siswa') // Label tombol konfirmasi
                    ->modalCancelActionLabel('Batal Hapus'),
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
            TagihansRelationManager::class,
            PembayaranRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiswas::route('/'),
            'create' => Pages\CreateSiswa::route('/create'),
            'edit' => Pages\EditSiswa::route('/{record}/edit'),
        ];
    }
}
