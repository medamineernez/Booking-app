<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Booking $booking
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['push'];
    }

    /**
     * Get the push notification representation.
     */
    public function toPush(object $notifiable): array
    {
        $eventName = $this->booking->ticket->event->title;
        $eventDate = $this->booking->ticket->event->date->format('M d, Y');

        return [
            'title' => 'Booking Confirmed! 🎉',
            'body' => "Your booking for {$eventName} on {$eventDate} has been confirmed.",
            'icon' => asset('favicon.ico'),
            'badge' => asset('favicon.ico'),
            'data' => [
                'booking_id' => $this->booking->id,
                'event_id' => $this->booking->ticket->event->id,
                'ticket_id' => $this->booking->ticket->id,
                'quantity' => $this->booking->quantity,
                'status' => $this->booking->status,
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'location' => $this->booking->ticket->event->location,
                'confirmation_message' => "Thank you for booking {$this->booking->quantity} ticket(s)!",
            ],
            'action' => [
                [
                    'action' => 'open',
                    'title' => 'View Booking',
                ],
            ],
        ];
    }

    /**
     * Get the array representation of the notification (for database/log).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'event_id' => $this->booking->ticket->event->id,
            'event_name' => $this->booking->ticket->event->title,
            'quantity' => $this->booking->quantity,
            'confirmation_message' => "Your booking for {$this->booking->quantity} ticket(s) has been confirmed.",
        ];
    }
}
