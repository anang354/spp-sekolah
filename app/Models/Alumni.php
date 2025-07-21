<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumni extends Model
{
    //
    protected $guarded = ['id'];

    public function pembayaranAlumni(): HasMany
    {
        return $this->hasMany(PembayaranAlumni::class, 'alumni_id');
    }
}
