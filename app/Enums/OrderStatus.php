<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT = 'DRAFT';
    case HELD = 'HELD';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';
}
