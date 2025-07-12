<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    //
    protected $guarded = ['id'];

    const JENJANG = [
        'smp' => 'SMP',
        'sma' => 'SMA'
    ];

    public function siswas(): HasMany 
    {
        return $this->hasMany(Siswa::class);
    }
}
