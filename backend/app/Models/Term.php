<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Term extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'name',
        'term_number',
        'start_date',
        'end_date',
        'status',
        'grace_period_days',
        'grace_period_end',
        'closed_at',
        'archived_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'grace_period_end' => 'date',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
        'term_number' => 'integer',
        'grace_period_days' => 'integer',
    ];

    /**
     * Get the academic year that owns this term
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get all assessments for this term
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Get all attendance records for this term
     */
    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all subscriptions for this term
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all payments for this term
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if term is in a specific status
     */
    public function isStatus($status)
    {
        return $this->status === $status;
    }

    /**
     * Check if term allows new assessments
     */
    public function allowsNewAssessments()
    {
        return in_array($this->status, ['draft', 'active']);
    }

    /**
     * Move term to closing status (grace period)
     */
    public function startClosing()
    {
        $this->status = 'closing';
        $this->grace_period_end = Carbon::now()->addDays($this->grace_period_days);
        $this->save();
    }

    /**
     * Close the term
     */
    public function close()
    {
        $this->status = 'closed';
        $this->closed_at = Carbon::now();
        $this->save();

        // Expire all subscriptions for this term
        $this->subscriptions()->where('status', 'active')->update([
            'status' => 'expired',
            'expires_at' => Carbon::now(),
        ]);
    }

    /**
     * Archive the term
     */
    public function archive()
    {
        $this->status = 'archived';
        $this->archived_at = Carbon::now();
        $this->save();
    }
}

