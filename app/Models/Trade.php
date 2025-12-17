<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = ['buy_order_id','sell_order_id','symbol','price','amount','usd_volume','fee_usd'];

    protected $casts = [
        'price' => 'decimal:2',
        'amount' => 'decimal:18',
        'usd_volume' => 'decimal:2',
        'fee_usd' => 'decimal:2',
    ];
}
