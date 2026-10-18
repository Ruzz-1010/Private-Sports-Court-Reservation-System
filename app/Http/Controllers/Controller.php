<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Provides $this->authorize() for policies and $this->deny() for guards.
    use AuthorizesRequests;
}
