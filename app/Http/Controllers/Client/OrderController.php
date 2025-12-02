<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Trip;
use App\Models\Place;
use App\Models\OrderPassenger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $client = auth()->user()->client;
        $orders = $client->orders()
            ->with(['trip.route.bus', 'orderPassengers.passenger'])
            ->latest()
            ->paginate(10);

        return view('client.orders.index', compact('orders'));
    }

    public function create(Trip $trip)
    {
        $trip->load(['route.bus', 'places']);
        $client = auth()->user()->client;
        $passengers = $client->passengers;
        $occupiedPlaces = $trip->places()->whereNotNull('passenger_id')->pluck('number_place')->toArray();
        $totalPlaces = $trip->route->bus->places;
        $availablePlaces = [];
        
        for ($i = 1; $i <= $totalPlaces; $i++) {
            if (!in_array($i, $occupiedPlaces)) {
                $availablePlaces[] = $i;
            }
        }

        return view('client.orders.create', compact('trip', 'passengers', 'availablePlaces'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'passengers' => 'required|array|min:1',
            'passengers.*.passenger_id' => 'required|exists:passengers,id',
            'passengers.*.place_number' => 'required|integer|min:1',
            'passengers.*.with_pet' => 'nullable|boolean',
        ]);

        $client = auth()->user()->client;
        $trip = Trip::findOrFail($validated['trip_id']);
        $trip->load('route');

        if (!$trip->hasAvailablePlaces(count($validated['passengers']))) {
            return back()->with('error', 'Недостаточно свободных мест')->withInput();
        }

        try {
            DB::beginTransaction();

            $basePrice = $trip->route->price;
            $totalPrice = 0;
            $orderWithPet = false;
            $travelDate = Carbon::parse($trip->date);
            $isWeekend = $travelDate->dayOfWeek == 0 || $travelDate->dayOfWeek == 6;
            $weekendMultiplier = $isWeekend ? 1.15 : 1.0;

            $order = Order::create([
                'trip_id' => $trip->id,
                'client_id' => $client->id,
                'status' => 'pending',
                'reserved_until' => Carbon::now()->addMinutes(15),
            ]);

            foreach ($validated['passengers'] as $passengerData) {
                $passenger = $client->passengers()->findOrFail($passengerData['passenger_id']);
                
                $place = Place::firstOrCreate([
                    'trip_id' => $trip->id,
                    'number_place' => $passengerData['place_number'],
                ]);

                if ($place->passenger_id !== null) {
                    throw new \Exception('Место ' . $passengerData['place_number'] . ' уже занято');
                }

                $place->passenger_id = $passenger->id;
                $place->save();

                // Calculate price for this passenger
                $passengerPrice = $basePrice;
                
                // Window seat (positions 1 and 4 in each row - edges) - +200
                $placeNumber = (int)$passengerData['place_number'];
                $seatsPerRow = 4;
                $positionInRow = (($placeNumber - 1) % $seatsPerRow) + 1;
                if ($positionInRow == 1 || $positionInRow == $seatsPerRow) {
                    $passengerPrice += 200;
                }
                
                // Pet option - +300
                $withPet = isset($passengerData['with_pet']) && $passengerData['with_pet'] == '1';
                if ($withPet) {
                    $passengerPrice += 300;
                    $orderWithPet = true;
                }
                
                // Weekend multiplier
                $passengerPrice *= $weekendMultiplier;
                $passengerPrice = round($passengerPrice, 2);
                
                $totalPrice += $passengerPrice;

                OrderPassenger::create([
                    'order_id' => $order->id,
                    'passenger_id' => $passenger->id,
                    'ticket' => Str::random(10) . '-' . $order->id,
                    'with_pet' => $withPet,
                    'price' => $passengerPrice,
                ]);
            }

            // Update order with total price
            $order->total_price = round($totalPrice, 2);
            $order->with_pet = $orderWithPet;
            $order->save();

            $trip->reservePlaces(count($validated['passengers']));

            DB::commit();

            return redirect()->route('client.orders.payment', $order)->with('success', 'Заказ успешно создан. Перейдите к оплате.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при создании заказа: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Order $order)
    {
        $client = auth()->user()->client;
        
        if ($order->client_id !== $client->id) {
            abort(403);
        }

        $order->load(['trip.route.bus', 'orderPassengers.passenger', 'trip.places']);

        return view('client.orders.show', compact('order'));
    }

    public function payment(Order $order)
    {
        $client = auth()->user()->client;
        
        if ($order->client_id !== $client->id) {
            abort(403);
        }

        // Проверяем, не истекла ли резервация
        if ($order->isExpired()) {
            $order->update(['status' => 'expired']);
            return redirect()->route('client.orders.index')->with('error', 'Время резервирования истекло. Заказ отменен.');
        }

        // Если уже оплачен, перенаправляем на страницу заказа
        if ($order->status === 'paid') {
            return redirect()->route('client.orders.show', $order)->with('info', 'Заказ уже оплачен');
        }

        $order->load(['trip.route.bus', 'orderPassengers.passenger', 'trip.places']);

        return view('client.orders.payment', compact('order'));
    }

    public function processPayment(Request $request, Order $order)
    {
        $client = auth()->user()->client;
        
        if ($order->client_id !== $client->id) {
            abort(403);
        }

        // Проверяем, не истекла ли резервация
        if ($order->isExpired()) {
            $order->update(['status' => 'expired']);
            return redirect()->route('client.orders.index')->with('error', 'Время резервирования истекло. Заказ отменен.');
        }

        // Если уже оплачен
        if ($order->status === 'paid') {
            return redirect()->route('client.orders.show', $order)->with('info', 'Заказ уже оплачен');
        }

        // 25% шанс отказа (0.25 вероятность)
        if (rand(1, 4) === 1) {
            // Заказ остается забронированным, можно попробовать снова
            return back()->with('error', 'Оплата не прошла. Попробуйте снова. У вас есть время до ' . $order->reserved_until->format('H:i'));
        }

        try {
            DB::beginTransaction();

            $order->status = 'paid';
            $order->reserved_until = null;
            $order->save();

            DB::commit();

            return redirect()->route('client.orders.show', $order)->with('success', 'Оплата прошла успешно!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при обработке оплаты: ' . $e->getMessage());
        }
    }

    public function destroy(Order $order)
    {
        $client = auth()->user()->client;
        
        if ($order->client_id !== $client->id) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $placesCount = $order->orderPassengers->count();
            Place::where('trip_id', $order->trip_id)
                ->whereIn('passenger_id', $order->orderPassengers->pluck('passenger_id'))
                ->update(['passenger_id' => null]);

            $trip = $order->trip;
            $trip->free_places += $placesCount;
            $trip->save();

            $order->delete();

            DB::commit();

            return redirect()->route('client.orders.index')->with('success', 'Заказ отменен');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при отмене заказа: ' . $e->getMessage());
        }
    }
}
