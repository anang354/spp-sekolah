<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KasKategori extends Model
{
    //
    protected $fillable = ['nama_kategori'];

    public function transaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class, 'kas_kategori_id');
    }
}
