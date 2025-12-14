<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'role_label',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'             => 'hashed',
            'role'                 => UserRole::class,
        ];
    }

    /**
     * Get the user's role as a capitalized string.
     */
    public function getRoleLabelAttribute(): string
    {
        return ucfirst($this->role->value);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // --------------------------------------------------------------------
    // Jwt token generation need to initialize the token 
    // that is statless to hold the data needed to generate token.
    // -------------------------------------------------------------------
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role->value,
        ];
    }
}
