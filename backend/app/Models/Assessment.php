<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'term_id',
        'class_subject_id',
        'teacher_id',
        'name',
        'type',
        'total_marks',
        'weight',
        'assessment_date',
        'due_date',
        'is_finalized',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'weight' => 'decimal:2',
        'assessment_date' => 'date',
        'due_date' => 'date',
        'is_finalized' => 'boolean',
    ];

    /**
     * Get the term
     */
    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the class subject
     */
    public function classSubject()
    {
        return $this->belongsTo(ClassSubject::class);
    }

    /**
     * Get the teacher
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get all results for this assessment
     */
    public function results()
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Check if assessment can be created (term must allow new assessments)
     */
    public function canBeCreated()
    {
        return $this->term->allowsNewAssessments();
    }
}

