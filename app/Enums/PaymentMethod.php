<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Online = 'online';
    case OnSite = 'on_site';
}
