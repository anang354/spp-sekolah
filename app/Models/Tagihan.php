<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tagihan extends Model
{
    //
    protected $guarded = ['id'];

    const BULAN = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];
    const TAHUN = [
        '2025' => '2025',
        '2026' => '2026',
        '2027' => '2027',
        '2028' => '2028',
        '2029' => '2029',
        '2030' => '2030',
    ];

    public function siswa() : BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function getTotalPembayaranAttribute()
    {
        return $this->pembayaran->sum('jumlah_dibayar');
    }

    public function getSisaTagihanAttribute()
    {
        return $this->jumlah_netto - $this->total_pembayaran;
    }

    public function getStatusLunasAttribute()
    {
        return $this->sisa_tagihan <= 0 ? 'Lunas' : 'Belum Lunas';
    }
}
