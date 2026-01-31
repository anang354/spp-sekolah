<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    // Menimpa metode resolveRecord untuk mengizinkan pengambilan data yang di-soft delete
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        // Mengambil record termasuk yang sudah di-soft delete
        $record = static::getResource()::getEloquentQuery()
            ->withTrashed() // Pastikan menyertakan data terhapus
            ->where('id', $key) // Langsung gunakan 'id' sebagai primary key
            ->first();

        if (! $record) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)
                ->setModel(static::getResource()::getModel(), [$key]);
        }

        return $record;
    }
}
