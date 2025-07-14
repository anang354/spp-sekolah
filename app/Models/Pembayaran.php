<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    //
    protected $guarded = ['id'];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    protected static function booted()
    {
        static::creating(function ($pembayaran) {
            $pembayaran->user_id = auth()->user()->id;
        });
        static::created(function ($pembayaran) {
            $tagihan = $pembayaran->tagihan;

            $totalBayar = $tagihan->pembayaran()->sum('jumlah_dibayar');

            if ($totalBayar >= $tagihan->jumlah_netto) {
                $tagihan->status = 'lunas';
            } elseif ($totalBayar > 0) {
                $tagihan->status = 'angsur';
            } else {
                $tagihan->status = 'baru';
            }

            $tagihan->save();
        });
    }
}
