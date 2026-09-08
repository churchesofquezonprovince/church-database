<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MinistryLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'ministry_book_id',
        'code',
        'title',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(
            MinistryBook::class,
            'ministry_book_id'
        );
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            ShepherdingContact::class,
            'shepherding_contact_ministry_lessons'
        )->withTimestamps();
    }
}
