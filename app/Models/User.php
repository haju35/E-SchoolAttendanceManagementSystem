<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'address', 'profile_photo', 'is_active'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function isAdmin(){
        return $this-> role == 'admin';
    }
    public function isTeacher(){
        return $this -> role == 'teacher';
    }

    public function isStudent(){
        return $this -> role == 'student';
    }

    public function isFamily(){
        return $this -> role == 'family';
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function family()
    {
        return $this->hasOne(Family::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function receivedNotifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function markedAttendances()
    {
        return $this->hasMany(Attendance::class, 'marked_by');
    }
}
