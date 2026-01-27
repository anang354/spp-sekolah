<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KwitansiPembayaranController extends Controller
{
    //
    public function index(Request $request)
    {
        $pembayaran = \App\Models\Pembayaran::where('nomor_bayar',str_replace('-', '/',$request->nomor_bayar))->with(['siswa', 'user', 'tagihan'])->get()->toArray();
        //dd($pembayaran);
        $url = route('verification-payment', ['encrypted_nomor' => \Illuminate\Support\Facades\Crypt::encryptString(str_replace('-', '/',$request->nomor_bayar))]);
        $qrCode =  base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate($url));
        $path = public_path().'/images/logo-sma.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $image = 'data:image/'.$type.';base64,'.base64_encode($data);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('templates.kwitansi-pembayaran-siswa',[
            'pembayaran' => $pembayaran,
            'logo' => $image,
            'qrcode' => $qrCode
        ]);

        $namaFile = $request->nomor_bayar.'_'.$pembayaran[0]['siswa']['nama'].'.pdf';
        return $pdf->stream($namaFile);
    }
    public function verification($encrypted_nomor)
    {
        $nomorBayar = \Illuminate\Support\Facades\Crypt::decryptString($encrypted_nomor);
        $checkPembayaran = \App\Models\Pembayaran::where('nomor_bayar', $nomorBayar)->with(['siswa'])->get();
        if(!$checkPembayaran){
            return abort(404);
        }
        return view('verification-payment', [
                'nomor_bayar' => $nomorBayar,
                'pembayaran' => $checkPembayaran
            ]);
        
    }
    public function alumni(Request $request)
    {
        $pembayaran = \App\Models\PembayaranAlumni::where('id',$request->id)->with(['alumni', 'user'])->first();

        $url = route('verification-payment-alumni', ['encrypted_nomor' => \Illuminate\Support\Facades\Crypt::encryptString($request->id)]);
        $qrCode =  base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate($url));
        $path = public_path().'/images/logo-sma.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $image = 'data:image/'.$type.';base64,'.base64_encode($data);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('templates.kwitansi-pembayaran-alumni',[
            'pembayaran' => $pembayaran,
            'logo' => $image,
            'qrcode' => $qrCode
        ]);
        $namaFile = 'Kwitansi-Pembayaran-Alumni-'.$pembayaran->alumni->nama.'.pdf';
        return $pdf->stream($namaFile);
    }
    public function verificationAlumni($encrypted_nomor)
    {
        $nomorBayar = \Illuminate\Support\Facades\Crypt::decryptString($encrypted_nomor);
        $checkPembayaran = \App\Models\PembayaranAlumni::where('id', $nomorBayar)->with(['alumni'])->first();
        if(!$checkPembayaran){
            return abort(404);
        }
        return view('verification-payment-alumni', [
                'nomor_bayar' => $nomorBayar,
                'pembayaran' => $checkPembayaran
            ]);
        
    }
}
