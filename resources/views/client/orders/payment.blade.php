@extends('layouts.main')

@section('title', 'Оплата заказа - BusBooking')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Оплата заказа №{{ $order->id }}</h2>

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <p class="font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Информация о бронировании -->
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h3 class="font-semibold text-lg text-blue-900 mb-3">Информация о рейсе</h3>
        <div class="space-y-2 text-sm text-gray-700">
            <div class="flex justify-between">
                <span class="font-medium">Маршрут:</span>
                <span>{{ $order->trip->route->from_station }} → {{ $order->trip->route->to_station }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Дата поездки:</span>
                <span>{{ $order->trip->date->format('d.m.Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Время отправления:</span>
                <span>{{ $order->trip->route->start }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Автобус:</span>
                <span>{{ $order->trip->route->bus->name }}</span>
            </div>
        </div>
    </div>

    <!-- Пассажиры и места -->
    <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
        <h3 class="font-semibold text-lg text-gray-900 mb-3">Пассажиры и места</h3>
        <div class="space-y-3">
            @foreach($order->orderPassengers as $op)
                @php
                    $place = $order->trip->places()->where('passenger_id', $op->passenger_id)->first();
                @endphp
                <div class="bg-white p-3 rounded border border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium">{{ $op->passenger->full_name }}</p>
                            <p class="text-sm text-gray-600">Место №{{ $place ? $place->number_place : 'N/A' }}
                                @php
                                    $seatsPerRow = 4;
                                    $isWindow = false;
                                    if($place) {
                                        $positionInRow = (($place->number_place - 1) % $seatsPerRow) + 1;
                                        $isWindow = ($positionInRow == 1 || $positionInRow == $seatsPerRow);
                                    }
                                @endphp
                                @if($isWindow)
                                    <span class="text-blue-600">(у окна)</span>
                                @endif
                            </p>
                            @if($op->with_pet)
                                <p class="text-sm text-purple-600">С животным</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ number_format($op->price, 2) }} ₽</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Таймер резервирования -->
    @if($order->reserved_until)
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg text-yellow-900 mb-1">Время на оплату</h3>
                    <p class="text-sm text-gray-600">Заказ зарезервирован до:</p>
                </div>
                <div class="text-right">
                    <div id="timer" class="text-2xl font-bold text-yellow-700"></div>
                    <div class="text-xs text-gray-500 mt-1">осталось времени</div>
                </div>
            </div>
            <div class="mt-3 w-full bg-yellow-200 rounded-full h-2">
                <div id="progressBar" class="bg-yellow-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
            </div>
        </div>
    @endif

    <!-- Стоимость -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h3 class="font-semibold text-lg text-gray-900 mb-3">Детализация стоимости</h3>
        <div class="space-y-2 text-sm">
            @php
                $basePrice = $order->trip->route->price;
                $isWeekend = $order->trip->date->dayOfWeek == 0 || $order->trip->date->dayOfWeek == 6;
            @endphp
            <div class="flex justify-between">
                <span>Базовая цена за место:</span>
                <span>{{ number_format($basePrice, 2) }} ₽</span>
            </div>
            @php
                $windowSeatsCount = 0;
                $petCount = 0;
                $seatsPerRow = 4;
                foreach($order->orderPassengers as $op) {
                    $place = $order->trip->places()->where('passenger_id', $op->passenger_id)->first();
                    // Window seats are positions 1 and 4 in each row (edges)
                    if($place) {
                        $positionInRow = (($place->number_place - 1) % $seatsPerRow) + 1;
                        if($positionInRow == 1 || $positionInRow == $seatsPerRow) {
                            $windowSeatsCount++;
                        }
                    }
                    if($op->with_pet) {
                        $petCount++;
                    }
                }
            @endphp
            @if($windowSeatsCount > 0)
                <div class="flex justify-between text-blue-600">
                    <span>+ Места у окна ({{ $windowSeatsCount }} × 200 ₽):</span>
                    <span>{{ number_format($windowSeatsCount * 200, 2) }} ₽</span>
                </div>
            @endif
            @if($petCount > 0)
                <div class="flex justify-between text-purple-600">
                    <span>+ Проезд с животным ({{ $petCount }} × 300 ₽):</span>
                    <span>{{ number_format($petCount * 300, 2) }} ₽</span>
                </div>
            @endif
            @if($isWeekend)
                <div class="flex justify-between text-orange-600">
                    <span>+ Выходной день (15%):</span>
                    <span>{{ number_format(($order->total_price - ($basePrice * $order->orderPassengers->count() + $windowSeatsCount * 200 + $petCount * 300)) / 1.15 * 0.15, 2) }} ₽</span>
                </div>
            @endif
            <div class="border-t border-gray-300 pt-2 mt-2">
                <div class="flex justify-between font-bold text-lg">
                    <span>Итого к оплате:</span>
                    <span class="text-indigo-600">{{ number_format($order->total_price, 2) }} ₽</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Форма оплаты -->
    <form method="POST" action="{{ route('client.orders.payment.process', $order->id) }}" id="paymentForm">
        @csrf
        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold mb-3">Данные для оплаты</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Номер карты</label>
                    <input type="text" name="card_number" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="0000 0000 0000 0000" maxlength="19" pattern="[0-9\s]{13,19}">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Срок действия</label>
                        <input type="text" name="expiry" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="MM/YY" maxlength="5" pattern="[0-9]{2}/[0-9]{2}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                        <input type="text" name="cvv" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="123" maxlength="3" pattern="[0-9]{3}">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" id="payButton" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
            Оплатить {{ number_format($order->total_price, 2) }} ₽
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('client.orders.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Вернуться к заказам</a>
    </div>
</div>

@if($order->reserved_until)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reservedUntil = new Date('{{ $order->reserved_until->toIso8601String() }}').getTime();
    const timerElement = document.getElementById('timer');
    const progressBar = document.getElementById('progressBar');
    const paymentForm = document.getElementById('paymentForm');
    const payButton = document.getElementById('payButton');
    
    function updateTimer() {
        const now = new Date().getTime();
        const distance = reservedUntil - now;
        
        if (distance < 0) {
            timerElement.textContent = '00:00';
            progressBar.style.width = '0%';
            payButton.disabled = true;
            payButton.textContent = 'Время истекло';
            payButton.classList.add('bg-gray-400', 'cursor-not-allowed');
            
            // Перенаправляем на главную страницу через 2 секунды
            setTimeout(() => {
                window.location.href = '{{ route("client.orders.index") }}';
            }, 2000);
            return;
        }
        
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        timerElement.textContent = 
            String(minutes).padStart(2, '0') + ':' + 
            String(seconds).padStart(2, '0');
        
        // Обновляем прогресс-бар (15 минут = 900000 мс)
        const totalTime = 15 * 60 * 1000;
        const elapsed = totalTime - distance;
        const progress = Math.max(0, Math.min(100, (elapsed / totalTime) * 100));
        progressBar.style.width = (100 - progress) + '%';
        
        // Меняем цвет при приближении к истечению
        if (distance < 5 * 60 * 1000) { // Меньше 5 минут
            timerElement.classList.remove('text-yellow-700');
            timerElement.classList.add('text-red-600');
            progressBar.classList.remove('bg-yellow-600');
            progressBar.classList.add('bg-red-600');
        }
    }
    
    updateTimer();
    const interval = setInterval(updateTimer, 1000);
    
    // Очистка интервала при уходе со страницы
    window.addEventListener('beforeunload', function() {
        clearInterval(interval);
    });
});
</script>
@endif
@endsection

