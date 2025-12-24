<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_scale_id',
        'grade',
        'label',
        'min_percentage',
        'max_percentage',
        'gpa_value',
        'description',
        'order',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'order' => 'integer',
    ];

    /**
     * Get the grading scale
     */
    public function gradingScale()
    {
        return $this->belongsTo(GradingScale::class);
    }
}

