<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'family_id', 'admission_number', 'roll_number',
        'date_of_birth', 'gender', 'current_class_id', 'current_section_id',
        'admission_date', 'status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }
    public function currentClass()
    {
        return $this->belongsTo(ClassRoom::class, 'current_class_id');
    }
    public function currentSection()
    {
        return $this->belongsTo(Section::class, 'current_section_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

     public function getClassNameAttribute()
    {
        return $this->currentClass ? $this->currentClass->name : 'N/A';
    }

    public function getSectionNameAttribute()
    {
        return $this->currentSection ? $this->currentSection->name : 'N/A';
    }
}









