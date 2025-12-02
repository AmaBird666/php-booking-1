<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'bus_id',
        'from_station',
        'to_station',
        'start',
        'duration',
        'price',
        'approved',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'price' => 'decimal:2',
    ];


    public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }


    public function trips()
    {
        return $this->hasMany(Trip::class, 'route_id');
    }


    public function schedule()
    {
        return $this->hasOne(RouteSchedule::class, 'route_id');
    }


    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
