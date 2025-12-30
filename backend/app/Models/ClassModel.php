<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'name',
        'level',
        'section',
        'capacity',
        'class_teacher_id',
        'academic_year_id',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school that owns this class
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year for this class
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the class teacher
     */
    public function classTeacher()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    /**
     * Get all enrollments for this class
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get all students in this class
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
            ->wherePivot('status', 'active')
            ->withPivot('enrollment_date', 'status')
            ->withTimestamps();
    }

    /**
     * Get all subjects for this class
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withPivot('teacher_id', 'academic_year_id')
            ->withTimestamps();
    }

    /**
     * Get all class subjects (ClassSubject model instances)
     */
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    /**
     * Get all fees for this class
     */
    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    /**
     * Extract level category from level string
     * e.g., "JHS 1" -> "jhs", "Primary 3" -> "primary", "Nursery 1" -> "nursery"
     */
    public function getLevelCategoryAttribute()
    {
        $level = strtolower($this->level ?? '');
        
        if (strpos($level, 'nursery') !== false) {
            return 'nursery';
        } elseif (strpos($level, 'creche') !== false) {
            return 'creche';
        } elseif (strpos($level, 'primary') !== false || strpos($level, 'p') === 0) {
            return 'primary';
        } elseif (strpos($level, 'jhs') !== false || strpos($level, 'junior') !== false) {
            return 'jhs';
        } elseif (strpos($level, 'shs') !== false || strpos($level, 'senior') !== false) {
            return 'shs';
        }
        
        return null;
    }
}
