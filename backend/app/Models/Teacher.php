<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'staff_number',
        'qualification',
        'specialization',
        'hire_date',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    /**
     * Get the user account for this teacher
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all classes where this teacher is class teacher
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'class_teacher_id');
    }

    /**
     * Get all class subjects assigned to this teacher
     */
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    /**
     * Get all assessments created by this teacher
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }
}

