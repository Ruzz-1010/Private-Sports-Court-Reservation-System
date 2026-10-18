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

    'operating_hours' => [
        'start' => env('FACILITY_OPENING_HOUR', 6),   // 06:00
        'end' => env('FACILITY_CLOSING_HOUR', 22),    // 22:00
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
    | Reference numbers
    |--------------------------------------------------------------------------
    */

    'reference_prefix' => env('RESERVATION_PREFIX', 'RSRV'),

];
