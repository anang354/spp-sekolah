<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Kelas;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\KelasResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\KelasResource\RelationManagers;


class KelasResource extends Resource
{
    protected static ?string $model = Kelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_kelas')->required(),
                Select::make('jenjang')->required()
                ->options(Kelas::JENJANG)->live()
                ->afterStateUpdated(fn (Forms\Set $set) => $set('level', null)),
                Select::make('level')
                        ->label('level')
                        ->options(function (Forms\Get $get): array { // Menggunakan closure untuk opsi
                            $jenjang = $get('jenjang'); // Mengambil nilai jenjang pendidikan yang dipilih

                            // Logika untuk menentukan opsi kelas berdasarkan jenjang
                            if ($jenjang === 'smp') {
                                return [
                                    '7' => 'Kelas 7',
                                    '8' => 'Kelas 8',
                                    '9' => 'Kelas 9',
                                ];
                            } elseif ($jenjang === 'sma') {
                                return [
                                    '10' => 'Kelas 10',
                                    '11' => 'Kelas 11',
                                    '12' => 'Kelas 12',
                                ];
                            }
                            // Kembalikan array kosong jika jenjang belum dipilih atau tidak cocok
                            return [];
                        })
                        ->required()
                        ->placeholder('Pilih Kelas')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_kelas'),
                TextColumn::make('level'),
                TextColumn::make('jenjang')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'smp' => 'info',
                    'sma' => 'primary',
                })
                ->formatStateUsing(fn ($state) => strtoupper($state)),
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
            'index' => Pages\ListKelas::route('/'),
            'create' => Pages\CreateKelas::route('/create'),
            'edit' => Pages\EditKelas::route('/{record}/edit'),
        ];
    }
}
