<?php

namespace App\Http\Controllers\Api;

use App\Models\SubscriptionPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPriceController extends BaseApiController
{
    /**
     * Display a listing of subscription prices
     */
    public function index(Request $request): JsonResponse
    {
        $query = SubscriptionPrice::query();

        if ($request->has('school_id')) {
            $query->where('school_id', $request->get('school_id'));
        }

        if ($request->has('price_type')) {
            $priceType = $request->get('price_type');
            if ($priceType === 'school') {
                $query->whereNotNull('school_id');
            } elseif ($priceType === 'global') {
                $query->whereNull('school_id');
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $prices = $query->with('school')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($prices, 'Subscription prices retrieved successfully');
    }

    /**
     * Store a newly created subscription price
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'price_type' => ['required', 'in:global,school'],
            'school_id' => ['required_if:price_type,school', 'nullable', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'payment_number' => ['nullable', 'string', 'max:20'],
            'payment_network' => ['nullable', 'in:mtn,vodafone,airteltigo'],
            'payment_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $priceType = $request->get('price_type');
        $schoolId = $request->get('school_id');

        // Check if price with same name already exists for this scope
        $existingPrice = SubscriptionPrice::where('name', $request->get('name'))
            ->where('school_id', $schoolId)
            ->first();

        if ($existingPrice) {
            return $this->error('A subscription price with this name already exists for this scope', 422);
        }

        $price = SubscriptionPrice::create([
            'school_id' => $priceType === 'school' ? $schoolId : null,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'amount' => $request->get('amount'),
            'currency' => $request->get('currency', 'GHS'),
            'payment_number' => $request->get('payment_number'),
            'payment_network' => $request->get('payment_network'),
            'payment_name' => $request->get('payment_name'),
            'is_active' => $request->get('is_active', true),
        ]);

        $price->load('school');

        return $this->success($price, 'Subscription price created successfully', 201);
    }

    /**
     * Display the specified subscription price
     */
    public function show(Request $request, SubscriptionPrice $subscriptionPrice): JsonResponse
    {
        $subscriptionPrice->load('school');

        return $this->success($subscriptionPrice, 'Subscription price retrieved successfully');
    }

    /**
     * Update the specified subscription price
     */
    public function update(Request $request, SubscriptionPrice $subscriptionPrice): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'payment_number' => ['nullable', 'string', 'max:20'],
            'payment_network' => ['nullable', 'in:mtn,vodafone,airteltigo'],
            'payment_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Check if name change would create duplicate
        if ($request->has('name') && $request->get('name') !== $subscriptionPrice->name) {
            $existingPrice = SubscriptionPrice::where('name', $request->get('name'))
                ->where('school_id', $subscriptionPrice->school_id)
                ->where('id', '!=', $subscriptionPrice->id)
                ->first();

            if ($existingPrice) {
                return $this->error('A subscription price with this name already exists for this scope', 422);
            }
        }

        $subscriptionPrice->update($request->only([
            'name',
            'description',
            'amount',
            'currency',
            'payment_number',
            'payment_network',
            'payment_name',
            'is_active',
        ]));

        $subscriptionPrice->load('school');

        return $this->success($subscriptionPrice, 'Subscription price updated successfully');
    }

    /**
     * Remove the specified subscription price
     */
    public function destroy(Request $request, SubscriptionPrice $subscriptionPrice): JsonResponse
    {
        $subscriptionPrice->delete();

        return $this->success(null, 'Subscription price deleted successfully');
    }

    /**
     * Get subscription price for a specific student (for parents)
     */
    public function getStudentPrice(Request $request, $studentId): JsonResponse
    {
        // Validate that student exists
        $student = \App\Models\Student::find($studentId);
        if (!$student) {
            return $this->error('Student not found', 404);
        }

        // Find applicable price using priority: class > level > global
        $price = SubscriptionPrice::findApplicablePrice($studentId);

        if (!$price) {
            return $this->success([
                'student_id' => $studentId,
                'amount' => null,
                'message' => 'No subscription price configured',
            ], 'No subscription price found');
        }

        return $this->success([
            'price' => $price->load('school'),
        ], 'Subscription price retrieved successfully');
    }
}

