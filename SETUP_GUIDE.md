# Setup Guide for Booking System Features

This guide explains how to set up and configure the three main features implemented in the booking system.

---

## Prerequisites

-   PHP 8.1+
-   Laravel 11.x
-   SQLite (or MySQL/PostgreSQL)
-   Composer

---

## Step 1: Run Migrations

First, run the database migrations to create necessary tables:

```bash
php artisan migrate
```

This will:

-   Create the `jobs` table for queue processing (already exists)
-   Create the `failed_jobs` table for failed job tracking (already exists)
-   **Add `push_token` column to `users` table** (NEW - 2025_11_12_000000)

---

## Step 2: Configure Queue System

### Update `.env` file

Add or update these configuration variables:

```env
# Queue Configuration (Database Driver)
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=sqlite
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90

# Cache Configuration
CACHE_DRIVER=file
CACHE_STORE=file
```

### Key Configuration Points:

1. **`QUEUE_CONNECTION=database`** - Use database queue driver
2. **`DB_QUEUE_CONNECTION=sqlite`** - Connection to use for queue
3. **`DB_QUEUE_TABLE=jobs`** - Table name for storing jobs
4. **`DB_QUEUE_RETRY_AFTER=90`** - Retry failed jobs after 90 seconds

---

## Step 3: Start Queue Worker

To process queued notifications, start the queue worker:

```bash
# Development: Process jobs one at a time
php artisan queue:work --once

# Production: Keep worker running
php artisan queue:work

# Production: Run with supervisor (recommended)
# See: https://laravel.com/docs/queues#supervisor-configuration
```

**Important:** The queue worker must be running for push notifications to be sent!

---

## Step 4: Create Push Token Update Endpoint (Optional)

To allow clients to register their push tokens, add this endpoint to AuthController:

```php
/**
 * Update user's push token (for push notifications)
 */
public function updatePushToken(Request $request)
{
    $validated = $request->validate([
        'push_token' => 'required|string|max:500',
    ]);

    $request->user()->update(['push_token' => $validated['push_token']]);

    return $this->successResponse(
        null,
        'Push token updated successfully'
    );
}
```

Add to `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/push-token', [AuthController::class, 'updatePushToken']);
});
```

---

## Step 5: Integrate with Push Service Provider

Choose your push notification service and implement the integration:

### Option A: OneSignal

1. **Install OneSignal SDK:**

    ```bash
    composer require onesignal/php-sdk
    ```

2. **Add to `.env`:**

    ```env
    ONESIGNAL_APP_ID=your_app_id
    ONESIGNAL_REST_API_KEY=your_rest_api_key
    ```

3. **Update `PushChannel.php`:**
    - Uncomment the `sendViaOneSignal()` method
    - Implement the integration as shown

### Option B: Firebase Cloud Messaging (FCM)

1. **Add to `.env`:**

    ```env
    FCM_SERVER_KEY=your_server_key
    FCM_SENDER_ID=your_sender_id
    ```

2. **Update `PushChannel.php`:**
    - Uncomment the `sendViaFCM()` method
    - Implement the integration as shown

### Option C: Web Push API

1. **Install required packages:**

    ```bash
    composer require web-push-php/web-push
    ```

2. **Implement custom push method in `PushChannel.php`**

---

## Step 6: Verify Installation

### Check Queue Table:

```bash
php artisan tinker

# View pending jobs
DB::table('jobs')->get();

# View failed jobs
DB::table('failed_jobs')->get();
```

### Check User Table:

```bash
php artisan tinker

# Verify push_token column exists
DB::table('users')->select('id', 'name', 'push_token')->get();
```

### Check Cache:

```bash
php artisan tinker

# Clear cache
Cache::clear();

# Check cache entries
Cache::get('events_list_page_1');
```

---

## Step 7: Test All Features

### Test 1: Create Booking and Verify Cache

```bash
# Terminal 1: Start queue worker
php artisan queue:work

# Terminal 2: Make requests
curl "http://localhost:8000/api/events"
# Response: Events retrieved successfully (cached)

curl "http://localhost:8000/api/events"
# Response: Events retrieved successfully (cached)
```

### Test 2: Create Event and Clear Cache

```bash
curl -X POST "http://localhost:8000/api/events" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "Test Event",
    "description": "Test Description",
    "date": "2025-12-15 19:00:00",
    "location": "Test Location"
  }'

# Cache is automatically cleared (observer triggered)
```

### Test 3: Create Booking and Send Notification

```bash
# Terminal 1: Start queue worker
php artisan queue:work

# Terminal 2: Create booking
curl -X POST "http://localhost:8000/api/tickets/1/bookings" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"quantity": 2}'

# Terminal 2: Create payment
curl -X POST "http://localhost:8000/api/bookings/1/payment" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"status": "success"}'

# Terminal 1: Watch for notification in queue worker logs
# You should see: "Push notification queued"
```

---

## Troubleshooting

### Queue Jobs Not Processing

**Problem:** Jobs pile up in the `jobs` table

**Solution:**

1. Verify queue worker is running: `php artisan queue:work`
2. Check `.env` configuration
3. Verify database connection: `php artisan db:show`

### Cache Not Working

**Problem:** Events are not being cached

**Solution:**

1. Check cache driver in `.env`: `CACHE_DRIVER=file`
2. Clear cache: `php artisan cache:clear`
3. Verify `storage/framework/cache` directory is writable

### Push Notifications Not Sent

**Problem:** Notifications not reaching devices

**Solution:**

1. Verify user has `push_token`: `DB::table('users')->where('id', 1)->first();`
2. Check queue worker logs for errors
3. Verify push service credentials in `.env`
4. Check `storage/logs/laravel.log` for error details

### Observer Not Clearing Cache

**Problem:** Cache not invalidated when events change

**Solution:**

1. Verify observer is registered: Check `AppServiceProvider.php`
2. Manually clear: `php artisan cache:clear`
3. Check for error logs: `tail -f storage/logs/laravel.log`

---

## Performance Optimization

### Recommended Settings for Production:

```env
# Use Redis for better performance
CACHE_DRIVER=redis
QUEUE_CONNECTION=database

# Database for failed jobs tracking
QUEUE_FAILED_DRIVER=database

# Increase job timeout (in seconds)
DB_QUEUE_RETRY_AFTER=300

# Use supervisor for queue worker
# See Laravel docs for supervisor configuration
```

### Monitor Queue Performance:

```bash
# View queue statistics
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Forget specific job
php artisan queue:forget {id}
```

---

## Additional Resources

-   [Laravel Notifications](https://laravel.com/docs/notifications)
-   [Laravel Queue](https://laravel.com/docs/queues)
-   [Laravel Cache](https://laravel.com/docs/cache)
-   [Laravel Observers](https://laravel.com/docs/eloquent#observers)

---

## Implementation Checklist

-   [ ] Run migrations (`php artisan migrate`)
-   [ ] Configure `.env` with queue settings
-   [ ] Add `push_token` to User model fillable
-   [ ] Start queue worker (`php artisan queue:work`)
-   [ ] Test cache with events endpoint
-   [ ] Create booking and verify notification is queued
-   [ ] Integrate with push service provider (OneSignal/FCM)
-   [ ] Setup production supervisor configuration
-   [ ] Configure monitoring and alerting
-   [ ] Test end-to-end flow

---

## Support

For issues or questions, refer to:

-   `FEATURES_IMPLEMENTATION.md` - Detailed feature documentation
-   `app/Notifications/BookingConfirmed.php` - Notification class
-   `app/Observers/EventObserver.php` - Cache observer
-   `app/Services/PaymentService.php` - Payment processing
