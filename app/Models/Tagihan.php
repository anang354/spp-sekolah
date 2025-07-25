<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Tagihan extends Model
{
    use LogsActivity;
    //
    protected $guarded = ['id'];

    protected static $logUnguarded = true;

    protected static $recordEvents = ['updated', 'deleted'];
    protected static $logAttributes = ['jatuh_tempo', 'jumlah_tagihan', 'jumlah_diskon', 'daftar_biaya', 'daftar_diskon', 'jumlah_netto', 'status', 'keterangan'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'tagihan';

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Tagihan siswa telah  di {$eventName}";
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    const KEUANGAN_SEKOLAH = 'sekolah';
    const KEUANGAN_PONDOK = 'pondok';

    const JENIS_KEUANGAN =  [
        self::KEUANGAN_SEKOLAH => 'sekolah',
        self::KEUANGAN_PONDOK => 'pondok',
    ];

    const BULAN = [
        '1' => 'Januari',
        '2' => 'Februari',
        '3' => 'Maret',
        '4' => 'April',
        '5' => 'Mei',
        '6' => 'Juni',
        '7' => 'Juli',
        '8' => 'Agustus',
        '9' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];
    const TAHUN = [
        '2024' => '2024',
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
