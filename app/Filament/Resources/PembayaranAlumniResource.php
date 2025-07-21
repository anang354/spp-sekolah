<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PembayaranAlumni;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PembayaranAlumniResource\Pages;
use App\Filament\Resources\PembayaranAlumniResource\RelationManagers;

class PembayaranAlumniResource extends Resource
{
    protected static ?string $model = PembayaranAlumni::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationGroup = 'Alumni';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                ->columns([
                    'sm' => 4,
                    'xl' => 6,
                    '2xl' => 8,
                ])
                ->schema([
                    Select::make('alumni_id')
                        ->label('Pilih Alumni')
                        ->options(\App\Models\Alumni::all()->pluck('nama', 'id'))
                        ->preload()
                        ->live()
                        ->required()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $alumni = \App\Models\Alumni::find($state);
                            $set('preview_total_tagihan', $alumni?->jumlah_netto ?? 0);
                        })
                        ->searchable()
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 4,
                        ]),
                    TextInput::make('preview_total_tagihan')
                        ->label('Tagihan')
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix('Rp. ') // Tambahkan prefix mata uang
                        ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                        ->hint('Total tagihan dari alumni yang dipilih. (Nilai ini tidak disimpan ke database)') // Pesan bantuan
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 4,
                        ]),
                ]),

                Section::make()
                    ->columns([
                        'sm' => 4,
                        'xl' => 6,
                        '2xl' => 7,
                    ])
                    ->schema([
                         Radio::make('metode_pembayaran')
                            ->options([
                                'tunai' => 'Tunai',
                                'transfer' => 'Transfer',
                            ])
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 1,
                            ]),
                        DatePicker::make('tanggal_pembayaran')
                            ->default(now())
                            ->required()
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 3,
                            ]),
                        TextInput::make('keterangan')
                        ->columnSpan([
                                'sm' => 'full',
                                'xl' => 2,
                                '2xl' => 3,
                            ]),
                ]),

                Section::make()
                    ->columns([
                        'sm' => 2,
                        'xl' => 4,
                        '2xl' => 8,
                    ])
                    ->schema([
                       TextInput::make('jumlah_dibayar')
                            ->numeric()
                            ->label('Jumlah Dibayar (Rp)')
                            ->live(debounce: 1000) // agar update terbilang secara live
                                ->afterStateUpdated(function (callable $set, $state) {
                                    $set('terbilang', \App\Helpers\Terbilang::make((int) $state));
                                })
                            ->required()
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 4,
                            ]),
                        Placeholder::make('terbilang')
                            ->label('Terbilang')
                            ->content(fn ($get) => $get('terbilang'))
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 4,
                            ]),
                        // ...
                ]),
                FileUpload::make('bukti_bayar')
                        ->disk('local')
                        ->directory('bukti-bayar')
                        ->downloadable() // <<< Penting: Mengizinkan file didownload dari Filament
                        ->previewable() // <<< Opsional: Memungkinkan pratinjau gambar atau PDF (jika didukung browser)
                        ->visibility('private') 
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_pembayaran'),
                TextColumn::make('alumni.nama')->searchable(),
                TextColumn::make('jumlah_dibayar')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
                TextColumn::make('metode_pembayaran'),
                TextColumn::make('user.name')
                    ->label('Operator'),
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
            'index' => Pages\ListPembayaranAlumnis::route('/'),
            'create' => Pages\CreatePembayaranAlumni::route('/create'),
            'edit' => Pages\EditPembayaranAlumni::route('/{record}/edit'),
        ];
    }
}
