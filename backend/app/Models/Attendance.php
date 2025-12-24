<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'term_id',
        'class_id',
        'student_id',
        'date',
        'status',
        'remarks',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the term
     */
    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the class
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class);
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user who marked this attendance
     */
    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}

