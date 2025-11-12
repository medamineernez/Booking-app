<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Booking;
use Symfony\Component\HttpFoundation\Response;

class PreventDoubleBooking
{
    /**
     * Handle an incoming request to prevent double booking.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post') && $request->route()->getName() === null) {
            $ticketId = $request->route('id');
            $userId = $request->user()?->id;

            if ($userId && $ticketId) {
                $existingBooking = Booking::where('user_id', $userId)
                    ->where('ticket_id', $ticketId)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($existingBooking) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You have already booked tickets for this event.',
                    ], 409);
                }
            }
        }

        return $next($request);
    }
}
