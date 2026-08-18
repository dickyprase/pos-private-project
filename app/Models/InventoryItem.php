<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['current_stock' => 'decimal:4', 'minimum_stock' => 'decimal:4', 'average_cost' => 'integer', 'allow_negative_stock' => 'boolean', 'is_active' => 'boolean'];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
