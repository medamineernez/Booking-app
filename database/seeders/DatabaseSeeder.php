<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->count(2)->create();

        $organizers = User::factory()->organizer()->count(3)->create();

        User::factory()->customer()->count(10)->create();

        $events = [];
        foreach ($organizers as $organizer) {
            $organizer_events = Event::factory()->count(2)->create([
                'created_by' => $organizer->id,
            ]);
            $events = array_merge($events, $organizer_events->toArray());
        }

        $existing_events = Event::count();
        if ($existing_events < 5) {
            Event::factory()->count(5 - $existing_events)->create([
                'created_by' => $organizers->first()->id,
            ]);
        }

        $all_events = Event::limit(5)->get();

        $tickets = [];
        foreach ($all_events as $event) {
            $event_tickets = Ticket::factory()->count(3)->create([
                'event_id' => $event->id,
            ]);
            $tickets = array_merge($tickets, $event_tickets->toArray());
        }

        $customers = User::where('role', 'customer')->get();
        $all_tickets = Ticket::all();

        for ($i = 0; $i < 20; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $customers->random()->id,
                'ticket_id' => $all_tickets->random()->id,
            ]);

            $ticket = $booking->ticket;
            $payment_amount = $ticket->price * $booking->quantity;

            Payment::factory()->create([
                'booking_id' => $booking->id,
                'amount' => $payment_amount,
            ]);
        }
    }
}
