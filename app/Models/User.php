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

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, \App\Support\Permissions::forRole($this->role ?? 'cliente'), true);
    }

    public function getPermissionsAttribute(): array
    {
        return \App\Support\Permissions::forRole($this->role ?? 'cliente');
    }

    public function getRoleLabelAttribute(): string
    {
        return \App\Support\Permissions::label($this->role ?? 'cliente');
    }

    public function getHomeAttribute(): string
    {
        return $this->hasPermission('erp.read') ? '/erp' : '/?vista=perfil';
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
    protected $attributes = ['role' => 'cliente'];

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
