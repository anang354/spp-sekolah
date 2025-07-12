<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    //
    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }
}
