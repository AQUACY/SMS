<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'student_id',
        'term_id',
        'payment_type',
        'initiated_by',
        'amount',
        'currency',
        'payment_method',
        'momo_provider',
        'momo_transaction_id',
        'reference',
        'verification_token',
        'payment_reference',
        'status',
        'webhook_data',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'webhook_data' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the parent/guardian
     */
    public function parent()
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the term
     */
    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the subscription created from this payment
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the user who initiated this payment (for manual payments)
     */
    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Check if this is a fee payment
     */
    public function isFeePayment()
    {
        return $this->payment_type === 'fee_payment';
    }

    /**
     * Check if this is a subscription payment
     */
    public function isSubscriptionPayment()
    {
        return $this->payment_type === 'subscription_payment';
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted($verifiedBy = 'webhook')
    {
        $this->status = 'completed';
        $this->verified_at = now();
        $this->verified_by = $verifiedBy;
        $this->save();
    }
}

