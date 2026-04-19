# Events Manager

Events Manager is a Symfony 6.4 web application for creating, browsing, and joining events. It includes authentication, event management, paid event checkout, unique ticket generation, email confirmations, and scheduled reminder emails.

## Features

- User registration and login with email verification code for sign-in
- Create, edit, delete, and browse events
- Event categories, locations, capacity limits, and attendee tracking
- Free event registration and paid event checkout flow
- Unique ticket code generation after successful payment
- Payment confirmation email with event details and ticket code
- Reminder email for upcoming events
- Home page filters by date, location, and category
- Responsive Bootstrap UI

## Requirements

- PHP 8.2 or newer
- Composer
- A database supported by Doctrine ORM
- Mail transport configured through Symfony Mailer
- Stripe secret key for paid events

## Installation

1. Clone the project and install dependencies:

```bash
composer install
```

2. Configure your environment in `.env.local`:

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/event_manager?serverVersion=8.0"
MAILER_DSN="smtp://user:password@smtp.example.com:587"
STRIPE_SECRET_KEY="sk_test_your_key_here"
STRIPE_DISABLE_SSL_VERIFY=0
```

3. Create the database and update the schema:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

4. Clear the cache if needed:

```bash
php bin/console cache:clear
```

## Running the app

Start the Symfony local server or use your preferred web server, then open the app in the browser.

If you use the Symfony CLI:

```bash
symfony server:start
```

## Main Pages

- Home page: filters upcoming and past events by date, location, and category
- Events list: shows all events with pricing and remaining seats
- Event details: shows event information, participants, and join or pay button
- Authentication pages: login, register, and verification code pages

## Payments and Tickets

Paid events use Stripe Checkout through the internal `StripeGateway` service. After payment is confirmed, the app:

- adds the user to the event attendees
- creates a unique ticket code
- sends a payment confirmation email

The checkout currency is EUR, and event prices are stored as cents.

## Reminder Emails

The reminder system is implemented as a console command:

```bash
php bin/console app:send-event-reminders --minutes=1
```

It sends reminder emails for events starting within the configured time window.

On this Windows workspace, the reminder is also wired to a scheduled task named `EventReminderMail` that runs every minute through a hidden launcher script.

## Project Structure

- `src/Controller/` handles the HTTP routes
- `src/Entity/` contains Doctrine entities for users, events, categories, locations, and tickets
- `src/Form/` contains Symfony form types
- `src/Repository/` contains Doctrine repositories
- `src/Service/` contains the Stripe gateway
- `templates/` contains Twig templates
- `migrations/` contains database migrations

## Configuration Notes

- `app.default_timezone` is set in `config/services.yaml`
- `STRIPE_DISABLE_SSL_VERIFY` can be used locally if the Stripe HTTP gateway needs relaxed SSL verification
- The app expects attendee and organizer relationships to be handled by the event controller

## Useful Commands

```bash
php bin/console doctrine:schema:validate
php bin/console cache:clear
php bin/console app:send-event-reminders --minutes=1
```

## License

Proprietary project for the event management assignment.
