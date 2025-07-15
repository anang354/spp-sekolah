<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\Tagihan;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\TagihanResource\Widgets\TagihanBelumDibayarWidget;

class TagihanSiswa extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static string $view = 'filament.pages.tagihan';

    protected function getHeaderWidgets(): array
    {
        return [
            TagihanBelumDibayarWidget::class, // Add your widget class here
        ];
    }

    public function table(Table $table): Table
    {
    return $table
        ->query(Tagihan::query()->with('pembayaran')->orderByDesc('created_at'))
         ->groups([
            'status',
        ])
        ->defaultGroup('status')
        ->columns([
            // Kolom Periode
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
            TextColumn::make('siswa.nama')->searchable(),
            TextColumn::make('siswa.kelas.nama_kelas'),
            TextColumn::make('siswa.kelas.jenjang')->label('Jenjang'),
            TextColumn::make('jatuh_tempo')->date('d F Y'),
            TextColumn::make('jumlah_tagihan')
                ->label('Jumlah Tagihan')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->summarize(Sum::make()),
            TextColumn::make('jumlah_diskon')
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
            })
            ->icons([
                'heroicon-m-check-badge' => 'lunas',
                'heroicon-m-arrow-path' => 'angsur',
                'heroicon-m-clock' => 'baru',
            ]),

        ])
        ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Filter Berdasarkan Kelas')
                    ->relationship('siswa.kelas', 'nama_kelas', fn (Builder $query) => $query->orderBy('nama_kelas')) // Relasi nested
                    ->preload()
                    ->searchable(),
                SelectFilter::make('jenjang')
                    ->label('Filter Berdasarkan Jenjang')
                    ->relationship('siswa.kelas', 'jenjang', fn (Builder $query) => $query->orderBy('nama_kelas')) // Relasi nested
                    ->preload()
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        'angsur' => 'angsur',
                        'lunas' => 'lunas',
                        'baru' => 'baru',
                    ])
                    ->multiple(),

                // <<< FILTER BERDASARKAN PERIODE BULAN >>>
                SelectFilter::make('periode_bulan')
                    ->label('Filter Berdasarkan Bulan')
                    ->options(Tagihan::BULAN)
                    ->attribute('periode_bulan'), // Kolom database yang difilter
                // <<< AKHIR FILTER BULAN >>>

                // <<< FILTER BERDASARKAN PERIODE TAHUN >>>
                SelectFilter::make('periode_tahun')
                    ->label('Filter Berdasarkan Tahun')
                    ->options(Tagihan::TAHUN)
                    ->attribute('periode_tahun'), // Kolom database yang difilter
                // <<< AKHIR FILTER TAHUN >>>
            ])
        ->headerActions([
            \App\Filament\Actions\Tagihans\CreateAction::make(),
        ]);
    }

    
}
