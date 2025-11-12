<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Http\Resources\EventResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    use ApiResponse;

    /**
     * Cache key for events list
     */
    private const EVENTS_CACHE_KEY = 'events_list';

    /**
     * Cache expiration time in minutes
     */
    private const CACHE_EXPIRATION = 10;

    /**
     * Get all events with pagination, search, and filters
     */
    public function index(Request $request)
    {
        $hasFilters = $request->has('search') || $request->has('location') ||
            $request->has('date_from') || $request->has('date_to');

        if (!$hasFilters) {
            $cacheKey = self::EVENTS_CACHE_KEY . '_page_' . ($request->query('page', 1));
            $cachedEvents = Cache::get($cacheKey);

            if ($cachedEvents) {
                return $this->successResponse(
                    $cachedEvents['events'],
                    'Events retrieved successfully (cached)',
                    200,
                    $cachedEvents['pagination']
                );
            }
        }

        $query = Event::with('creator', 'tickets');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->search($search, ['title', 'description']);
        }

        if ($request->has('location')) {
            $query->where('location', $request->query('location'));
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->filterByDate($request->query('date_from'), $request->query('date_to'), 'date');
        } elseif ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->query('date_from'));
        } elseif ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->query('date_to'));
        }

        $query->latest('date');

        $perPage = $request->query('per_page', 15);
        $events = $query->paginate($perPage);

        $response = EventResource::collection($events);
        $paginationData = [
            'pagination' => [
                'total' => $events->total(),
                'count' => $events->count(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ]
        ];

        if (!$hasFilters) {
            $cacheKey = self::EVENTS_CACHE_KEY . '_page_' . $events->currentPage();
            Cache::put($cacheKey, [
                'events' => $response,
                'pagination' => $paginationData['pagination'],
            ], now()->addMinutes(self::CACHE_EXPIRATION));
        }

        return $this->successResponse(
            $response,
            'Events retrieved successfully',
            200,
            $paginationData
        );
    }

    /**
     * Get a single event with its tickets
     */
    public function show($id)
    {
        $event = Event::with('creator', 'tickets')->find($id);

        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }

        return $this->successResponse(
            new EventResource($event),
            'Event retrieved successfully'
        );
    }

    /**
     * Create a new event (organizer only)
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date|after:now',
            'location' => 'required|string|max:255',
        ]);

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'location' => $validated['location'],
            'created_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new EventResource($event->load('creator', 'tickets')),
            'Event created successfully',
            201
        );
    }

    /**
     * Update an event (organizer only)
     */
    public function update(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }
        if ($event->created_by !== $request->user()->id) {
            return $this->errorResponse('This action is unauthorized.', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'date' => 'sometimes|date|after:now',
            'location' => 'sometimes|string|max:255',
        ]);

        $event->update($validated);

        return $this->successResponse(
            new EventResource($event->load('creator', 'tickets')),
            'Event updated successfully'
        );
    }

    /**
     * Delete an event (organizer only)
     */
    public function destroy(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }

        if ($event->created_by !== $request->user()->id) {
            return $this->errorResponse('This action is unauthorized.', 403);
        }

        $event->delete();

        return $this->successResponse(
            null,
            'Event deleted successfully'
        );
    }
}
