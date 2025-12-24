<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school that owns this academic year
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all terms for this academic year
     */
    public function terms()
    {
        return $this->hasMany(Term::class);
    }

    /**
     * Get the active term for this academic year
     */
    public function activeTerm()
    {
        return $this->hasOne(Term::class)->where('status', 'active');
    }

    /**
     * Get all classes for this academic year
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    /**
     * Get all enrollments for this academic year
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}

