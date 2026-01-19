<?php

namespace App\Filament\Actions\Tagihans;

use App\Jobs\ProcessBroadcastTagihan;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class BroadcastTagihanAction
{
    public static function make($type = 1, $label = null): BulkAction
    {
         $defaultLabel = $label ?: 'Kirim Pesan ' . $type;
         return BulkAction::make('pesan_' . $type)
            ->icon('heroicon-o-chat-bubble-bottom-center-text')
            ->color('primary')
            ->label($defaultLabel)
            ->action(function (Collection $records) use ($type) {
                $pengaturan = \App\Models\Pengaturan::first();
                if (!$pengaturan) {
                    // Handle jika tidak ada pengaturan
                    return;
                }
                $setting = $pengaturan->only(['pesan'.$type, 'whatsapp_active', 'token_whatsapp']);
                if($setting['whatsapp_active'] === false) {
                    return;
                }

                foreach ($records as $index => $tagihan) {
                    // Dispatch job dengan delay 8 detik per pesan
                    ProcessBroadcastTagihan::dispatch($tagihan->id, $type)
                        ->delay(now()->addSeconds($index * 8));
                }
                Notification::make()
                    ->title('Pesan sedang diproses untuk dikirimkan secara bertahap.')
                    ->success()
                    ->send();
            });
    }
}