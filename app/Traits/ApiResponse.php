<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{

    protected function successResponse($data, $message = null, $code = 200, $additionalAttributes = []): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message ?: 'Ok.',
            'data' => $data
        ] + $additionalAttributes, $code);
    }

    protected function successDataPaginated($data, $dataPaginated = null, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'perPage' => $dataPaginated?->perPage(),
                'count' => $dataPaginated?->count(),
                'nextCursor' => $dataPaginated->nextCursor()?->encode(),
                'previousCursor' => $dataPaginated->previousCursor()?->encode(),
                'nextPageUrl' => $dataPaginated?->nextPageUrl(),
                'previousPageUrl' => $dataPaginated?->previousPageUrl(),
            ],
        ], $code);
    }

    protected function simplePaginatedData($data, $dataPaginated = null, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'perPage' => $dataPaginated?->perPage(),
                'count' => $dataPaginated?->count(),
                'total' => $dataPaginated?->total(),
                'page' => $dataPaginated?->currentPage(),
                'hasMorePages' => $dataPaginated?->hasMorePages(),
            ],
        ], $code);
    }

    protected function errorResponse($message = null, $code, $errors = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors
        ], $code);
    }
}
