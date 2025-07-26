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
        $path = public_path().'/images/logo-sekolah.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $image = 'data:image/'.$type.';base64,'.base64_encode($data);
        $pdf = Pdf::loadView('templates.kartu-spp',[
            'siswa' => $siswa,
            'logo' => $image
        ]);
        return $pdf->stream();
    }
}
