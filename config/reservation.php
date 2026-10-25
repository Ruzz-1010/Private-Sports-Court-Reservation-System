<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Facility
    |--------------------------------------------------------------------------
    |
    | Operating hours are expressed in the facility timezone. A booking must
    | start and end inside the same operating window on the same day.
    |
    */

    'timezone' => env('FACILITY_TIMEZONE', 'Asia/Manila'),

    'currency' => env('FACILITY_CURRENCY', 'PHP'),

    // Printed on receipts so a downloaded document identifies the facility.
    'facility_name' => env('FACILITY_NAME', 'San Jose Private Pickleball Courts'),

    'facility_address' => env('FACILITY_ADDRESS', 'Barangay San Jose, Puerto Princesa City, Palawan'),

    'operating_hours' => [
        'start' => env('FACILITY_OPENING_HOUR', 9),   // 09:00
        'end' => env('FACILITY_CLOSING_HOUR', 21),    // 21:00
    ],

    // Days the facility is closed, using PHP day indexes (0 = Sunday).
    'closed_days' => [0],

    /*
    |--------------------------------------------------------------------------
    | Booking rules
    |--------------------------------------------------------------------------
    |
    | Booking increment is the smallest bookable slot. Minimum and maximum
    | duration must be whole multiples of the increment.
    |
    */

    'increment_minutes' => (int) env('BOOKING_INCREMENT_MINUTES', 30),

    'min_duration_minutes' => (int) env('BOOKING_MIN_MINUTES', 60),

    'max_duration_minutes' => (int) env('BOOKING_MAX_MINUTES', 240),

    // Customers may not book further ahead than this many days.
    'advance_booking_days' => (int) env('BOOKING_ADVANCE_DAYS', 30),

    // How far before the slot starts a booking is still allowed.
    'lead_time_minutes' => (int) env('BOOKING_LEAD_TIME_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Payment holds
    |--------------------------------------------------------------------------
    |
    | A pending reservation holds its slot until the deadline. The on-site hold
    | is longer because the customer must physically reach the facility, but it
    | never extends past the reservation start time.
    |
    */

    'hold_minutes' => [
        'online' => (int) env('HOLD_ONLINE_MINUTES', 15),
        'on_site' => (int) env('HOLD_ON_SITE_MINUTES', 1440),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refunds and no-show policy
    |--------------------------------------------------------------------------
    |
    | A customer who paid online and then asks for a refund gets the amount
    | back minus the administration fee (a percentage of what was paid). The
    | fee covers the payment provider charge and the slot that was held.
    |
    | A no-show is a booking the customer never showed up for. Staff record
    | these; once the limit below is reached, management may suspend the
    | account. The value is display-only: the system states the policy and
    | staff apply it, it does not ban anyone automatically.
    |
    */

    'refund' => [
        // Percentage of the paid amount kept by the facility.
        'admin_fee_percent' => (float) env('REFUND_ADMIN_FEE_PERCENT', 15),
        // Cut-off for asking for a refund before the slot starts.
        'request_deadline_hours' => (int) env('REFUND_REQUEST_DEADLINE_HOURS', 24),
    ],

    'no_show' => [
        // No-shows allowed before the account may be suspended.
        'limit_before_suspension' => (int) env('NO_SHOW_LIMIT_BEFORE_SUSPENSION', 3),
        // How long a recorded no-show stays counted against the customer.
        'counting_window_days' => (int) env('NO_SHOW_COUNTING_WINDOW_DAYS', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reservation reminders
    |--------------------------------------------------------------------------
    |
    | The scheduler emails a reminder this many minutes before a confirmed
    | reservation starts. Set it to 0 to switch reminders off entirely.
    |
    */

    'reminder' => [
        'minutes_before' => (int) env('RESERVATION_REMINDER_MINUTES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email verification
    |--------------------------------------------------------------------------
    |
    | A new account must confirm its email address with a six-digit code before
    | any customer screen opens. Codes are kept in the cache, never in the
    | database, so they expire on their own.
    |
    */

    'email_verification' => [
        // How long a code stays usable.
        'expires_minutes' => (int) env('EMAIL_VERIFICATION_EXPIRES', 15),

        // Wrong tries allowed before the code is burned.
        'max_attempts' => (int) env('EMAIL_VERIFICATION_ATTEMPTS', 5),

        // Seconds before "Send a new code" works again.
        'resend_seconds' => (int) env('EMAIL_VERIFICATION_RESEND_SECONDS', 60),

        // Digits in the code. Six is what the screen and the email assume.
        'code_length' => (int) env('EMAIL_VERIFICATION_CODE_LENGTH', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password rules
    |--------------------------------------------------------------------------
    |
    | Applied wherever Password::defaults() is used, which today means customer
    | registration. Letters plus numbers is the minimum the facility wants; the
    | stricter switches exist so they can be turned on without code changes.
    */

    'password' => [
        'min_length' => (int) env('PASSWORD_MIN_LENGTH', 8),

        // bcrypt stops reading after 72 bytes, so anything longer is not used.
        'max_length' => (int) env('PASSWORD_MAX_LENGTH', 72),

        'require_letters' => (bool) env('PASSWORD_REQUIRE_LETTERS', true),
        'require_numbers' => (bool) env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_mixed_case' => (bool) env('PASSWORD_REQUIRE_MIXED_CASE', false),
        'require_symbols' => (bool) env('PASSWORD_REQUIRE_SYMBOLS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reference numbers
    |--------------------------------------------------------------------------
    */

    'reference_prefix' => env('RESERVATION_PREFIX', 'RSRV'),

];
