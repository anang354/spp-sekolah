<?php

namespace App\Filament\Resources\KasLaporanResource\Pages;

use App\Filament\Resources\KasLaporanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKasLaporans extends ListRecords
{
    protected static string $resource = KasLaporanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
