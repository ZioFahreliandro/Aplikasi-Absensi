<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_name',
        'date',
        'time',
        'type',
        'selfie_url',
        'latitude',
        'longitude',
        'distance',
        'ip_address',
        'status',
        'attendance_note'
    ];

    /**
     * Get the employee that owns the attendance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
