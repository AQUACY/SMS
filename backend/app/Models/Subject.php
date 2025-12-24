<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_core',
    ];

    protected $casts = [
        'is_core' => 'boolean',
    ];

    /**
     * Get the school that owns this subject
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all classes that have this subject
     */
    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_subjects')
            ->withPivot('teacher_id', 'academic_year_id')
            ->withTimestamps();
    }

    /**
     * Get all class subjects
     */
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }
}

