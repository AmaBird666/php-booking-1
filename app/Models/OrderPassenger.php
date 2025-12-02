<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPassenger extends Model
{
    protected $fillable = [
        'ticket',
        'order_id',
        'passenger_id',
        'with_pet',
        'price',
    ];

    protected $casts = [
        'with_pet' => 'boolean',
        'price' => 'decimal:2',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }


    public function passenger()
    {
        return $this->belongsTo(Passenger::class, 'passenger_id');
    }
}
