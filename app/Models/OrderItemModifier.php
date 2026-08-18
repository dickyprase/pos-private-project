<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemModifier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['price_adjustment' => 'integer', 'quantity' => 'integer'];
    }
}
