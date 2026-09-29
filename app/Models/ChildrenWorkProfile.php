<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildrenWorkProfile extends Model
{
    use HasFactory;

    protected $table = 'children_work_profiles';

    protected $fillable = [
        'person_id',
        'locality_id',
        'is_active',
        'group_name',
        'serving_one_id',
        'notes',
    ];

    protected $casts = [
        'person_id' => 'integer',
        'locality_id' => 'integer',
        'is_active' => 'boolean',
        'serving_one_id' => 'integer',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(
            Person::class
        );
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(
            Locality::class
        );
    }

    public function servingOne(): BelongsTo
    {
        return $this->belongsTo(
            Person::class,
            'serving_one_id'
        );
    }
}
