<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = ['bus_id', 'number', 'is_window', 'allows_pet'];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function isBooked($travelDate = null)
    {
        $query = $this->tickets()->whereIn('status', ['pending', 'paid']);
        
        if ($travelDate) {
            $query->where('travel_date', $travelDate);
        }
        
        // Проверяем, не истекла ли резервация
        $query->where(function($q) {
            $q->where('status', 'paid')
              ->orWhere(function($q2) {
                  $q2->where('status', 'pending')
                     ->where('reserved_until', '>', now());
              });
        });
        
        return $query->exists();
    }
}
