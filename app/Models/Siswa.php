<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Siswa extends Model
{
    //
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id'];
    protected static $logUnguarded = true;

    protected static $recordEvents = ['updated', 'deleted'];
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'user';
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Data siswa telah  di {$eventName}";
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['jenis_kelamin', 'nama_wali']);
    }

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
