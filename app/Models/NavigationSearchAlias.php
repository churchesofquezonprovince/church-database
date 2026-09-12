<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationSearchAlias extends Model
{
    protected $fillable = [
        'phrase',
        'target_type',
        'target_group',
        'target_label',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
