<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Biaya;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\BiayaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BiayaResource\RelationManagers;

class BiayaResource extends Resource
{
    protected static ?string $model = Biaya::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_biaya')->required(),
                Select::make('jenis_siswa')->required()
                ->options([
                    'semua' => 'Semua Siswa',
                    'boarding' => 'Siswa Boarding',
                    'non-boarding' => 'Siswa Komplek'
                ]),
                Select::make('jenjang')->required()
                ->options([
                    'smp' => 'SMP',
                    'sma' => 'SMA',
                ]),
                Forms\Components\Radio::make('jenis_keuangan')->required()
                            ->options(Biaya::JENIS_KEUANGAN),
                Section::make('Nominal Biaya')->schema([
                    TextInput::make('nominal')->numeric()->prefix('Rp.')->required()
                    ->live(debounce: 500) // agar update terbilang secara live
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('terbilang', \App\Helpers\Terbilang::make((int) $state));
                    }),
                    Forms\Components\Placeholder::make('terbilang')
                    ->label('Terbilang')
                    ->content(fn ($get) => $get('terbilang')),
                    TextInput::make('keterangan'),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_biaya'),
                TextColumn::make('jenis_siswa'),
                TextColumn::make('nominal')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
                TextColumn::make('jenis_keuangan'),
                TextColumn::make('keterangan')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                        ->visible(fn () => auth()->user()->role === 'admin'),
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
            'index' => Pages\ListBiayas::route('/'),
            'create' => Pages\CreateBiaya::route('/create'),
            'edit' => Pages\EditBiaya::route('/{record}/edit'),
            'activities' => Pages\ListBiayaActivities::route('/{record}/activities'),
        ];
    }
}
