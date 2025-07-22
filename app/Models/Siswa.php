<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Siswa extends Model
{
    //
    protected $guarded = ['id'];

    public function kelas() : BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function diskon(): BelongsToMany
    {
        return $this->belongsToMany(Diskon::class, 'diskon_siswa')->withTimestamps();
    }

    public function tagihans(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }
    public function alamatSambung(): BelongsTo
    {
        return $this->belongsTo(AlamatSambung::class);
    }
}
