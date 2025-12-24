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
        'amount',
        'currency',
        'payment_method',
        'momo_provider',
        'momo_transaction_id',
        'reference',
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

