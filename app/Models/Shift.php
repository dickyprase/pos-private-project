<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime', 'status' => ShiftStatus::class, 'opening_cash' => 'integer', 'expected_cash' => 'integer', 'actual_cash' => 'integer', 'difference' => 'integer'];
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
