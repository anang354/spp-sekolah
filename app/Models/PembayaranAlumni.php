<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranAlumni extends Model
{
    protected $guarded = ['id'];

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function booted()
    {
        static::creating(function ($pembayaran) {
            $pembayaran->user_id = auth()->user()->id;
        });
        static::created(function ($pembayaran) {
            $alumni = $pembayaran->alumni;

            $totalBayar = $alumni->pembayaranAlumni()->sum('jumlah_dibayar');

            if ($totalBayar >= $alumni->jumlah_netto) {
                $alumni->status = 'lunas';
            } elseif ($totalBayar > 0) {
                $alumni->status = 'angsur';
            } else {
                $alumni->status = 'baru';
            }

            $alumni->save();
        });
    }
}
