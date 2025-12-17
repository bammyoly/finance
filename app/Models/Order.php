<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const OPEN = 1;
    const FILLED = 2;
    const CANCELLED = 3;
    
    protected $fillable = ['user_id', 'symbol', 'side', 'price', 'amount', 'status', 'locked_usd'];

    protected $casts = [
        'price' => 'decimal:2',
        'amount' => 'decimal:18',
        'locked_usd' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
