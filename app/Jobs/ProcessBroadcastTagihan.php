<?php

namespace App\Jobs;

use App\Models\Tagihan;
use App\Models\Pengaturan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Carbon\Carbon;

class ProcessBroadcastTagihan implements ShouldQueue
{
    use Queueable;

    protected $tagihanId;
    protected $type;

    /**
     * Create a new job instance.
     */
    public function __construct($tagihanId, $type)
    {
        $this->tagihanId = $tagihanId;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tagihan = Tagihan::find($this->tagihanId);
        if (!$tagihan) {
            return;
        }

        $pengaturan = Pengaturan::first();
        if (!$pengaturan) {
            return;
        }

        $setting = $pengaturan->only(['pesan'.$this->type, 'whatsapp_active', 'token_whatsapp']);
        if ($setting['whatsapp_active'] === false) {
            return;
        }

        $template = $setting['pesan'.$this->type];
        $token = $setting['token_whatsapp'];

        $jatuhTempo = Carbon::parse($tagihan->jatuh_tempo);
        $bulanNama = Tagihan::BULAN[$jatuhTempo->format('n')];
        $params = [
            '{nama_siswa}' => $tagihan->siswa->nama,
            '{nama_wali}' => $tagihan->siswa->nama_wali ?? '',
            '{nama_kelas}' => $tagihan->siswa->kelas->nama_kelas ?? '',
            '{daftar_biaya}' => $tagihan->daftar_biaya,
            '{jenis_keuangan}' => $tagihan->jenis_keuangan,
            '{tahun}' => $tagihan->periode_tahun,
            '{bulan}' => $bulanNama,
            '{jatuh_tempo}' => $jatuhTempo->format('d-m-Y'),
            '{total_tagihan}' => number_format($tagihan->jumlah_netto, 0, ',', '.'),
            '{jumlah_diskon}' => number_format($tagihan->jumlah_diskon, 0, ',', '.'),
            '{jumlah_tagihan}' => number_format($tagihan->jumlah_tagihan, 0, ',', '.'),
            '{status}' => $tagihan->status,
        ];

        $pesanSiapKirim = str_replace(array_keys($params), array_values($params), $template);
        $target = $tagihan->siswa->nomor_hp;

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
    }
}
