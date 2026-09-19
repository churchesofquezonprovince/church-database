<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampusWorkTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'semester',
        'is_active',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function studentNucleusMemberships(): HasMany
    {
        return $this->hasMany(StudentNucleusMembership::class);
    }

    public function campusContactMemberships(): HasMany
    {
        return $this->hasMany(
            CampusContactTermMembership::class
        );
    }

    public function campusContacts(): BelongsToMany
    {
        return $this->belongsToMany(
            CampusContact::class,
            'campus_contact_term_memberships'
        )
            ->withTimestamps();
    }

    public function getDisplayLabelAttribute(): string
    {
        return 'AY ' . $this->academic_year . ' · ' . $this->semester;
    }
}
