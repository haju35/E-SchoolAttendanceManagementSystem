<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassSubject extends Pivot
{
    protected $table = 'class_subject';

    protected $fillable = [
        'class_id', 'subject_id', 'is_compulsory'
    ];

    protected $casts = [
        'is_compulsory' => 'boolean'
    ];

    public function class()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
