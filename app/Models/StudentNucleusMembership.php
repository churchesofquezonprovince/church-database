<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNucleusMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_work_term_id',
        'person_id',
        'spiritual_condition',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(
            CampusWorkTerm::class,
            'campus_work_term_id'
        );
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
