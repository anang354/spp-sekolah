<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
             ->logAll();
    }

}
