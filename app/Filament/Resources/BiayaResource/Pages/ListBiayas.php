<?php

namespace App\Filament\Resources\BiayaResource\Pages;

use App\Filament\Resources\BiayaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBiayas extends ListRecords
{
    protected static string $resource = BiayaResource::class;

    public function getTabs(): array 
    {
        return [
            'sma' => Tab::make('SMA')
            ->icon('heroicon-s-academic-cap')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenjang', 'sma')->orderByDesc('created_at')),
            'smp' => Tab::make('SMP')
            ->icon('heroicon-m-star')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('jenjang', 'smp')->orderByDesc('created_at')),
        ];
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
