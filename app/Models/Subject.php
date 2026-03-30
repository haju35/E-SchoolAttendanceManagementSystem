<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code'
    ];

    public function classes()
    {
        return $this->belongsToMany(ClassRoom::class, 'class_subjects')
                    ->withPivot('is_compulsory')
                    ->withTimestamps();
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
