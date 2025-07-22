<?php

namespace App\Filament\Imports;

use App\Models\Alumni;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class AlumniImporter extends Importer
{
    protected static ?string $model = Alumni::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('jenjang')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('tahun_lulus')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('jumlah_tagihan')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('jumlah_diskon')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('jumlah_netto')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('alamat')
                ->rules(['max:255']),
            ImportColumn::make('file')
                ->rules(['max:255']),
            ImportColumn::make('keterangan')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Alumni
    {
        // return Alumni::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Alumni();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your alumni import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
