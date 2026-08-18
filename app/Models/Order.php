<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'order_type' => OrderType::class, 'paid_at' => 'datetime', 'subtotal' => 'integer', 'discount_total' => 'integer', 'tax_total' => 'integer', 'service_charge_total' => 'integer', 'rounding_total' => 'integer', 'grand_total' => 'integer'];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
