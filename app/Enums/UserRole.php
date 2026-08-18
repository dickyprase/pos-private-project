<?php

namespace App\Enums;

enum UserRole: string
{
    case OWNER = 'OWNER';
    case MANAGER = 'MANAGER';
    case CASHIER = 'CASHIER';
}
