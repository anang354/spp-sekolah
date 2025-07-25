<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pembayaran extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];
    protected static $logUnguarded = true;

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'pembayaran';

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Data pembayaran telah  di {$eventName}";
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

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
