<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'course_id',
        'student_id',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
