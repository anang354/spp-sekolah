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
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AlumniResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AlumniResource\RelationManagers;

class AlumniResource extends Resource
{
    protected static ?string $model = Alumni::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 99;

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
            TextInput::make('keterangan')
            ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable(),
                TextColumn::make('tahun_lulus')->sortable()->toggleable(),
                TextColumn::make('jenjang'),
                TextColumn::make('jumlah_tagihan')
                ->sortable()
                ->numeric(decimalPlaces: 0),
                TextColumn::make('jumlah_diskon')
                ->sortable()
                ->numeric(decimalPlaces: 0),
                TextColumn::make('jumlah_netto')
                ->sortable()
                ->numeric(decimalPlaces: 0),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->status === 'lunas'),
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
            'index' => Pages\ListAlumnis::route('/'),
            'create' => Pages\CreateAlumni::route('/create'),
            'edit' => Pages\EditAlumni::route('/{record}/edit'),
        ];
    }
}
