<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Booking;
use App\Models\Payment;
use App\Enum\UserRole;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingConfirmed;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $organizer;
    protected Event $event;
    protected Ticket $ticket;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake(); // Prevent actual notifications from being sent

        $this->customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'push_token' => 'test_push_token']);
        $this->organizer = User::factory()->create(['role' => UserRole::ORGANIZER]);
        $this->event = Event::factory()->create(['created_by' => $this->organizer->id]);
        $this->ticket = Ticket::factory()->create(['event_id' => $this->event->id, 'quantity' => 10, 'price' => 50]);
        $this->booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    /**
     * Test a customer can make a successful payment for a booking.
     */
    public function test_customer_can_make_successful_payment_for_booking(): void
    {
        $this->actingAs($this->customer, 'sanctum');

        $paymentData = [
            'status' => 'success',
        ];

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment", $paymentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'booking_id',
                    'amount',
                    'status',
                ],
                'message',
            ])
            ->assertJson(['message' => 'Payment processed successfully. Booking confirmed.']);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'amount' => 100.00, // 2 tickets * 50 price
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'confirmed',
        ]);

        Notification::assertSentTo($this->customer, BookingConfirmed::class, function ($notification) {
            return $notification->booking->id === $this->booking->id;
        });
    }

    /**
     * Test payment failure for a booking.
     */
    public function test_payment_failure_for_booking(): void
    {
        $this->actingAs($this->customer, 'sanctum');

        $paymentData = [
            'status' => 'failed',
        ];

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment", $paymentData);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Payment failed. Please try again.']);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'pending',
        ]);

        Notification::assertNotSentTo($this->customer, BookingConfirmed::class);
    }

    /**
     * Test a customer cannot pay for another user's booking.
     */
    public function test_customer_cannot_pay_for_another_users_booking(): void
    {
        $otherCustomer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $this->actingAs($otherCustomer, 'sanctum');

        $paymentData = [
            'status' => 'success',
        ];

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment", $paymentData);

        $response->assertStatus(403)
            ->assertJson(['message' => 'You are not authorized to pay for this booking']);

        $this->assertDatabaseMissing('payments', [
            'booking_id' => $this->booking->id,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'pending',
        ]);

        Notification::assertNotSentTo($this->customer, BookingConfirmed::class);
    }

    /**
     * Test a booking that already has a payment cannot be paid again.
     */
    public function test_cannot_pay_for_booking_that_already_has_payment(): void
    {
        $this->actingAs($this->customer, 'sanctum');

        Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'amount' => 100.00,
            'status' => 'success',
        ]);

        $paymentData = [
            'status' => 'success',
        ];

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment", $paymentData);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Payment already exists for this booking']);

        $this->assertDatabaseCount('payments', 1); // Only one payment record

        Notification::assertNotSentTo($this->customer, BookingConfirmed::class);
    }

    /**
     * Test an organizer cannot process payment for a booking.
     */
    public function test_organizer_cannot_process_payment(): void
    {
        $this->actingAs($this->organizer, 'sanctum');

        $paymentData = [
            'status' => 'success',
        ];

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment", $paymentData);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);

        Notification::assertNotSentTo($this->customer, BookingConfirmed::class);
    }
}
