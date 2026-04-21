<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTeacher extends Model
{
    use HasFactory;

    protected $table = 'class_teachers';

    protected $fillable = [
        'teacher_id',
        'class_room_id',
        'section_id',
        'academic_year',
        'assigned_by',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Accessor for teacher name
    public function getTeacherNameAttribute()
    {
        return $this->teacher?->user?->name ?? 'N/A';
    }

    // Accessor for class name
    public function getClassNameAttribute()
    {
        return $this->classRoom?->name ?? 'N/A';
    }

    // Accessor for section name
    public function getSectionNameAttribute()
    {
        return $this->section?->name ?? 'N/A';
    }

    // Get students count in this class
    public function getStudentsCountAttribute()
    {
        return $this->classRoom?->students()
            ->where('current_section_id', $this->section_id)
            ->count() ?? 0;
    }
}