<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'person_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_ENCODER = 'encoder';

    public const ROLE_VIEWER = 'viewer';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function person(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function preferredLocalityName(): ?string
    {
        if (! $this->person_id) {
            return null;
        }

        // Read the current canonical locality, never a copied user locality.
        $provinceId = \Illuminate\Support\Facades\DB::table('province_settings')
            ->value('primary_province_id');

        if (! $provinceId) {
            return null;
        }

        return \Illuminate\Support\Facades\DB::table('persons')
            ->join('localities', 'localities.id', '=', 'persons.locality_id')
            ->where('persons.id', $this->person_id)
            ->where('localities.province_id', $provinceId)
            ->where('localities.is_active', true)
            ->value('localities.name');
    }

    public function defaultLocalitySelection(
        array $allowed,
        mixed $requested = null,
        ?string $fallback = null
    ): ?string {
        // An explicit selection (including blank) always beats the preference.
        $candidate = $requested === null
            ? $this->preferredLocalityName()
            : (is_string($requested) ? trim($requested) : null);

        if (filled($candidate)) {
            foreach ($allowed as $name) {
                if (mb_strtolower((string) $name) === mb_strtolower($candidate)) {
                    return (string) $name;
                }
            }
        }

        return $fallback;
    }

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
