<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembayaran;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PembayaranResource\Pages;
use App\Filament\Resources\PembayaranResource\RelationManagers;

class PembayaranResource extends Resource
{
    protected static ?string $model = Pembayaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    Select::make('siswa_id')
                        ->label('Pilih Siswa')
                        ->options(\App\Models\Siswa::all()->pluck('nama', 'id'))
                        ->preload()
                        ->live()
                        ->required()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('tagihan_id', null))
                        ->searchable()
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 4,
                        ]),
                    Select::make('tagihan_id')
                        ->label('Tagihan')
                        ->options(function (callable $get) {
                            return \App\Models\Tagihan::where('siswa_id', $get('siswa_id'))
                                ->whereColumn('jumlah_netto', '>', DB::raw('(SELECT COALESCE(SUM(jumlah_dibayar), 0) FROM pembayarans WHERE pembayarans.tagihan_id = tagihans.id)'))
                                ->get()
                                ->mapWithKeys(function ($tagihan) {
                                    $label = \Carbon\Carbon::createFromDate(null, $tagihan->periode_bulan, 1)->translatedFormat('F') . ' ' . $tagihan->periode_tahun.' - Rp.'.number_format($tagihan->jumlah_netto, 0, ",", ".");

                                    return [$tagihan->id => $label];
                                });
                        })
                        ->reactive()
                        ->required()
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
                            ->live(debounce: 500) // agar update terbilang secara live
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
                        ->disk('storage')
                        ->directory('bukti-bayar')
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListPembayarans::route('/'),
            'create' => Pages\CreatePembayaran::route('/create'),
            'edit' => Pages\EditPembayaran::route('/{record}/edit'),
        ];
    }
}
