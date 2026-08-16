<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampusWorkStudentCenterMember extends Model
{
    protected $fillable = [
        'campus_work_student_center_id',
        'campus_contact_id',
        'notes',
    ];

    public function studentCenter(): BelongsTo
    {
        return $this->belongsTo(CampusWorkStudentCenter::class, 'campus_work_student_center_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CampusContact::class, 'campus_contact_id');
    }
}
