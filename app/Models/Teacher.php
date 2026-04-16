<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'qualification', 'joining_date', 'is_class_teacher', 
        'assigned_class_id',     
        'assigned_section_id'
    ];

    protected $casts = [
        'joining_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function classAttendances()
    {
        return $this->hasMany(ClassAttendance::class, 'marked_by');
    }

    public function assignedClass()
    {
        return $this->belongsTo(ClassRoom::class, 'assigned_class_id');
    }

    public function assignedSection()
    {
        return $this->belongsTo(Section::class, 'assigned_section_id');
    }

    public function isClassTeacherOf($classId, $sectionId = null)
    {
        if (!$this->is_class_teacher) return false;
        
        if ($sectionId) {
            return $this->assigned_class_id == $classId && $this->assigned_section_id == $sectionId;
        }
        
        return $this->assigned_class_id == $classId;
    }

    // Get students in teacher's class (if class teacher)
    public function getClassStudents()
    {
        if (!$this->is_class_teacher) {
            return collect();
        }
        
        return Student::where('current_class_id', $this->assigned_class_id)
            ->where('current_section_id', $this->assigned_section_id)
            ->with('user')
            ->get();
    }
    //
}
