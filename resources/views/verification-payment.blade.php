<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verifikasi Pembayaran</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen flex flex-col items-center justify-center">
     <div>
        <img src="{{ asset('images/logo-sekolah.jpg') }}" alt="Logo Sekolah" class="h-32 mx-auto mb-4">
        <p class="text-gray-400 font-medium text-xl pb-4 text-center">Yayasan Budi Luhur Boarding School Batam</p>
        <p class="text-gray-500 text-sm pb-4 text-center">Sistem Sumbangan Pembinaan Pendidikan </p>
    </div>
    <div class="max-w-md w-full bg-green-50 rounded-lg text-green-700 inset-ring inset-ring-green-600/20 p-6">
        <div class="w-full flex flex-row mb-6 gap-4">
            <div>
                <p class="mb-2">Tanggal</p>
                <p class="mb-2">Nomor Bayar</p>
                <p class="mb-2">Nama</p>
                <p class="mb-2">Kelas</p>
                <p class="mb-2">Nominal</p>
            </div>
            <div class="font-medium">
                <p class="mb-2">{{ $pembayaran[0]['tanggal_pembayaran'] }}</p>
                <p class="mb-2">{{ $nomor_bayar }}</p>
                <p class="mb-2">{{ $pembayaran[0]['siswa']['nama'] }}</p>
                <p class="mb-2">{{ \App\Models\Kelas::find($pembayaran[0]['siswa']['kelas_id'])->value('nama_kelas') }}</p>
                @php
                    $totalPembayaran = 0;
                    foreach($pembayaran as $itemPembayaran) {
                        $totalPembayaran += $itemPembayaran['jumlah_dibayar'];
                    }
                @endphp
                <p class="mb-2">Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</p>
            </div>
        </div>

        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">Ada kesalahan pembayaran? <a href="https://wa.me/6281270070115" class="text-blue-600 hover:text-blue-500">Konfirmasi Admin</a></p>
        </div>
    </div>
</body>
</html>