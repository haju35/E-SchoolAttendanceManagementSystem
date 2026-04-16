<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassAttendance extends Model
{
    use HasFactory;

    protected $table = 'class_attendances';

    protected $fillable = [
        'student_id', 'class_room_id', 'section_id', 'date',
        'status', 'marked_by', 'remarks'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(Teacher::class, 'marked_by');
    }

    // Scope for today's attendance
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    // Scope for a specific date
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }
}