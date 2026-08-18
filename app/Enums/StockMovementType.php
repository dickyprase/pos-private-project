<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PURCHASE = 'PURCHASE';
    case SALE_USAGE = 'SALE_USAGE';
    case ADJUSTMENT = 'ADJUSTMENT';
    case WASTE = 'WASTE';
    case RETURN = 'RETURN';
    case OPNAME = 'OPNAME';
}
