<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year',
        'year_level_id',
        'course_id',
        'term_type',
        'start_date',
        'end_date',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function yearLevel()
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
