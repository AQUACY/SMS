<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    /**
     * Success response
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    protected function error(string $message = 'Error', int $statusCode = 400, $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($data !== null) {
            // If data is an array with 'errors' key, use it as errors, otherwise use as data
            if (is_array($data) && isset($data['errors'])) {
                $response['errors'] = $data['errors'];
                unset($data['errors']);
            }
            
            // Add remaining data to response
            if (is_array($data) && !empty($data)) {
                $response['data'] = $data;
            } elseif (!is_array($data)) {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Paginated response
     */
    protected function paginated($data, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ]);
    }
}

