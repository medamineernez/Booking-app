<?php

namespace App\Observers;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    /**
     * Cache key for events list
     */
    private const EVENTS_CACHE_KEY = 'events_list';

    /**
     * Cache expiration time in minutes
     */
    private const CACHE_EXPIRATION = 10;

    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        $this->clearEventsCache();
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        $this->clearEventsCache();
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(Event $event): void
    {
        $this->clearEventsCache();
    }

    /**
     * Clear the events cache
     */
    private function clearEventsCache(): void
    {
        Cache::forget(self::EVENTS_CACHE_KEY);
        Log::info('Events cache cleared');
    }
}
