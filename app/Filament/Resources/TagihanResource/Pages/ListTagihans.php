<?php

namespace App\Filament\Resources\TagihanResource\Pages;

use App\Filament\Resources\TagihanResource;
use App\Filament\Resources\TagihanResource\Widgets\TagihanBelumDibayarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTagihans extends ListRecords
{
    protected static string $resource = TagihanResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            TagihanBelumDibayarWidget::class, // Add your widget class here
        ];
    }
}
