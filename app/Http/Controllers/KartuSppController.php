<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class KartuSppController extends Controller
{
    //

    public function index(Request $request)
    {
        $siswa = \App\Models\Siswa::where('id',$request->id)->with(['tagihans', 'pembayaran', 'kelas', 'alamatSambung'])->first()->toArray();
        $path = public_path().'/images/logo-'.$siswa['kelas']['jenjang'].'.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $image = 'data:image/'.$type.';base64,'.base64_encode($data);
        $urlCode = route('verification', ['encrypted_nisn' => \Illuminate\Support\Facades\Crypt::encryptString($siswa['nisn'])]);
        $qrCode =  base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate($urlCode));
        $pdf = Pdf::loadView('templates.kartu-spp',[
            'siswa' => $siswa,
            'logo' => $image,
            'qrcode' => $qrCode
        ]);
        return $pdf->stream();
    }
    public function alumni(Request $request)
    {
        $alumni = \App\Models\Alumni::where('id',$request->id)->with(['pembayaranAlumni'])->first()->toArray();
        $path = public_path().'/images/logo-sekolah.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $image = 'data:image/'.$type.';base64,'.base64_encode($data);
        $pdf = Pdf::loadView('templates.kartu-alumni',[
            'alumni' => $alumni,
            'logo' => $image
        ]);
        return $pdf->stream();
    }
    public function verification($encrypted_nisn)
    {
        try{
            $nisn = \Illuminate\Support\Facades\Crypt::decryptString($encrypted_nisn);
        
            $checkSiswa = \App\Models\Siswa::where('nisn', $nisn)->first(); 
            if (!$checkSiswa) {
                return abort(404);
            }
            return view('verification');
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return abort(404);
        }
        
    }
}
