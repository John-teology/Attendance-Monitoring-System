<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = [
        'name',
        'type',
        'academic_year',
        'start_date',
        'end_date',
    ];

    public function yearLevels()
    {
        return $this->belongsToMany(YearLevel::class, 'term_year_level')->withTimestamps();
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
