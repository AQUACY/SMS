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
     * Also checks for completed subscription payments (in case subscription wasn't created yet)
     */
    public function hasActiveSubscription($studentId, $termId)
    {
        // Check for active subscription
        $hasSubscription = $this->subscriptions()
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
        
        if ($hasSubscription) {
            return true;
        }
        
        // Also check for completed subscription payments (subscription might not be created yet)
        $hasCompletedPayment = $this->payments()
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('payment_type', 'subscription_payment')
            ->where('status', 'completed')
            ->exists();
        
        return $hasCompletedPayment;
    }
}

