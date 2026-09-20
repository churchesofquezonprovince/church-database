<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MinistryBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'title_tagalog',
        'short_title',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(MinistryLesson::class)
            ->orderBy('sort_order')
            ->orderBy('code');
    }
}
