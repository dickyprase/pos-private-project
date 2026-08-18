<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderNumberService
{
    public function next(): string
    {
        $prefix = config('pos.transaction_prefix', 'KP');
        $date = now()->toDateString();

        $latestNumber = Order::query()
            ->where('order_number', 'like', $prefix.'-'.now()->format('Ymd').'-%')
            ->lockForUpdate()
            ->max('order_number');
        $bootstrap = $latestNumber ? (int) substr($latestNumber, strrpos($latestNumber, '-') + 1) : 0;
        DB::table('number_sequences')->insertOrIgnore([
            'scope' => $prefix,
            'sequence_date' => $date,
            'sequence' => $bootstrap,
        ]);
        $row = DB::table('number_sequences')
            ->where('scope', $prefix)
            ->where('sequence_date', $date)
            ->lockForUpdate()
            ->first();
        $sequence = ((int) $row->sequence) + 1;
        DB::table('number_sequences')->where('id', $row->id)->update(['sequence' => $sequence]);

        return sprintf('%s-%s-%05d', $prefix, now()->format('Ymd'), $sequence);
    }
}
