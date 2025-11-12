<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Booking;
use App\Enum\UserRole;

class TicketBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $organizer;
    protected Event $event;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'push_token' => 'test_push_token']);
        $this->organizer = User::factory()->create(['role' => UserRole::ORGANIZER]);
        $this->event = Event::factory()->create(['created_by' => $this->organizer->id]);
        $this->ticket = Ticket::factory()->create(['event_id' => $this->event->id, 'quantity' => 10]);
    }

    /**
     * Test a customer can book a ticket with valid data.
     */
    public function test_customer_can_book_ticket_with_valid_data(): void
    {
        $this->actingAs($this->customer, 'sanctum');

        $bookingData = [
            'quantity' => 2,
        ];

        $response = $this->postJson("/api/tickets/{$this->ticket->id}/bookings", $bookingData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'quantity',
                    'status',
                    'user' => ['id', 'name'],
                    'ticket' => ['id', 'type', 'price'],
                ],
                'message',
            ])
            ->assertJson(['message' => 'Booking created successfully']);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);

        // Assert ticket quantity decreased
        $this->assertEquals(8, $this->ticket->fresh()->quantity);
    }

    /**
     * Test a customer cannot book more tickets than available.
     */
    public function test_customer_cannot_book_more_than_available_tickets(): void
    {
        $this->actingAs($this->customer, 'sanctum');

        $bookingData = [
            'quantity' => 15, // Only 10 available
        ];

        $response = $this->postJson("/api/tickets/{$this->ticket->id}/bookings", $bookingData);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Not enough tickets available.']);

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 15,
        ]);

        // Assert ticket quantity unchanged
        $this->assertEquals(10, $this->ticket->fresh()->quantity);
    }

    /**
     * Test a customer can cancel their own booking.
     */
    public function test_customer_can_cancel_their_own_booking(): void
    {
        $this->actingAs($this->customer, 'sanctum');

        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Booking cancelled successfully']);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test a customer cannot cancel another user's booking.
     */
    public function test_customer_cannot_cancel_another_users_booking(): void
    {
        $otherCustomer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $this->actingAs($otherCustomer, 'sanctum');

        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(403)
            ->assertJson(['message' => 'You are not authorized to cancel this booking']);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test a customer cannot double-book the same ticket (handled by middleware).
     */
    public function test_customer_cannot_double_book_the_same_ticket(): void
    {
        $this->actingAs($this->customer, 'sanctum');

        // First booking
        $bookingData = [
            'quantity' => 1,
        ];
        $this->postJson("/api/tickets/{$this->ticket->id}/bookings", $bookingData);

        // Second booking for the same ticket by the same user
        $response = $this->postJson("/api/tickets/{$this->ticket->id}/bookings", $bookingData);

        $response->assertStatus(401)
            ->assertJson(['message' => 'You have already booked tickets for this event.']);
    }

    /**
     * Test an organizer cannot book a ticket.
     */
    public function test_organizer_cannot_book_ticket(): void
    {
        $this->actingAs($this->organizer, 'sanctum');

        $bookingData = [
            'quantity' => 1,
        ];

        $response = $this->postJson("/api/tickets/{$this->ticket->id}/bookings", $bookingData);

        $response->assertStatus(403) // Forbidden due to RoleMiddleware
            ->assertJson(['message' => 'Unauthorized']);
    }
}
