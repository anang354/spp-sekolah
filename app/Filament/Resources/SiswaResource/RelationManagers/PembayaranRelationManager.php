<?php

namespace App\Filament\Resources\SiswaResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class PembayaranRelationManager extends RelationManager
{
    protected static string $relationship = 'pembayaran';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
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
                TextColumn::make('user.name')->label('Petugas'),
                TextColumn::make('keterangan')
                ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('Lihat Struk')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->visible(fn ($record) => $record->metode_pembayaran === 'transfer')
                ->modalHeading('Bukti Pembayaran')
                ->modalWidth('xl')
                ->modalContent(function ($record) {
                    // 1. Cek apakah file benar-benar ada di disk local
                    if (!Storage::disk('local')->exists($record->bukti_bayar)) {
                        return new HtmlString('<p class="text-center text-danger-500">File tidak ditemukan di penyimpanan.</p>');
                    }

                    // 2. Ambil konten file dan ubah jadi Base64
                    $fileContent = Storage::disk('local')->get($record->bukti_bayar);
                    $mimeType = Storage::disk('local')->mimeType($record->bukti_bayar);
                    $base64 = base64_encode($fileContent);
                    
                    // 3. Render gambar menggunakan Data URI
                    return new HtmlString('
                        <div class="flex justify-center w-full">
                            <img 
                                src="data:'. $mimeType .';base64,'. $base64 .'" 
                                alt="Bukti Bayar" 
                                class="rounded-lg shadow-md max-w-full max-h-[80vh]" 
                            />
                        </div>
                    ');
                })->slideOver()
                ->modalSubmitAction(false)
                ->modalCancelAction(fn ($action) => $action->label('Tutup'))
                // Validasi: Sembunyikan tombol jika tidak ada bukti bayar
                ->hidden(fn ($record) => !$record->bukti_bayar),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
