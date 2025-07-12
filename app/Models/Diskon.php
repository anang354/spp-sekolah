<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Diskon extends Model
{
    //
    protected $guarded = ['id'];

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'diskon_siswa')->withTimestamps();
    }
}
