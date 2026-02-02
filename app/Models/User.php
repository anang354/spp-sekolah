<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Activitylog\LogOptions;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LogsActivity, SoftDeletes;

    protected static $recordEvents = ['updated', 'deleted'];
    protected static $logAttributes = ['name', 'email', 'password', 'is_active', 'role'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'user';

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if($model->isDirty(['remember_token'] && $model->getDirty() === ['remember_token'])) {
                activity()->disableLogging();
            }
        });
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Data user telah  di {$eventName}";
    }



    const ROLE_ADMIN    = "admin";
    const ROLE_EDITOR   = "editor";
    const ROLE_VIEW     = "viewer";

    const USER_ROLES = [
        self::ROLE_ADMIN    => 'Admin',
        self::ROLE_EDITOR   => 'Editor',
        self::ROLE_VIEW     => 'View',
    ];

    public function isAdmin() {
        return $this->role === self::ROLE_ADMIN;
    }
    public function isEditor() {
        return $this->role === self::ROLE_EDITOR;
    }
    public function isViewer() {
        return $this->role === self::ROLE_VIEW;
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }
    public function pembayaranAlumni(): HasMany
    {
        return $this->hasMany(PembayaranAlumni::class);
    }
    public function kasTransaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class);
    }

    public function kasLaporan(): HasMany
    {
        return $this->hasMany(KasLaporan::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //Jika menggunakan protected  $guarded = ['id']
    //protected static $logUnguarded = true;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'email', 'password', 'role', 'is_active',
            ]);
    }
}
