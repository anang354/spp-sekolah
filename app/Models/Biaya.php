<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
class Biaya extends Model
{
    //
    use LogsActivity;

    protected $guarded = ['id'];

    protected static $logUnguarded = true;

    protected static $logOnlyDirty = true;
    protected static $logName = 'Biaya';

    const KEUANGAN_SEKOLAH = 'sekolah';
    const KEUANGAN_PONDOK = 'pondok';

    const JENIS_KEUANGAN =  [
            self::KEUANGAN_SEKOLAH => 'sekolah',
            self::KEUANGAN_PONDOK => 'pondok',
        ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
             ->logAll();
    }

    public function diskon(): HasMany
    {
        return $this->hasMany(Diskon::class);
    }

}
