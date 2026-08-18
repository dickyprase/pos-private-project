<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tax_rate' => 'decimal:2', 'service_charge_rate' => 'decimal:2', 'allow_negative_stock' => 'boolean', 'qris_enabled' => 'boolean', 'tax_enabled' => 'boolean'];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['store_name' => 'Kopi Senja']);
    }
}
