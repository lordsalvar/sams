<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QR extends Model
{
    protected $table = 'qr_codes';

    protected $fillable = [
        'course_id',
        'session_datetime',
        'token',
        'expires_at',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
