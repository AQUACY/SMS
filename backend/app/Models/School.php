<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'domain',
        'logo',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all users for this school
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all academic years for this school
     */
    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    /**
     * Get all students for this school
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get all classes for this school
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    /**
     * Get all subjects for this school
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * Get all grading scales for this school
     */
    public function gradingScales()
    {
        return $this->hasMany(GradingScale::class);
    }

    /**
     * Get the default grading scale for this school
     */
    public function defaultGradingScale()
    {
        return $this->hasOne(GradingScale::class)->where('is_default', true)->where('is_active', true);
    }
}

