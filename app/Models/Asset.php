<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = ['user_id', 'symbol', 'amount', 'locked_amount'];

    protected $casts = [
        'amount' => 'decimal:18',
        'locked_amount' => 'decimal:18'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
