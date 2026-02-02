<?php

namespace App\Filament\Resources\KasLaporanResource\Pages;

use App\Filament\Resources\KasLaporanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKasLaporan extends EditRecord
{
    protected static string $resource = KasLaporanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
