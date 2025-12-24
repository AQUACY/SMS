<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'student_id',
        'term_id',
        'status',
        'amount',
        'currency',
        'starts_at',
        'expires_at',
        'payment_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
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
     * Get the payment
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive()
    {
        return $this->status === 'active' 
            && $this->expires_at > Carbon::now();
    }

    /**
     * Expire the subscription
     */
    public function expire()
    {
        $this->status = 'expired';
        $this->expires_at = Carbon::now();
        $this->save();
    }
}

