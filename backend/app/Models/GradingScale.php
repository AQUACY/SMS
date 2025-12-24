<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingScale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all grade levels for this grading scale
     */
    public function gradeLevels()
    {
        return $this->hasMany(GradeLevel::class)->orderBy('order', 'desc');
    }

    /**
     * Get the grade for a given percentage
     */
    public function getGradeForPercentage($percentage)
    {
        // Get all grade levels ordered by min_percentage descending (highest first)
        $gradeLevels = $this->gradeLevels()->orderBy('min_percentage', 'desc')->get();

        foreach ($gradeLevels as $level) {
            // Check if percentage is within this grade level's range
            if ($percentage >= $level->min_percentage) {
                // If max_percentage is null, it's the highest grade (no upper limit)
                if ($level->max_percentage === null || $percentage <= $level->max_percentage) {
                    return $level->grade;
                }
            }
        }

        // If no match found, return the lowest grade (usually F)
        $lowestGrade = $gradeLevels->last();
        return $lowestGrade ? $lowestGrade->grade : 'F';
    }

    /**
     * Get the default grading scale for a school
     */
    public static function getDefaultForSchool($schoolId)
    {
        return static::where('school_id', $schoolId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('gradeLevels')
            ->first();
    }
}

