<?php

namespace App\Filament\Resources\KelasResource\Pages;

use App\Filament\Resources\KelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListKelas extends ListRecords
{
    protected static string $resource = KelasResource::class;

    public function getTabs(): array 
    {
        return [
            'sma' => Tab::make('SMA')
            ->icon('heroicon-s-academic-cap')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenjang', 'sma')->orderByDesc('level')),
            'smp' => Tab::make('SMP')
            ->icon('heroicon-m-star')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('jenjang', 'smp')->orderByDesc('level')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
