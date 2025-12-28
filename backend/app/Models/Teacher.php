<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'staff_number',
        'qualification',
        'specialization',
        'hire_date',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    /**
     * Get the user account for this teacher
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all classes where this teacher is class teacher
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'class_teacher_id');
    }

    /**
     * Get all class subjects assigned to this teacher
     */
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    /**
     * Get all assessments created by this teacher
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Generate a unique staff number with school code format: SCHOOLCODE-TEA001
     * 
     * @param int $schoolId
     * @return string
     */
    public static function generateStaffNumber($schoolId): string
    {
        $school = School::find($schoolId);
        
        if (!$school || !$school->code) {
            throw new \Exception('School not found or school code not set');
        }

        $schoolCode = strtoupper($school->code);
        
        // Get all staff numbers for this school that match the format
        $teachers = self::whereHas('user', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
        ->where('staff_number', 'like', $schoolCode . '-%')
        ->get();

        $maxNumber = 0;
        
        foreach ($teachers as $teacher) {
            if (!$teacher->staff_number) {
                continue;
            }
            
            // Extract the number part (e.g., "TEA001" from "BA01-TEA001")
            $parts = explode('-', $teacher->staff_number, 2);
            if (count($parts) === 2) {
                $numberPart = $parts[1]; // e.g., "TEA001"
                
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
        $staffNumber = 'TEA' . str_pad($maxNumber, 3, '0', STR_PAD_LEFT);

        return $schoolCode . '-' . $staffNumber;
    }
}

