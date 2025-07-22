<?php

namespace App\Filament\Imports;

use App\Models\Siswa;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class SiswaImporter extends Importer
{
    protected static ?string $model = Siswa::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('kelas')
                ->requiredMapping()
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('nisn')
                ->requiredMapping()
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('nama')
                ->requiredMapping()
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('jenis_kelamin')
                ->requiredMapping()
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('nama_wali')
                ->requiredMapping()
                ->rules(['max:255']),
            ImportColumn::make('nomor_hp')
                ->requiredMapping()
                ->rules(['max:255']),
            ImportColumn::make('is_boarding')
                ->requiredMapping()
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('is_active')
                ->requiredMapping()
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('alamatSambung')
                ->requiredMapping()
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
        ];
    }

    public function resolveRecord(): ?Siswa
    {
        // return Siswa::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Siswa();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your siswa import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
