<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_ADMIN    = "admin";
    const ROLE_EDITOR   = "editor";
    const ROLE_VIEW     = "view";

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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
}
