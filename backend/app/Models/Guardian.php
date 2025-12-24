<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'phone',
        'momo_provider',
        'momo_number',
    ];

    /**
     * Get the user account for this guardian
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all students for this guardian
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_parent', 'parent_id', 'student_id')
            ->withPivot('relationship', 'is_primary')
            ->withTimestamps();
    }

    /**
     * Get all subscriptions for this guardian
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'parent_id');
    }

    /**
     * Get all payments for this guardian
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'parent_id');
    }

    /**
     * Check if guardian has active subscription for student and term
     */
    public function hasActiveSubscription($studentId, $termId)
    {
        return $this->subscriptions()
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }
}

