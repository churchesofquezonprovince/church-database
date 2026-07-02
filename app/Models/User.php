<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_ENCODER = 'encoder';

    public const ROLE_VIEWER = 'viewer';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEncoder(): bool
    {
        return $this->role === self::ROLE_ENCODER;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function canManageRecords(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_ENCODER,
        ], true);
    }

    public function canImportRecords(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_ENCODER,
        ], true);
    }

    public function canExportRecords(): bool
    {
        return $this->isAdmin();
    }

    public function canDeleteRecords(): bool
    {
        return $this->isAdmin();
    }

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
