<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampusWorkDashboardItem extends Model
{
    public const SECTION_STUDENT_BOOK = 'student_book';
    public const SECTION_SERVING_BOOK = 'serving_book';
    public const SECTION_ADDITIONAL_READING = 'additional_reading';

    protected $fillable = [
        'section',
        'title',
        'description',
        'link',
        'sort_order',
    ];
}
