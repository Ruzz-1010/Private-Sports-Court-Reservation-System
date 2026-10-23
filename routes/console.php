<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Unpaid reservations must release their slot promptly, because the database
| exclusion constraint cannot use now() in its predicate and will keep
| rejecting inserts until the hold is actually marked expired.
|
*/

Schedule::command('reservations:expire')
    ->everyMinute()
    ->withoutOverlapping();

// Emails a reminder a few minutes before a confirmed booking starts.
// The window is small (10 minutes by default), so this must run every minute
// to guarantee each booking is caught exactly once.
Schedule::command('reservations:remind')
    ->everyMinute()
    ->withoutOverlapping();
