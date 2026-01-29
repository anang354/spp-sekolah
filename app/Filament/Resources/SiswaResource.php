<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Siswa;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\SiswaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Actions\Siswas\GenerateAlumniBulkAction;
use App\Filament\Resources\SiswaResource\RelationManagers;
use App\Filament\Resources\SiswaResource\RelationManagers\TagihansRelationManager;
use App\Filament\Resources\SiswaResource\RelationManagers\PembayaranRelationManager;

class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nisn')->numeric(),
                Select::make('kelas_id')->required()->options(\App\Models\Kelas::all()->pluck('nama_kelas', 'id')),
                Select::make('alamat_sambung_id')
                ->required()
                ->searchable()
                ->preload()
                ->options(
                \App\Models\AlamatSambung::all()
                    ->mapWithKeys(fn ($a) => [
                        $a->id => "{$a->kelompok} / {$a->desa} / {$a->daerah}"
                    ])
                    ),
                TextInput::make('nama')->required(),
                Radio::make('jenis_kelamin')->required()
                ->options([
                    'laki-laki'  => 'Laki-laki',
                    'perempuan'  => 'Perempuan',
                ]),
                TextInput::make('nomor_hp')->numeric(),
                Radio::make('is_boarding')
                    ->label('Apakah siswa boarding?')
                    ->required()
                    ->boolean(),
                TextInput::make('nama_wali'),
                Radio::make('is_active')->boolean(),
            ]);
    }
    public static function table(Table $table): Table
    {
        $now = Carbon::now();
        $tahun = $now->year;
        $bulan = $now->month;
        $periodeAngka = $now->format('Ym'); // contoh: 202507 (untuk Juli 2025)
        $querySiswa = Siswa::query()
             ->select('siswas.*')

    // Total tagihan
    ->selectSub(function ($query) use ($periodeAngka) {
        $query->from('tagihans')
            ->selectRaw('COALESCE(SUM(jumlah_netto), 0)')
            ->whereColumn('tagihans.siswa_id', 'siswas.id')
            ->whereRaw("DATE_FORMAT(jatuh_tempo, '%Y%m') <= ?", [$periodeAngka]);
    }, 'total_tagihan')

    // Total pembayaran
    ->selectSub(function ($query) use ($periodeAngka) {
        $query->from('pembayarans')
            ->selectRaw('COALESCE(SUM(jumlah_dibayar), 0)')
            ->whereColumn('pembayarans.siswa_id', 'siswas.id')
            ->whereExists(function ($sub) use ($periodeAngka) {
                $sub->selectRaw(1)
                    ->from('tagihans')
                    ->whereColumn('tagihans.id', 'pembayarans.tagihan_id')
                    ->whereRaw("DATE_FORMAT(jatuh_tempo, '%Y%m') <= ?", [$periodeAngka]);
            });
    }, 'total_dibayar')

    // Selisih tagihan dan pembayaran
    ->selectRaw("
        (
            (SELECT COALESCE(SUM(jumlah_netto), 0)
             FROM tagihans
             WHERE tagihans.siswa_id = siswas.id
               AND DATE_FORMAT(jatuh_tempo, '%Y%m') <= ?)
            -
            (SELECT COALESCE(SUM(jumlah_dibayar), 0)
             FROM pembayarans
             WHERE pembayarans.siswa_id = siswas.id
               AND EXISTS (
                   SELECT 1 FROM tagihans
                   WHERE tagihans.id = pembayarans.tagihan_id
                     AND DATE_FORMAT(jatuh_tempo, '%Y%m') <= ?
               )
            )
        ) AS total_tagihan_belum_lunas
    ", [$periodeAngka, $periodeAngka]);
        return $table
            ->query(
            $querySiswa
            )
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('nisn')->searchable()->copyable(),
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('jenis_kelamin')
                   ->formatStateUsing(fn ($state) => ucwords($state)),
                TextColumn::make('kelas.nama_kelas'),
                TextColumn::make('kelas.jenjang')
                ->badge()
                ->label('Jenjang')
                ->color(fn (string $state): string => match ($state) {
                    'smp' => 'info',
                    'sma' => 'primary',
                })
                ->formatStateUsing(fn ($state) => strtoupper($state)),
                TextColumn::make('alamatSambung.kelompok')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama_wali')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nomor_hp')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_boarding')
                    ->label('Siswa Boarding')
                    ->boolean(),
                TextColumn::make('total_tagihan_belum_lunas')
                ->label('Tagihan Belum Dibayar')
                ->prefix('Rp. ')
                ->numeric(decimalPlaces: 0)
                ->sortable(),
                // ->getStateUsing(function ($record) {
                //     if($record->tagihans
                //         ->where('status', '!=', 'lunas')
                //         ->sum('jumlah_netto') !== 0 ) {
                //              return $record->tagihans
                //             ->sum('jumlah_netto') -
                //             $record->pembayaran->sum('jumlah_dibayar');
                //         } else {
                //             return 0;
                //         }

                // })
                // ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('jenis_kelamin')->options([
                    'laki-laki' => 'Laki-laki',
                    'perempuan' => 'Perempuan'
                ]),
                SelectFilter::make('kelas')
                ->multiple()
                ->preload()
                ->relationship('kelas','nama_kelas'),
                SelectFilter::make('jenjang')
                ->multiple()
                ->preload()
                ->relationship('kelas','jenjang'),
                TernaryFilter::make('is_boarding')->label('Siswa Boarding'),
                TernaryFilter::make('is_active')->label('Siswa Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Hapus Data Siswa') // Judul modal konfirmasi
                    ->modalDescription('Tindakan ini adalah softdelete anda dapat memulihkan datanya kembali nanti dan tidak merubah hitungan pembayaran.') // Pesan konfirmasi kustom
                    ->modalSubmitActionLabel('Ya, Hapus Siswa') // Label tombol konfirmasi
                    ->modalCancelActionLabel('Batal Hapus'),
                Tables\Actions\Action::make('lihat-pdf')
                    ->color('success')
                    ->label('Kartu SPP')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(function($record) {
                        return url('/admin/kartu-spp/'.$record->id);
                    })
                    ->openUrlInNewTab(),
                \Filament\Tables\Actions\RestoreAction::make(), // Untuk mengembalikan data
                \Filament\Tables\Actions\ForceDeleteAction::make(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\ImportAction::make()
                    ->importer(\App\Filament\Imports\SiswaImporter::class)
                    ->color('success')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->role === 'admin'),
                    GenerateAlumniBulkAction::make()
                    ->visible(fn() => auth()->user()->role === 'admin'),
                    \App\Filament\Actions\Siswas\PesanTagihanAction::make()
                    ->visible(fn() => auth()->user()->role === 'admin' || auth()->user()->role === 'editor'),
                ]),
            ]);
    }
    // Menimpa metode getEloquentQuery untuk mengizinkan pengambilan data yang di-soft delete
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletesScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TagihansRelationManager::class,
            PembayaranRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiswas::route('/'),
            'create' => Pages\CreateSiswa::route('/create'),
            'edit' => Pages\EditSiswa::route('/{record}/edit'),
            'activities' => Pages\ListSiswaActivities::route('/{record}/activities'),
        ];
    }
}
