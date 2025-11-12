<?php

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'admin';
    case ORGANIZER = 'organizer';
    case CUSTOMER = 'customer';
}
