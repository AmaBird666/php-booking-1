<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Seat;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function showBus($routeId, Request $request)
    {
        $route = Route::findOrFail($routeId);
        $seats = Seat::where('bus_id', $route->bus_id)->orderBy('number')->get();
        $travelDate = $request->get('date', Carbon::today()->format('Y-m-d'));

        return view('booking.bus', compact('route', 'seats', 'travelDate'));
    }

    public function reserve(Request $request, $seatId)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $seat = Seat::findOrFail($seatId);
        $route = Route::findOrFail($request->route_id);
        $travelDate = Carbon::parse($request->date);

        // Проверяем, не занято ли место на эту дату
        if ($seat->isBooked($travelDate->format('Y-m-d'))) {
            return back()->with('error', 'Это место уже занято на выбранную дату.');
        }

        $price = $route->price; // base_price

        // Место у окна дороже (каждое четное место)
        if ($seat->is_window) {
            $price += 200;
        }

        // Животное с доплатой
        $with_pet = $request->has('with_pet');
        if ($with_pet) {
            $price += 300;
        }

        // По выходным цена больше (суббота = 6, воскресенье = 0)
        $dayOfWeek = $travelDate->dayOfWeek;
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $price *= 1.15;
        }

        // Резервируем место на 15 минут
        $reservedUntil = Carbon::now()->addMinutes(15);

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'route_id' => $route->id,
            'seat_id' => $seat->id,
            'with_pet' => $with_pet,
            'price' => round($price, 2),
            'status' => 'pending',
            'reserved_until' => $reservedUntil,
            'travel_date' => $travelDate,
        ]);

        return redirect()->route('payment.page', $ticket->id);
    }
}
