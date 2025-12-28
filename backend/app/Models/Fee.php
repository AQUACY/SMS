<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'term_id',
        'class_id',
        'level_category',
        'name',
        'description',
        'amount',
        'currency',
        'is_active',
        'due_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'due_date' => 'date',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the term
     */
    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the class (if class-specific fee)
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get fee type
     */
    public function getFeeTypeAttribute()
    {
        if ($this->class_id) {
            return 'class';
        } elseif ($this->level_category) {
            return 'level';
        } else {
            return 'school';
        }
    }

    /**
     * Get fee scope description
     */
    public function getScopeDescriptionAttribute()
    {
        if ($this->class_id) {
            return $this->class ? $this->class->name : 'Specific Class';
        } elseif ($this->level_category) {
            return ucfirst($this->level_category) . ' Level';
        } else {
            return 'School Wide';
        }
    }

    /**
     * Find applicable fee for a student in a term
     * Priority: Class-specific > Level-specific > School-wide
     */
    public static function findApplicableFee($studentId, $termId)
    {
        $student = Student::with(['activeEnrollment.class'])->find($studentId);
        if (!$student) {
            return null;
        }

        $term = Term::find($termId);
        if (!$term) {
            return null;
        }

        $schoolId = $student->school_id;
        $activeEnrollment = $student->activeEnrollment;
        $class = $activeEnrollment?->class;
        $levelCategory = $class?->level_category;

        // Priority 1: Class-specific fee
        if ($class) {
            $classFee = self::where('school_id', $schoolId)
                ->where('term_id', $termId)
                ->where('class_id', $class->id)
                ->where('is_active', true)
                ->first();
            
            if ($classFee) {
                return $classFee;
            }
        }

        // Priority 2: Level-specific fee
        if ($levelCategory) {
            $levelFee = self::where('school_id', $schoolId)
                ->where('term_id', $termId)
                ->where('level_category', $levelCategory)
                ->whereNull('class_id')
                ->where('is_active', true)
                ->first();
            
            if ($levelFee) {
                return $levelFee;
            }
        }

        // Priority 3: School-wide fee
        $schoolFee = self::where('school_id', $schoolId)
            ->where('term_id', $termId)
            ->whereNull('class_id')
            ->whereNull('level_category')
            ->where('is_active', true)
            ->first();

        return $schoolFee;
    }
}

