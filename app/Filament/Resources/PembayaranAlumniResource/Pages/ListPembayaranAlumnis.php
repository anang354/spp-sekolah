<?php

namespace App\Filament\Resources\PembayaranAlumniResource\Pages;

use App\Filament\Resources\PembayaranAlumniResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPembayaranAlumnis extends ListRecords
{
    protected static string $resource = PembayaranAlumniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
