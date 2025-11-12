# 🎟️ Laravel 12 Event Booking Platform

A Laravel 12 web application that manages user registration, event creation, ticket booking, and payment processing — complete with **push notifications**, **queued jobs**, **caching**, and **automated tests**.

---

## 🚀 Features

-   **🧑‍💻 User Authentication**: Secure user registration, login, and logout functionalities with role-based access control (`customer`, `organizer`). Includes push token management for notifications.
-   **📅 Event Management**: Organizers can create, view, update, and delete events. Customers can browse available events.
-   **🎫 Ticket Booking**: Customers can book tickets for events, with checks for ticket availability and prevention of double-booking.
-   **💳 Payment Processing**: Handles payment simulations, updating booking statuses, and triggering notifications upon successful payment.
-   **🔔 Push Notifications (FCM)**: Customers receive real-time push notifications via Firebase Cloud Messaging (FCM) when their bookings are confirmed. Supports both Legacy and v1 APIs.
-   **🧵 Queued Jobs**: All notifications are dispatched asynchronously using Laravel's queue system with the `database` driver, ensuring responsiveness and reliability.
-   **⚡ Event Caching**: Frequently accessed event lists are cached for 10 minutes, significantly improving performance. The cache is automatically invalidated upon event creation, update, or deletion.
-   **✅ Comprehensive Testing**: Robust suite of feature and unit tests for core functionalities, aiming for 85%+ code coverage across controllers and services.

---

## ⚙️ Requirements

| Component | Version                    |
| --------- | -------------------------- |
| PHP       | 8.2+                       |
| Laravel   | 12.x                       |
| Composer  | 2.x                        |
| MySQL     | 8+                         |
| Node.js   | 18+ (optional, for assets) |

---

## 🧰 Installation & Setup

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/yourusername/laravel-event-booking.git
cd laravel-event-booking
```

### 2️⃣ Install Dependencies

Use the provided `composer setup` script to install PHP dependencies, initialize `.env`, generate app key, run migrations, install NPM dependencies, and build assets:

```bash
composer setup
```

**Note**: This script will create a `.env` file from `.env.example` if one doesn't exist. You will need to configure it as described in the next step.

### 3️⃣ Environment Configuration (`.env`)

After running `composer setup`, open the newly created or existing `.env` file and configure your database connection. For push notifications, you'll need to add your Firebase Cloud Messaging (FCM) credentials. Refer to these guides for detailed setup:

-   [**ENV_SETUP_INSTRUCTIONS.md**](ENV_SETUP_INSTRUCTIONS.md): Comprehensive guide for all environment variables.
-   [**QUICK_ENV_CONFIG.md**](QUICK_ENV_CONFIG.md): A concise, copy-paste friendly `.env` configuration for FCM.

### 4️⃣ Set up Database Queue

To enable asynchronous notification processing, set up the database queue:

1.  Ensure `QUEUE_CONNECTION=database` in your `.env` file.
2.  Run the migration to create the `jobs` table (usually done by `composer setup`, but can be run manually if needed):
    ```bash
    php artisan queue:table
    php artisan migrate
    ```
3.  Start the queue worker to process jobs:
    ```bash
    php artisan queue:work
    ```
    For development, you can run `composer dev` which includes a queue listener.

---

## 🧪 Running Tests & Coverage

To run all feature and unit tests, and generate a code coverage report (target 85%+):

```bash
composer test
```

This command will output a summary to your terminal and generate an HTML coverage report in the `./html-coverage` directory.

---

## 🗺️ System Overview & API Endpoints

For a high-level overview of the system architecture and a breakdown of key API endpoints, refer to:

-   [**SYSTEM_OVERVIEW.md**](SYSTEM_OVERVIEW.md)

---
