<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Event;
use App\Http\Resources\TicketResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ApiResponse;

    /**
     * Create a new ticket for an event (organizer only)
     */
    public function store(Request $request, $event_id)
    {
        $event = Event::find($event_id);

        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }

        // Check if user is the creator or admin
        if ($request->user()->id !== $event->created_by && $request->user()->role->value !== 'admin') {
            return $this->errorResponse('You are not authorized to add tickets to this event', 403);
        }

        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        $ticket = Ticket::create([
            'type' => $validated['type'],
            'price' => $validated['price'],
            'quantity' => $validated['quantity'],
            'event_id' => $event_id,
        ]);

        return $this->successResponse(
            new TicketResource($ticket->load('event')),
            'Ticket created successfully',
            201
        );
    }

    /**
     * Update a ticket (organizer only)
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::with('event')->find($id);

        if (!$ticket) {
            return $this->errorResponse('Ticket not found', 404);
        }

        $validated = $request->validate([
            'type' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
        ]);

        $ticket->update($validated);

        return $this->successResponse(
            new TicketResource($ticket->load('event')),
            'Ticket updated successfully'
        );
    }

    /**
     * Delete a ticket (organizer only)
     */
    public function destroy(Request $request, $id)
    {
        $ticket = Ticket::with('event')->find($id);

        if (!$ticket) {
            return $this->errorResponse('Ticket not found', 404);
        }


        $ticket->delete();

        return $this->successResponse(
            null,
            'Ticket deleted successfully'
        );
    }
}
