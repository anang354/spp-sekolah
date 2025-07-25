<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Alumni extends Model
{
    //
    use LogsActivity;

    protected $guarded = ['id'];

    protected static $logUnguarded = true;

    protected static $recordEvents = ['updated', 'deleted'];
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'alumni';
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Data alumni telah  di {$eventName}";
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function pembayaranAlumni(): HasMany
    {
        return $this->hasMany(PembayaranAlumni::class, 'alumni_id');
    }
}
