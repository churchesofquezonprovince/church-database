<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampusContactTermMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_work_term_id',
        'campus_contact_id',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(
            CampusWorkTerm::class,
            'campus_work_term_id'
        );
    }

    public function campusContact(): BelongsTo
    {
        return $this->belongsTo(
            CampusContact::class
        );
    }
}
