<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Pengaturan extends Model
{
    //
    protected $guarded = ['id'];
    use LogsActivity;
    protected static $logUnguarded = true;
    protected static $recordEvents = ['updated'];
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'pengaturan';
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Data pengaturan telah di {$eventName}";
    }
    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logAll();
    }
}
