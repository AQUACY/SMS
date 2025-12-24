<?php

namespace App\Http\Controllers\Api;

use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends BaseApiController
{
    /**
     * Display a listing of subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::whereHas('student', function ($q) use ($request) {
            $q->where('school_id', $request->get('school_id'));
        })->with(['parent.user', 'student', 'term', 'payment']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($subscriptions, 'Subscriptions retrieved successfully');
    }

    /**
     * Display the specified subscription
     */
    public function show(Request $request, Subscription $subscription): JsonResponse
    {
        if ($subscription->student->school_id !== $request->get('school_id')) {
            return $this->error('Subscription not found', 404);
        }

        $subscription->load(['parent.user', 'student', 'term', 'payment']);

        return $this->success($subscription, 'Subscription retrieved successfully');
    }

    /**
     * Get student subscriptions
     */
    public function studentSubscriptions(Request $request, $studentId): JsonResponse
    {
        $subscriptions = Subscription::where('student_id', $studentId)
            ->with(['parent.user', 'term', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($subscriptions, 'Student subscriptions retrieved successfully');
    }

    /**
     * Get parent subscriptions
     */
    public function parentSubscriptions(Request $request, $parentId): JsonResponse
    {
        $subscriptions = Subscription::where('parent_id', $parentId)
            ->with(['student', 'term', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($subscriptions, 'Parent subscriptions retrieved successfully');
    }
}

