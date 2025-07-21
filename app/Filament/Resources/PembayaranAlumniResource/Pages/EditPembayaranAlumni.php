<?php

namespace App\Filament\Resources\PembayaranAlumniResource\Pages;

use App\Filament\Resources\PembayaranAlumniResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPembayaranAlumni extends EditRecord
{
    protected static string $resource = PembayaranAlumniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
