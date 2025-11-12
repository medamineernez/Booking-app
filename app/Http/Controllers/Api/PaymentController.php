<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Http\Resources\PaymentResource;
use App\Traits\ApiResponse;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a mock payment for a booking
     */
    public function store(Request $request, $booking_id)
    {
        $booking = Booking::with('ticket')->find($booking_id);

        if (!$booking) {
            return $this->errorResponse('Booking not found', 404);
        }

        if ($request->user()->id !== $booking->user_id) {
            return $this->errorResponse('You are not authorized to pay for this booking', 403);
        }

        if ($booking->payment) {
            return $this->errorResponse('Payment already exists for this booking', 400);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:success,failed,refunded',
        ]);

        $result = $this->paymentService->processPayment($booking, $validated['status'] ?? null);

        return $this->successResponse(
            new PaymentResource($result['payment']->load('booking')),
            $result['message'],
            $result['success'] ? 201 : 400
        );
    }

    /**
     * Get a specific payment
     */
    public function show($id)
    {
        $payment = Payment::with('booking.ticket.event', 'booking.user')->find($id);

        if (!$payment) {
            return $this->errorResponse('Payment not found', 404);
        }

        return $this->successResponse(
            new PaymentResource($payment),
            'Payment retrieved successfully'
        );
    }
}
