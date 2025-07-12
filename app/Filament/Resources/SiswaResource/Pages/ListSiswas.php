<?php

namespace App\Filament\Resources\SiswaResource\Pages;

use App\Filament\Resources\SiswaResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    public function getTabs(): array
    {
        $jenjangPendidikan = \App\Models\Kelas::select('jenjang')
        ->distinct()
        ->pluck('jenjang')
        ->toArray();
        foreach ($jenjangPendidikan as $jenjang) {
            $tabs[$jenjang] = Tab::make(strtoupper($jenjang)) // Ubah ke uppercase untuk label tab
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereHas('kelas', fn (Builder $kelasQuery) => 
                        $kelasQuery->where('jenjang', $jenjang)
                    )
                )
                ->badge(
                    // Menghitung jumlah siswa untuk jenjang ini
                    $this->getResource()::getModel()::whereHas('kelas', fn (Builder $kelasQuery) => 
                        $kelasQuery->where('jenjang', $jenjang)
                    )->count()
                );
        }
        return $tabs;
    }
    public function getDefaultActiveTab(): string | int | null
    {
        return "sma";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
