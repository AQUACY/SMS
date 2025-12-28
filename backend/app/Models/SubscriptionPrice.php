<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'amount',
        'currency',
        'payment_number',
        'payment_network',
        'payment_name',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school (if school-specific price)
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get price type
     */
    public function getPriceTypeAttribute()
    {
        return $this->school_id ? 'school' : 'global';
    }

    /**
     * Get scope description
     */
    public function getScopeDescriptionAttribute()
    {
        if ($this->school_id) {
            return $this->school ? $this->school->name : 'Specific School';
        } else {
            return 'Global (All Schools)';
        }
    }

    /**
     * Find applicable subscription price for a student
     * Priority: School-specific > Global
     */
    public static function findApplicablePrice($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return null;
        }

        $schoolId = $student->school_id;

        // Priority 1: School-specific price
        if ($schoolId) {
            $schoolPrice = self::where('school_id', $schoolId)
                ->where('is_active', true)
                ->first();
            
            if ($schoolPrice) {
                return $schoolPrice;
            }
        }

        // Priority 2: Global price
        $globalPrice = self::whereNull('school_id')
            ->where('is_active', true)
            ->first();

        return $globalPrice;
    }
}

