<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembayaran;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PembayaranResource\Pages;
use App\Filament\Resources\PembayaranResource\RelationManagers;

class PembayaranResource extends Resource
{
    protected static ?string $model = Pembayaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

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
                        ->disabledOn('edit')
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
                                    $label = $tagihan->daftar_biaya.' '.\Carbon\Carbon::createFromDate(null, $tagihan->periode_bulan, 1)->translatedFormat('F') . ' ' . $tagihan->periode_tahun.' - Rp.'.number_format($tagihan->sisa_tagihan, 0, ",", ".");

                                    return [$tagihan->id => $label];
                                });
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $tagihan = \App\Models\Tagihan::find($value);

                            if (! $tagihan) {
                                return null;
                            }

                            // Copy-paste formatting label yang sama seperti di atas
                            $bulan = \Carbon\Carbon::createFromDate(null, $tagihan->periode_bulan, 1)->translatedFormat('F');
                            // Catatan: sisa_tagihan mungkin perlu dicek aksesornnya di model Tagihan
                            $sisa = number_format($tagihan->sisa_tagihan, 0, ",", ".");
                            
                            return "{$tagihan->daftar_biaya} {$bulan} {$tagihan->periode_tahun} - Rp.{$sisa}";
                        })
                        ->reactive()
                        ->searchable()
                        ->disabledOn('edit')
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
                             ->required()
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
                            ->disabledOn('edit')
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
                        ->visibility('private'),
                \Filament\Forms\Components\Toggle::make('is_whatsapp_sent')
                    ->label('Apakah mengirim notifikasi WhatsApp?')
                    ->default(true)
                    ->dehydrated(false),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('nomor_bayar')->searchable()->toggleable(),
                TextColumn::make('siswa.nama')->searchable(),
                TextColumn::make('tanggal_pembayaran')
                ->date('l, d F Y'),
                TextColumn::make('periode_tagihan')
                    ->label('Periode Tagihan')
                    ->getStateUsing(function ($record) {
                        $bulan = $record->tagihan->periode_bulan ?? null;
                        $tahun = $record->tagihan->periode_tahun ?? null;

                        if ($bulan && $tahun) {
                            return Carbon::createFromDate(null, $bulan, 1)->translatedFormat('F') . ' ' . $tahun;
                        }

                        return '-';
                }),
                TextColumn::make('jumlah_dibayar')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
                TextColumn::make('metode_pembayaran')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                        'tunai' => 'success',
                        'transfer' => 'info',
                    }),
                    TextColumn::make('tagihan.jenis_keuangan') // Notasi dot untuk akses relasi
                    ->label('Keuangan')
                    ->badge() // Opsional: Agar tampil seperti label warna
                    ->color(fn (string $state): string => match ($state) {
                        'pondok' => 'primary',
                        'sekolah' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Operator')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('keterangan')
                ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('metode_pembayaran')
                    ->options([
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer',
                    ]),
                SelectFilter::make('jenis_keuangan')
                ->label('Jenis Keuangan')
                // 1. Tentukan Pilihan (Options)
                // Bisa manual array seperti ini:
                ->options([
                    'pondok' => 'Pondok',
                    'sekolah' => 'Sekolah',
                ])
                // ATAU ambil unik dari database (agar dinamis):
                // ->options(fn() => \App\Models\Tagihan::distinct()->pluck('jenis_keuangan', 'jenis_keuangan')->toArray())

                // 2. Logika Query ke Tabel Relasi
                ->query(function (Builder $query, array $data) {
                    // Jika user tidak memilih apa-apa, jangan lakukan filter
                    if (empty($data['value'])) {
                        return $query;
                    }

                    // Gunakan whereHas untuk menembus ke tabel 'tagihan'
                    return $query->whereHas('tagihan', function (Builder $query) use ($data) {
                        $query->where('jenis_keuangan', $data['value']);
                    });
                }),
                 Filter::make('Tanggal Pembayaran')
                    ->form([
                        DatePicker::make('tanggal_mulai')->label('Dari'),
                        DatePicker::make('tanggal_selesai')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['tanggal_mulai'], fn ($q, $date) => $q->whereDate('tanggal_pembayaran', '>=', $date))
                            ->when($data['tanggal_selesai'], fn ($q, $date) => $q->whereDate('tanggal_pembayaran', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn()=>  auth()->user()->role === 'admin'),
                Tables\Actions\Action::make('kwitansi')
                    ->label('Kwitansi')
                    ->icon('heroicon-o-printer')
                    ->url(function($record) {
                        return url('/admin/kwitansi-pembayaran/'.str_replace('/', '-', $record->nomor_bayar));
                    })->openUrlInNewTab()->visible(fn()=>  auth()->user()->role === 'admin' || auth()->user()->role === 'editor'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make()
                    //     ->visible(fn()=>  auth()->user()->role === 'admin'),
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
            'activities' => Pages\ListPembayaranActivities::route('/{record}/activities'),
        ];
    }
}
