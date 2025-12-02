<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'route_id',
        'seat_id',
        'price',
        'with_pet',
        'status',
        'reserved_until',
        'travel_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'with_pet' => 'boolean',
        'reserved_until' => 'datetime',
        'travel_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
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
