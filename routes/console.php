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
