<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case QRIS = 'QRIS';
    case DEBIT_CARD = 'DEBIT_CARD';
    case CREDIT_CARD = 'CREDIT_CARD';
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case EWALLET = 'EWALLET';
    case OTHER = 'OTHER';
}
