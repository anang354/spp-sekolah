<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\Siswa;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class TagihanSiswaTerbesar extends BaseWidget
{
    protected static ?string $heading = '🏆 Top Rank Tagihan Siswa';


    public function table(Table $table): Table
    {
        return $table
        ->paginated(false)
            ->query(
                Siswa::query()
            ->select('siswas.*')
            ->selectSub(function ($query) {
                $query->from('tagihans')
                    ->selectRaw('COALESCE(SUM(jumlah_netto), 0)')
                    ->whereColumn('tagihans.siswa_id', 'siswas.id');
            }, 'total_tagihan')
            ->selectSub(function ($query) {
                $query->from('pembayarans')
                    ->selectRaw('COALESCE(SUM(jumlah_dibayar), 0)')
                    ->whereColumn('pembayarans.siswa_id', 'siswas.id');
            }, 'total_pembayaran')
            ->selectRaw('(
                (select COALESCE(SUM(jumlah_netto), 0)
                 from tagihans where tagihans.siswa_id = siswas.id)
                -
                (select COALESCE(SUM(jumlah_dibayar), 0)
                 from pembayarans where pembayarans.siswa_id = siswas.id)
            ) as sisa_tagihan')
            ->orderByDesc('sisa_tagihan')
            ->limit(7)
            )
            ->columns([
                TextColumn::make('nama')->label('Nama Siswa'),
                TextColumn::make('kelas.nama_kelas')->label('Kelas')->toggleable(),
                TextColumn::make('alamatSambung.kelompok')->label('Kelompok')->toggleable(),
                TextColumn::make('total_tagihan')
                ->numeric(decimalPlaces: 0)
                ->label('Total Tagihan'),
                TextColumn::make('total_pembayaran')
                ->numeric(decimalPlaces: 0)
                ->label('Telah Dibayar'),
                TextColumn::make('sisa_tagihan')
                ->numeric(decimalPlaces: 0)
                ->label('Sisa Tagihan'),
            ]);
    }
}
