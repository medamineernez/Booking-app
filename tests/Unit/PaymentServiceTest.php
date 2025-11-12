<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PaymentService;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\User;
use App\Models\Event;
use App\Enum\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingConfirmed;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;
    protected Booking $booking;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
        Notification::fake();

        $this->user = User::factory()->create(['role' => UserRole::CUSTOMER, 'push_token' => 'test_push_token']);
        $event = Event::factory()->create(['created_by' => User::factory()->create(['role' => UserRole::ORGANIZER])->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'price' => 50, 'quantity' => 10]);
        $this->booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    /**
     * Test successful payment processing.
     */
    public function test_process_payment_success(): void
    {
        $result = $this->paymentService->processPayment($this->booking, 'success');

        $this->assertTrue($result['success']);
        $this->assertEquals('Payment processed successfully. Booking confirmed.', $result['message']);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'confirmed',
        ]);

        Notification::assertSentTo($this->user, BookingConfirmed::class, function ($notification) {
            return $notification->booking->id === $this->booking->id;
        });
    }

    /**
     * Test failed payment processing.
     */
    public function test_process_payment_failure(): void
    {
        $result = $this->paymentService->processPayment($this->booking, 'failed');

        $this->assertFalse($result['success']);
        $this->assertEquals('Payment failed. Please try again.', $result['message']);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'pending',
        ]);

        Notification::assertNotSentTo($this->user, BookingConfirmed::class);
    }

    /**
     * Test payment refund processing.
     */
    public function test_process_payment_refund(): void
    {
        $result = $this->paymentService->processPayment($this->booking, 'refunded');

        $this->assertFalse($result['success']);
        $this->assertEquals('Payment was refunded. Booking cancelled.', $result['message']);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'refunded',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'cancelled',
        ]);

        Notification::assertNotSentTo($this->user, BookingConfirmed::class);
    }

    /**
     * Test refunding an existing successful payment.
     */
    public function test_refund_successful_payment(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'success',
        ]);
        $this->booking->update(['status' => 'confirmed']);

        $result = $this->paymentService->refundPayment($payment);

        $this->assertTrue($result['success']);
        $this->assertEquals('Payment refunded successfully. Booking cancelled.', $result['message']);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'cancelled',
        ]);

        Notification::assertNotSentTo($this->user, BookingConfirmed::class); // No notification for refund
    }

    /**
     * Test cannot refund an already refunded payment.
     */
    public function test_cannot_refund_already_refunded_payment(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'refunded',
        ]);
        $this->booking->update(['status' => 'cancelled']);

        $result = $this->paymentService->refundPayment($payment);

        $this->assertFalse($result['success']);
        $this->assertEquals('Payment is already refunded.', $result['message']);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);
    }

    /**
     * Test cannot refund a failed payment.
     */
    public function test_cannot_refund_failed_payment(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'failed',
        ]);
        $this->booking->update(['status' => 'pending']);

        $result = $this->paymentService->refundPayment($payment);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot refund a failed payment.', $result['message']);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
    }
}
