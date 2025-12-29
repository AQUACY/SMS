<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends BaseApiController
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of notifications
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', auth()->id());

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($notifications, 'Notifications retrieved successfully');
    }

    /**
     * Get unread notifications
     */
    public function unread(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($notifications, 'Unread notifications retrieved successfully');
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return $this->error('Notification not found', 404);
        }

        $notification->markAsRead();

        return $this->success($notification, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->success(null, 'All notifications marked as read');
    }

    /**
     * Get unread announcement notifications (prominent display)
     */
    public function announcements(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->where('is_announcement', true)
            ->where('is_read', false)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($notifications, 'Announcements retrieved successfully');
    }

    /**
     * Admin: Send notification to users
     */
    public function send(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only admins can send notifications
        if (!$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            return $this->error('Unauthorized. Only administrators can send notifications.', 403);
        }

        $validator = Validator::make($request->all(), [
            'recipient_type' => 'required|in:all,role,specific',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|max:50',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'is_announcement' => 'nullable|boolean',
            'send_email' => 'nullable|boolean',
            // For role-based sending
            'role' => 'required_if:recipient_type,role|string',
            // For specific users
            'user_ids' => 'required_if:recipient_type,specific|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $validator->validated();
        $type = $data['type'] ?? 'general';
        $priority = $data['priority'] ?? 'normal';
        $isAnnouncement = $data['is_announcement'] ?? false;
        $sendEmail = $data['send_email'] ?? true;

        $notifications = [];

        try {
            switch ($data['recipient_type']) {
                case 'all':
                    $notifications = $this->notificationService->sendToAll(
                        $type,
                        $data['title'],
                        $data['message'],
                        [],
                        $isAnnouncement,
                        $priority,
                        $user,
                        $sendEmail,
                        $user->school_id
                    );
                    break;

                case 'role':
                    $notifications = $this->notificationService->sendToRole(
                        $data['role'],
                        $type,
                        $data['title'],
                        $data['message'],
                        [],
                        $isAnnouncement,
                        $priority,
                        $user,
                        $sendEmail,
                        $user->school_id
                    );
                    break;

                case 'specific':
                    $users = User::whereIn('id', $data['user_ids'])->get();
                    $notifications = $this->notificationService->sendToMany(
                        $users->all(),
                        $type,
                        $data['title'],
                        $data['message'],
                        [],
                        $isAnnouncement,
                        $priority,
                        $user,
                        $sendEmail
                    );
                    break;
            }

            return $this->success([
                'notifications_sent' => count($notifications),
                'notifications' => $notifications,
            ], 'Notifications sent successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to send notifications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Admin: Get list of users for notification sending
     */
    public function recipients(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only admins can view recipients
        if (!$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $query = User::with('roles');

        if ($user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->get('role'));
            });
        }

        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
            ];
        });

        return $this->success($users, 'Recipients retrieved successfully');
    }

    /**
     * Admin: Get notifications sent by the current admin
     */
    public function sent(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only admins can view sent notifications
        if (!$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $query = Notification::where('created_by', $user->id)
            ->with(['user:id,first_name,last_name,email'])
            ->select([
                'id',
                'user_id',
                'type',
                'title',
                'message',
                'is_announcement',
                'priority',
                'email_sent',
                'email_sent_at',
                'created_at',
                'created_by',
            ]);

        // Group by title, message, type, priority, is_announcement to show unique notifications
        // and include count of recipients
        $notifications = $query->orderBy('created_at', 'desc')->get();

        // Group notifications by their content (title, message, type, priority, is_announcement)
        $grouped = $notifications->groupBy(function ($notification) {
            return md5($notification->title . $notification->message . $notification->type . $notification->priority . $notification->is_announcement . $notification->created_at->format('Y-m-d H:i'));
        });

        $result = $grouped->map(function ($group) {
            $first = $group->first();
            return [
                'id' => $first->id,
                'title' => $first->title,
                'message' => $first->message,
                'type' => $first->type,
                'priority' => $first->priority,
                'is_announcement' => $first->is_announcement,
                'recipient_count' => $group->count(),
                'email_sent' => $group->where('email_sent', true)->count() > 0,
                'email_sent_count' => $group->where('email_sent', true)->count(),
                'can_edit' => $group->where('email_sent', false)->count() > 0,
                'can_delete' => $group->where('email_sent', false)->count() > 0,
                'created_at' => $first->created_at,
                'notification_ids' => $group->pluck('id')->toArray(),
            ];
        })->values();

        return $this->success($result, 'Sent notifications retrieved successfully');
    }

    /**
     * Admin: Update a notification (only if email was not sent)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = auth()->user();

        // Only admins can update notifications
        if (!$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $notification = Notification::where('id', $id)
            ->where('created_by', $user->id)
            ->first();

        if (!$notification) {
            return $this->error('Notification not found', 404);
        }

        // Cannot edit if email was sent
        if ($notification->email_sent) {
            return $this->error('Cannot edit notification that has been emailed', 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string',
            'type' => 'sometimes|nullable|string|max:50',
            'priority' => 'sometimes|nullable|in:low,normal,high,urgent',
            'is_announcement' => 'sometimes|nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Update all notifications with the same content (same title, message, type, priority, is_announcement)
        // that were created at the same time and haven't been emailed
        $notificationsToUpdate = Notification::where('created_by', $user->id)
            ->where('title', $notification->title)
            ->where('message', $notification->message)
            ->where('type', $notification->type)
            ->where('priority', $notification->priority)
            ->where('is_announcement', $notification->is_announcement)
            ->where('created_at', $notification->created_at)
            ->where('email_sent', false)
            ->get();

        $data = $validator->validated();
        
        foreach ($notificationsToUpdate as $notif) {
            $notif->update($data);
        }

        return $this->success([
            'updated_count' => $notificationsToUpdate->count(),
            'notification' => $notificationsToUpdate->first(),
        ], 'Notification updated successfully');
    }

    /**
     * Admin: Delete a notification (only if email was not sent)
     */
    public function destroy($id): JsonResponse
    {
        $user = auth()->user();

        // Only admins can delete notifications
        if (!$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $notification = Notification::where('id', $id)
            ->where('created_by', $user->id)
            ->first();

        if (!$notification) {
            return $this->error('Notification not found', 404);
        }

        // Cannot delete if email was sent
        if ($notification->email_sent) {
            return $this->error('Cannot delete notification that has been emailed', 422);
        }

        // Delete all notifications with the same content that haven't been emailed
        $deletedCount = Notification::where('created_by', $user->id)
            ->where('title', $notification->title)
            ->where('message', $notification->message)
            ->where('type', $notification->type)
            ->where('priority', $notification->priority)
            ->where('is_announcement', $notification->is_announcement)
            ->where('created_at', $notification->created_at)
            ->where('email_sent', false)
            ->delete();

        return $this->success([
            'deleted_count' => $deletedCount,
        ], 'Notification deleted successfully');
    }
}

