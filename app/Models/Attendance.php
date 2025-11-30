<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'enrollment_id',
        'qr_code_id',
        'scanned_at',
        'scan_source',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function qrCode()
    {
        return $this->belongsTo(QR::class);
    }
}
