<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    protected $fillable = [
        'trip_id',
        'client_id',
        'total_price',
        'with_pet',
        'status',
        'reserved_until',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'with_pet' => 'boolean',
        'reserved_until' => 'datetime',
    ];


    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    
    public function orderPassengers()
    {
        return $this->hasMany(OrderPassenger::class, 'order_id');
    }


    public function passengers()
    {
        return $this->belongsToMany(Passenger::class, 'order_passengers', 'order_id', 'passenger_id')
            ->withPivot('ticket')
            ->withTimestamps();
    }

    public function isExpired(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if (!$this->reserved_until) {
            return false;
        }

        return Carbon::now()->greaterThan($this->reserved_until);
    }

    public function isReserved(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
