<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use App\Http\Resources\BookingResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    /**
     * Get current user's bookings
     */
    public function index(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with('ticket.event', 'payment')
            ->paginate(15);

        return $this->successResponse(
            BookingResource::collection($bookings),
            'Bookings retrieved successfully',
            200,
            [
                'pagination' => [
                    'total' => $bookings->total(),
                    'count' => $bookings->count(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                ]
            ]
        );
    }

    /**
     * Create a new booking (customer)
     */
    public function store(Request $request, $ticket_id)
    {
        $ticket = Ticket::with('event')->find($ticket_id);

        if (!$ticket) {
            return $this->errorResponse('Ticket not found', 404);
        }
        $user = $request->user();
        if (!$user || $user->role !== 'customer') {
            return $this->errorResponse('This action is unauthorized.', 401);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if ($ticket->quantity < $validated['quantity']) {
            return $this->errorResponse(
                'Not enough tickets available.',
                401
            );
        }

        $booking = Booking::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket_id,
            'quantity' => $validated['quantity'],
            'status' => 'pending',
        ]);

        return $this->successResponse(
            new BookingResource($booking->load('ticket.event', 'payment')),
            'Booking created successfully',
            201
        );
    }

    /**
     * Cancel a booking (customer)
     */
    public function cancel(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return $this->errorResponse('Booking not found', 404);
        }

        if ($request->user()->id !== $booking->user_id) {
            return $this->errorResponse('You are not authorized to cancel this booking', 403);
        }

        if ($booking->status === 'cancelled') {
            return $this->errorResponse('Booking is already cancelled', 400);
        }

        $booking->update(['status' => 'cancelled']);

        return $this->successResponse(
            new BookingResource($booking->load('ticket.event', 'payment')),
            'Booking cancelled successfully'
        );
    }
}
