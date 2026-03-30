<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_room_id', 'name', 'capacity'
    ];

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'current_section_id');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
