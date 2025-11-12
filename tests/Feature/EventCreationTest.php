<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Enum\UserRole;
use App\Models\Event;

class EventCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(UserRole $role = UserRole::ORGANIZER)
    {
        $user = User::factory()->create(['role' => $role]);
        return $this->actingAs($user, 'sanctum');
    }

    /**
     * Test an organizer can create an event with valid data.
     */
    public function test_organizer_can_create_event_with_valid_data(): void
    {
        $this->authenticate(UserRole::ORGANIZER);

        $eventData = [
            'title' => 'Test Event',
            'description' => 'This is a test event description.',
            'date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'location' => 'Test Venue',
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'date',
                    'location',
                    'creator' => ['id', 'name', 'email'],
                ],
                'message',
            ])
            ->assertJson(['message' => 'Event created successfully']);

        $this->assertDatabaseHas('events', [
            'title' => 'Test Event',
            'location' => 'Test Venue',
        ]);
    }

    /**
     * Test a customer cannot create an event.
     */
    public function test_customer_cannot_create_event(): void
    {
        $this->authenticate(UserRole::CUSTOMER);

        $eventData = [
            'title' => 'Customer Event',
            'description' => 'Description',
            'date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'location' => 'Location',
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(403) // Forbidden
            ->assertJson(['message' => 'This action is unauthorized.']);
    }

    /**
     * Test unauthenticated user cannot create an event.
     */
    public function test_unauthenticated_user_cannot_create_event(): void
    {
        $eventData = [
            'title' => 'Unauthenticated Event',
            'description' => 'Description',
            'date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'location' => 'Location',
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(401) // Unauthorized
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Test event creation with invalid data (missing title).
     */
    public function test_organizer_cannot_create_event_with_missing_title(): void
    {
        $this->authenticate(UserRole::ORGANIZER);

        $eventData = [
            'description' => 'Description',
            'date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'location' => 'Location',
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test event creation with a date in the past.
     */
    public function test_organizer_cannot_create_event_with_past_date(): void
    {
        $this->authenticate(UserRole::ORGANIZER);

        $eventData = [
            'title' => 'Past Event',
            'description' => 'Description',
            'date' => now()->subDay()->format('Y-m-d H:i:s'),
            'location' => 'Location',
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * Test an organizer can update their own event.
     */
    public function test_organizer_can_update_their_own_event(): void
    {
        $user = User::factory()->create(['role' => UserRole::ORGANIZER]);
        $this->actingAs($user, 'sanctum');

        $event = Event::factory()->create(['created_by' => $user->id]);

        $updatedData = [
            'title' => 'Updated Event Title',
            'location' => 'Updated Location',
        ];

        $response = $this->putJson("/api/events/{$event->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Event updated successfully']);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
            'location' => 'Updated Location',
        ]);
    }

    /**
     * Test an organizer cannot update another organizer's event.
     */
    public function test_organizer_cannot_update_another_organizers_event(): void
    {
        $organizer1 = User::factory()->create(['role' => UserRole::ORGANIZER]);
        $organizer2 = User::factory()->create(['role' => UserRole::ORGANIZER]);

        $this->actingAs($organizer1, 'sanctum');

        $event = Event::factory()->create(['created_by' => $organizer2->id]);

        $updatedData = [
            'title' => 'Attempted Update',
        ];

        $response = $this->putJson("/api/events/{$event->id}", $updatedData);

        $response->assertStatus(403) // Forbidden
            ->assertJson(['message' => 'This action is unauthorized.']);
    }

    /**
     * Test an organizer can delete their own event.
     */
    public function test_organizer_can_delete_their_own_event(): void
    {
        $user = User::factory()->create(['role' => UserRole::ORGANIZER]);
        $this->actingAs($user, 'sanctum');

        $event = Event::factory()->create(['created_by' => $user->id]);

        $response = $this->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Event deleted successfully']);

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /**
     * Test an organizer cannot delete another organizer's event.
     */
    public function test_organizer_cannot_delete_another_organizers_event(): void
    {
        $organizer1 = User::factory()->create(['role' => UserRole::ORGANIZER]);
        $organizer2 = User::factory()->create(['role' => UserRole::ORGANIZER]);

        $this->actingAs($organizer1, 'sanctum');

        $event = Event::factory()->create(['created_by' => $organizer2->id]);

        $response = $this->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403) // Forbidden
            ->assertJson(['message' => 'This action is unauthorized.']);
    }
}
