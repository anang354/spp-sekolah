<?php

namespace App\Filament\Resources\AlumniResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AlumniResource;
use App\Filament\Widgets\TagihanAlumniWidget;

class ListAlumnis extends ListRecords
{
    protected static string $resource = AlumniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
     protected function getHeaderWidgets(): array
    {
        return [
            TagihanAlumniWidget::class
        ];
    }
}
