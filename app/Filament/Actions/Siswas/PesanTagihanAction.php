<?php

namespace App\Filament\Actions\Siswas;

use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class PesanTagihanAction 
{
    public static function make(): BulkAction
    {
        return BulkAction::make('pesan_tagihan')
        ->label('Kirim Pesan Tagihan')
        ->color('warning')
        ->icon('heroicon-o-chat-bubble-left-right')
            ->action(function (Collection $records){
                // Action logic here
                $pengaturan = \App\Models\Pengaturan::first();
                if (!$pengaturan) {
                    // Handle jika tidak ada pengaturan
                    return;
                }
                $setting = $pengaturan->only(['pesan3', 'whatsapp_active', 'token_whatsapp']);
                if($setting['whatsapp_active'] === false) {
                    return;
                }
                $template = $setting['pesan3'];
                $token = $setting['token_whatsapp'];
                foreach ($records as $siswa) {
                        $target = $siswa->nomor_hp;
                        $params = [
                            '{nama_siswa}' => $siswa->nama,
                            '{nama_wali}' => $siswa->nama_wali,
                            '{nama_kelas}' => $siswa->kelas->nama_kelas,
                            
                            '{total_tagihan_belum_lunas}' => number_format($siswa->total_tagihan_belum_lunas, 0, ',', '.'),
                        ];

                        $pesanSiapKirim = str_replace(array_keys($params), array_values($params), $template);
                        // Kirim via Fonnte API
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.fonnte.com/send',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => array(
                                'target' => $target,
                                'message' => $pesanSiapKirim,
                            ),
                            CURLOPT_HTTPHEADER => array(
                                "Authorization: $token"
                            ),
                        ));

                        $response = curl_exec($curl);
                        if (curl_errno($curl)) {
                            $error_msg = curl_error($curl);
                            // Log error jika perlu
                        }
                        curl_close($curl);
                        if (isset($error_msg)) {
                            Notification::make()
                                ->title("Gagal mengirim pesan ke {$siswa->nama}: {$error_msg}")
                                ->danger()
                                ->send();
                        }
                        Notification::make()
                            ->title("Pesan berhasil dikirim ke {$siswa->nama}")
                            ->success()
                            ->send();
                }
            });
    }
}