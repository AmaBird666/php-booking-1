@extends('layouts.main')

@section('title', 'Заказ #' . $order->id)

@section('content')
<div class="mb-6">
    <a href="{{ route('client.orders.index') }}" class="text-indigo-600 hover:text-indigo-700">&larr; Назад к заказам</a>
</div>

<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Заказ #{{ $order->id }}</h1>
        <div>
            @if($order->status === 'pending')
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Ожидает оплаты</span>
            @elseif($order->status === 'paid')
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Оплачен</span>
            @elseif($order->status === 'expired')
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Истек</span>
            @endif
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-4">Информация о рейсе</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><strong>Маршрут:</strong> {{ $order->trip->route->from_station }} → {{ $order->trip->route->to_station }}</div>
            <div><strong>Дата:</strong> {{ $order->trip->date->format('d.m.Y') }}</div>
            <div><strong>Время:</strong> {{ $order->trip->route->start }}</div>
            <div><strong>Автобус:</strong> {{ $order->trip->route->bus->name }}</div>
            <div><strong>Базовая цена:</strong> {{ $order->trip->route->price }} ₽</div>
            @if($order->trip->date->dayOfWeek == 0 || $order->trip->date->dayOfWeek == 6)
                <div><strong>Выходной день:</strong> <span class="text-orange-600">+15%</span></div>
            @endif
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-4">Пассажиры</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Пассажир</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Место</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дополнительно</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Номер билета</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($order->orderPassengers as $op)
                    @php
                        $place = $order->trip->places()->where('passenger_id', $op->passenger_id)->first();
                    @endphp
                    <tr>
                        <td class="px-6 py-4">{{ $op->passenger->full_name }}</td>
                        <td class="px-6 py-4">
                            №{{ $place ? $place->number_place : 'N/A' }}
                            @php
                                $seatsPerRow = 4;
                                $isWindow = false;
                                if($place) {
                                    $positionInRow = (($place->number_place - 1) % $seatsPerRow) + 1;
                                    $isWindow = ($positionInRow == 1 || $positionInRow == $seatsPerRow);
                                }
                            @endphp
                            @if($isWindow)
                                <span class="text-blue-600 text-xs">(у окна)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($op->with_pet)
                                <span class="text-purple-600 text-sm">С животным</span>
                            @else
                                <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold">{{ number_format($op->price, 2) }} ₽</td>
                        <td class="px-6 py-4">{{ $op->ticket }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-between items-center">
        <div>
            @if($order->status === 'pending' && $order->reserved_until)
                <p class="text-sm text-gray-600">Время на оплату до: <span class="font-medium">{{ $order->reserved_until->format('d.m.Y H:i') }}</span></p>
            @endif
        </div>
        <div class="text-right">
            <p class="text-xl font-bold">Итого: {{ number_format($order->total_price, 2) }} ₽</p>
            @if($order->status === 'pending' && !$order->isExpired())
                <a href="{{ route('client.orders.payment', $order) }}" class="mt-3 inline-block px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium">
                    Перейти к оплате
                </a>
            @endif
        </div>
    </div>
</div>
@endsection

