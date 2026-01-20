<?php

namespace App\Filament\Resources\PembayaranResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PembayaranResource;

class CreatePembayaran extends CreateRecord
{
    protected static string $resource = PembayaranResource::class;
    protected function afterCreate(): void 
    {
        // Cek apakah perlu kirim WhatsApp
        if (!$this->data['is_whatsapp_sent']) {
            return;
        }

        $pengaturan = \App\Models\Pengaturan::first();
        if ($pengaturan && $pengaturan->whatsapp_active) {
            $pembayaran = $this->record;
            $siswa = \App\Models\Siswa::findOrFail($pembayaran->siswa_id);
            $jatuhTempo = Carbon::parse($pembayaran->tagihan->jatuh_tempo);
            $bulanNama = \App\Models\Tagihan::BULAN[$jatuhTempo->format('n')];
$templatePesan = "
Nomor Bayar: {nomor_pembayaran} \n
Assalamualaikum Bapak/Ibu {nama_wali}, \n 
Pembayaran atas nama {nama_siswa} untuk {daftar_biaya} {bulan} {tahun} sebesar {total_pembayaran} telah kami terima pada tanggal {tanggal_pembayaran}. \n 
Kami Syukuri Alhamdulillah Jazakumullahu Khoiro.";
            $params = [
                '{nama_siswa}' => $siswa->nama,
                '{nama_wali}' => $siswa->nama_wali ?? '',
                '{daftar_biaya}' => $pembayaran->tagihan->daftar_biaya,
                '{total_pembayaran}' => number_format($pembayaran->jumlah_dibayar, 0, ',', '.'),
                '{tanggal_pembayaran}' => \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d-m-Y'),
                '{nomor_pembayaran}' => $pembayaran->nomor_bayar,
                '{bulan}' => $bulanNama,
                '{tahun}' => $pembayaran->tagihan->periode_tahun,
            ];
            $pesanSiapKirim = str_replace(array_keys($params), array_values($params), $templatePesan);
            
            // Kirim via Fonnte API
            $target = $siswa->nomor_hp;
            $token = $pengaturan->token_whatsapp;
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
                CURLOPT_POSTFIELDS => http_build_query(array(
                    'target' => $target,
                    'message' => $pesanSiapKirim,
                )),
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
        } 
    }
}
