<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification to a single user
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $isAnnouncement = false,
        string $priority = 'normal',
        ?User $createdBy = null,
        bool $sendEmail = true
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_announcement' => $isAnnouncement,
            'priority' => $priority,
            'created_by' => $createdBy?->id,
            'email_sent' => false,
        ]);

        // Send email if requested and user has email
        if ($sendEmail && $user->email) {
            try {
                Mail::to($user->email)->send(new NotificationMail($notification));
                $notification->update([
                    'email_sent' => true,
                    'email_sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send notification email', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToMany(
        array $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $isAnnouncement = false,
        string $priority = 'normal',
        ?User $createdBy = null,
        bool $sendEmail = true
    ): array {
        $notifications = [];
        
        foreach ($users as $user) {
            $notifications[] = $this->send(
                $user,
                $type,
                $title,
                $message,
                $data,
                $isAnnouncement,
                $priority,
                $createdBy,
                $sendEmail
            );
        }

        return $notifications;
    }

    /**
     * Send notification to all users with a specific role
     */
    public function sendToRole(
        string $roleName,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $isAnnouncement = false,
        string $priority = 'normal',
        ?User $createdBy = null,
        bool $sendEmail = true,
        ?int $schoolId = null
    ): array {
        $query = User::whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $users = $query->get();
        
        return $this->sendToMany(
            $users->all(),
            $type,
            $title,
            $message,
            $data,
            $isAnnouncement,
            $priority,
            $createdBy,
            $sendEmail
        );
    }

    /**
     * Send notification to all users
     */
    public function sendToAll(
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $isAnnouncement = false,
        string $priority = 'normal',
        ?User $createdBy = null,
        bool $sendEmail = true,
        ?int $schoolId = null
    ): array {
        $query = User::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $users = $query->get();
        
        return $this->sendToMany(
            $users->all(),
            $type,
            $title,
            $message,
            $data,
            $isAnnouncement,
            $priority,
            $createdBy,
            $sendEmail
        );
    }

    /**
     * Send payment notification to parent and accounts manager
     */
    public function sendPaymentNotification(
        $payment,
        string $status = 'completed'
    ): void {
        $parent = $payment->parent;
        $student = $payment->student;
        $term = $payment->term;

        if (!$parent || !$parent->user) {
            return;
        }

        $parentUser = $parent->user;
        $accountsManagers = User::whereHas('roles', function ($q) {
            $q->where('name', 'accounts_manager');
        })->where('school_id', $parentUser->school_id)->get();

        if ($status === 'completed') {
            // Notify parent
            $this->send(
                $parentUser,
                'payment',
                'Payment Successful',
                "Your payment of {$payment->currency} {$payment->amount} for {$student->full_name} ({$term->name}) has been processed successfully.",
                [
                    'payment_id' => $payment->id,
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                ],
                false,
                'normal',
                null,
                true
            );

            // Notify accounts managers
            foreach ($accountsManagers as $manager) {
                $this->send(
                    $manager,
                    'payment',
                    'New Payment Received',
                    "A payment of {$payment->currency} {$payment->amount} has been received from {$parentUser->first_name} {$parentUser->last_name} for {$student->full_name}.",
                    [
                        'payment_id' => $payment->id,
                        'parent_id' => $parent->id,
                        'student_id' => $student->id,
                        'term_id' => $term->id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                    ],
                    false,
                    'normal',
                    null,
                    true
                );
            }
        } elseif ($status === 'failed') {
            // Notify parent of failed payment
            $this->send(
                $parentUser,
                'payment',
                'Payment Failed',
                "Your payment of {$payment->currency} {$payment->amount} for {$student->full_name} ({$term->name}) has failed. Please try again.",
                [
                    'payment_id' => $payment->id,
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                ],
                false,
                'high',
                null,
                true
            );
        }
    }

    /**
     * Send subscription notification
     */
    public function sendSubscriptionNotification(
        $subscription,
        string $status = 'active'
    ): void {
        $parent = $subscription->parent;
        $student = $subscription->student;
        $term = $subscription->term;

        if (!$parent || !$parent->user) {
            return;
        }

        $parentUser = $parent->user;

        if ($status === 'active') {
            $this->send(
                $parentUser,
                'subscription',
                'Subscription Activated',
                "Your subscription for {$student->full_name} ({$term->name}) has been activated. You now have access to all student data for this term.",
                [
                    'subscription_id' => $subscription->id,
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                ],
                false,
                'normal',
                null,
                true
            );
        } elseif ($status === 'expired') {
            $this->send(
                $parentUser,
                'subscription',
                'Subscription Expired',
                "Your subscription for {$student->full_name} ({$term->name}) has expired. Please renew to continue accessing student data.",
                [
                    'subscription_id' => $subscription->id,
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                ],
                false,
                'high',
                null,
                true
            );
        }
    }
}

