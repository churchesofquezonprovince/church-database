<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampusWorkStudentCenter extends Model
{
    protected $fillable = [
        'name',
        'school_campus',
        'locality',
        'place',
        'notes',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(CampusWorkStudentCenterMember::class);
    }
}
