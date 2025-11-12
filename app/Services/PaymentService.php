<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use Illuminate\Support\Str;

class PaymentService
{

    public function processPayment(Booking $booking, ?string $requestedStatus = null)
    {
        $amount = $booking->ticket->price * $booking->quantity;
        $paymentStatus = $this->simulatePayment($requestedStatus);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => $paymentStatus,
        ]);

        if ($paymentStatus === 'success') {
            $booking->update(['status' => 'confirmed']);

            $booking->user->notify(new BookingConfirmed($booking));

            return [
                'success' => true,
                'payment' => $payment,
                'message' => 'Payment processed successfully. Booking confirmed.',
            ];
        } elseif ($paymentStatus === 'failed') {
            $booking->update(['status' => 'pending']);
            return [
                'success' => false,
                'payment' => $payment,
                'message' => 'Payment failed. Please try again.',
            ];
            $booking->update(['status' => 'cancelled']);
            return [
                'success' => false,
                'payment' => $payment,
                'message' => 'Payment was refunded. Booking cancelled.',
            ];
        }
    }

    /**
     * Simulate payment processing
     * In real scenario, integrate with Stripe, PayPal, etc.
     *
     * @param string|null $forcedStatus
     * @return string 'success', 'failed', or 'refunded'
     */
    private function simulatePayment(?string $forcedStatus = null): string
    {
        if ($forcedStatus && in_array($forcedStatus, ['success', 'failed', 'refunded'])) {
            return $forcedStatus;
        }

        $random = rand(1, 100);

        if ($random <= 80) {
            return 'success';
        } elseif ($random <= 95) {
            return 'failed';
        } else {
            return 'refunded';
        }
    }


    public function refundPayment(Payment $payment): array
    {
        if ($payment->status === 'refunded') {
            return [
                'success' => false,
                'message' => 'Payment is already refunded.',
            ];
        }

        if ($payment->status === 'failed') {
            return [
                'success' => false,
                'message' => 'Cannot refund a failed payment.',
            ];
        }

        $payment->update(['status' => 'refunded']);

        $payment->booking->update(['status' => 'cancelled']);

        return [
            'success' => true,
            'message' => 'Payment refunded successfully. Booking cancelled.',
        ];
    }

    /**
     * Get payment status details
     *
     * @param Payment $payment
     * @return array
     */
    public function getPaymentDetails(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'is_successful' => $payment->status === 'success',
            'is_refunded' => $payment->status === 'refunded',
            'is_failed' => $payment->status === 'failed',
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ];
    }
}
