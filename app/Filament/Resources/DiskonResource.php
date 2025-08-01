<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Diskon;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DiskonResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DiskonResource\RelationManagers;

class DiskonResource extends Resource
{
    protected static ?string $model = Diskon::class;

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Diskon';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('tipe')->options([
                    'persentase' => 'Persentase (%)',
                    'nominal' => 'Nominal (Rp)'
                ])->columnSpan('full')->required()->live(),
                TextInput::make('nama_diskon')->required(),
                Select::make('biaya_id')
                    ->label('Biaya yang di Diskon')
                    ->options(
                        \App\Models\Biaya::all()->mapWithKeys(fn ($a) => [
                            $a->id => "{$a->nama_biaya} - {$a->jenjang}"
                        ])
                    )->required(),
                Section::make('Diskon')->schema([
                    TextInput::make('persentase')->suffix('%')->columnSpan(1)
                        ->visible(fn (callable $get) => $get('tipe') === 'persentase'),
                    TextInput::make('nominal')->numeric()->prefix('Rp.')->columnSpan('full')
                    ->visible(fn (callable $get) => $get('tipe') === 'nominal'),
                    TextInput::make('keterangan')->columnSpan('full')
                ])->columns(6),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_diskon'),
                TextColumn::make('tipe'),
                TextColumn::make('biaya.nama_biaya')->label('Biaya yang di Diskon'),
                TextColumn::make('persentase')
                ->suffix('%'),
                TextColumn::make('nominal')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0),
                TextColumn::make('keterangan'),
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
            'index' => Pages\ListDiskons::route('/'),
            'create' => Pages\CreateDiskon::route('/create'),
            'edit' => Pages\EditDiskon::route('/{record}/edit'),
        ];
    }
}
