<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'photo',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school that owns this student
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all parents/guardians for this student
     */
    public function parents()
    {
        return $this->belongsToMany(Guardian::class, 'student_parent', 'student_id', 'parent_id')
            ->withPivot('relationship', 'is_primary')
            ->withTimestamps();
    }

    /**
     * Get all enrollments for this student
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get active enrollment
     */
    public function activeEnrollment()
    {
        return $this->hasOne(Enrollment::class)->where('status', 'active');
    }

    /**
     * Get all results for this student
     */
    public function results()
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Get all attendance records for this student
     */
    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all subscriptions for this student
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    /**
     * Generate a unique student number with school code format: SCHOOLCODE-STU001
     * 
     * @param int $schoolId
     * @return string
     */
    public static function generateStudentNumber($schoolId): string
    {
        $school = School::find($schoolId);
        
        if (!$school || !$school->code) {
            throw new \Exception('School not found or school code not set');
        }

        $schoolCode = strtoupper($school->code);
        
        // Get all student numbers for this school that match the format
        $students = self::where('school_id', $schoolId)
            ->where('student_number', 'like', $schoolCode . '-%')
            ->get();

        $maxNumber = 0;
        
        foreach ($students as $student) {
            // Extract the number part (e.g., "STU001" from "BA01-STU001")
            $parts = explode('-', $student->student_number, 2);
            if (count($parts) === 2) {
                $numberPart = $parts[1]; // e.g., "STU001"
                
                // Extract numeric part
                preg_match('/(\d+)$/', $numberPart, $matches);
                if (isset($matches[1])) {
                    $number = (int)$matches[1];
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }
        }

        // Increment and format
        $maxNumber++;
        $studentNumber = 'STU' . str_pad($maxNumber, 3, '0', STR_PAD_LEFT);

        return $schoolCode . '-' . $studentNumber;
    }
}

