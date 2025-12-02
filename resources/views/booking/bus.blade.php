@extends('layouts.main')

@section('title', 'Выбор места - BusBooking')

@section('content')
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Выбор места: {{ $route->from_station }} → {{ $route->to_station }}</h2>
    
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Дата поездки:</label>
        <input type="date" id="travelDate" value="{{ $travelDate }}" class="border border-gray-300 rounded-md px-3 py-2" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
    </div>

    <form action="" method="POST" id="bookingForm">
        @csrf
        <input type="hidden" name="route_id" value="{{ $route->id }}">
        <input type="hidden" name="date" id="formDate" value="{{ $travelDate }}">
        <input type="hidden" name="seat_id" id="selectedSeatId">

        <!-- Легенда -->
        <div class="mb-6 flex flex-wrap gap-4 justify-center p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-green-100 border-2 border-green-500 rounded"></div>
                <span class="text-sm">Свободно</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-100 border-2 border-blue-500 rounded"></div>
                <span class="text-sm">У окна (+200 ₽)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-purple-100 border-2 border-purple-500 rounded"></div>
                <span class="text-sm">С животным (+300 ₽)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gray-300 border-2 border-gray-500 rounded"></div>
                <span class="text-sm">Занято</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-yellow-100 border-2 border-yellow-500 rounded"></div>
                <span class="text-sm">Выбрано</span>
            </div>
        </div>

        <!-- Схема автобуса -->
        <div class="bus-container mb-6">
            <!-- Кабина водителя -->
            <div class="text-center mb-4">
                <div class="inline-block bg-gray-800 text-white px-6 py-2 rounded-t-lg">
                    <span class="text-sm">🚌 Кабина водителя</span>
                </div>
            </div>

            <!-- Места автобуса -->
            <div class="bus-layout relative">
                <!-- Левая сторона (окна) -->
                <div class="bus-side bus-left">
                    @php
                        $seatsPerRow = 2;
                        $totalRows = ceil($seats->count() / 4);
                        $leftSeats = $seats->filter(function($seat) {
                            return $seat->number % 4 == 1 || ($seat->number % 4 == 2 && $seat->number <= $seats->count() / 2);
                        })->sortBy('number');
                    @endphp
                    
                    @foreach($leftSeats as $seat)
                        @php
                            $isBooked = $seat->isBooked($travelDate);
                            $row = ceil($seat->number / 4);
                        @endphp
                        <div class="seat-wrapper" data-seat-id="{{ $seat->id }}" data-seat-number="{{ $seat->number }}" data-is-window="{{ $seat->is_window ? '1' : '0' }}" data-allows-pet="{{ $seat->allows_pet ? '1' : '0' }}">
                            <label class="seat-label 
                                {{ $seat->is_window ? 'window-seat' : '' }}
                                {{ $seat->allows_pet ? 'pet-seat' : '' }}
                                {{ $isBooked ? 'booked' : 'available' }}
                                {{ $seat->number % 2 == 1 ? 'seat-left' : 'seat-right' }}"
                                data-seat-id="{{ $seat->id }}">
                                @if(!$isBooked)
                                    <input type="radio" name="seat_id" value="{{ $seat->id }}" class="seat-radio" data-seat-id="{{ $seat->id }}">
                                @endif
                                <div class="seat-number">{{ $seat->number }}</div>
                                @if($seat->is_window)
                                    <div class="seat-icon">🪟</div>
                                @endif
                                @if($seat->allows_pet)
                                    <div class="seat-icon">🐾</div>
                                @endif
                            </label>
                        </div>
                    @endforeach
                </div>

                <!-- Проход -->
                <div class="bus-aisle">
                    <div class="aisle-line"></div>
                </div>

                <!-- Правая сторона (проход) -->
                <div class="bus-side bus-right">
                    @php
                        $rightSeats = $seats->filter(function($seat) use ($leftSeats) {
                            return !$leftSeats->contains('id', $seat->id);
                        })->sortBy('number');
                    @endphp
                    
                    @foreach($rightSeats as $seat)
                        @php
                            $isBooked = $seat->isBooked($travelDate);
                        @endphp
                        <div class="seat-wrapper" data-seat-id="{{ $seat->id }}" data-seat-number="{{ $seat->number }}" data-is-window="{{ $seat->is_window ? '1' : '0' }}" data-allows-pet="{{ $seat->allows_pet ? '1' : '0' }}">
                            <label class="seat-label 
                                {{ $seat->is_window ? 'window-seat' : '' }}
                                {{ $seat->allows_pet ? 'pet-seat' : '' }}
                                {{ $isBooked ? 'booked' : 'available' }}
                                {{ $seat->number % 2 == 0 ? 'seat-left' : 'seat-right' }}"
                                data-seat-id="{{ $seat->id }}">
                                @if(!$isBooked)
                                    <input type="radio" name="seat_id" value="{{ $seat->id }}" class="seat-radio" data-seat-id="{{ $seat->id }}">
                                @endif
                                <div class="seat-number">{{ $seat->number }}</div>
                                @if($seat->is_window)
                                    <div class="seat-icon">🪟</div>
                                @endif
                                @if($seat->allows_pet)
                                    <div class="seat-icon">🐾</div>
                                @endif
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Задняя часть автобуса -->
            <div class="text-center mt-4">
                <div class="inline-block bg-gray-300 px-6 py-2 rounded-b-lg">
                    <span class="text-sm text-gray-600">Задняя часть</span>
                </div>
            </div>
        </div>

        <!-- Дополнительные опции -->
        <div class="mb-6 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-200 rounded-lg">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Дополнительные опции:</h3>
            <label class="flex items-center gap-3 cursor-pointer p-3 bg-white rounded-lg hover:bg-purple-50 transition-colors">
                <input type="checkbox" name="with_pet" id="withPet" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-purple-500">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🐾</span>
                    <div>
                        <span class="text-sm font-medium text-gray-900 block">Путешествую с животным</span>
                        <span class="text-xs text-purple-600 font-semibold">+300 ₽</span>
                    </div>
                </div>
            </label>
        </div>

        <!-- Информация о цене -->
        <div id="priceInfo" class="mb-6 p-6 bg-gradient-to-br from-indigo-50 to-blue-50 border-2 border-indigo-200 rounded-lg shadow-md hidden">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span>💰</span>
                <span>Расчет стоимости</span>
            </h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-700">Базовая цена:</span>
                    <span class="font-semibold text-gray-900"><span id="basePrice">{{ $route->price }}</span> ₽</span>
                </div>
                <div id="windowPrice" class="hidden flex justify-between items-center py-2 border-b border-blue-200">
                    <span class="text-blue-700 flex items-center gap-2">
                        <span>🪟</span>
                        <span>Место у окна:</span>
                    </span>
                    <span class="font-semibold text-blue-600">+200 ₽</span>
                </div>
                <div id="petPrice" class="hidden flex justify-between items-center py-2 border-b border-purple-200">
                    <span class="text-purple-700 flex items-center gap-2">
                        <span>🐾</span>
                        <span>С животным:</span>
                    </span>
                    <span class="font-semibold text-purple-600">+300 ₽</span>
                </div>
                <div id="weekendPrice" class="hidden flex justify-between items-center py-2 border-b border-orange-200">
                    <span class="text-orange-700 flex items-center gap-2">
                        <span>📅</span>
                        <span>Выходной день (15%):</span>
                    </span>
                    <span class="font-semibold text-orange-600">+<span id="weekendAmount"></span> ₽</span>
                </div>
                <div class="mt-4 pt-3 border-t-2 border-indigo-300 flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-900">Итого к оплате:</span>
                    <span class="text-2xl font-bold text-indigo-600"><span id="totalPrice">0</span> ₽</span>
                </div>
            </div>
        </div>

        <button type="submit" id="submitBtn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
            Забронировать место
        </button>
    </form>
</div>

<style>
.bus-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.bus-layout {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    margin: 2rem 0;
}

.bus-side {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.bus-aisle {
    width: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.aisle-line {
    width: 4px;
    height: 100%;
    background: repeating-linear-gradient(
        to bottom,
        #d1d5db 0px,
        #d1d5db 10px,
        transparent 10px,
        transparent 20px
    );
}

.seat-wrapper {
    position: relative;
}

.seat-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    aspect-ratio: 1;
    border: 3px solid #4b5563;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    background: #f3f4f6;
    position: relative;
}

.seat-label.available:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.seat-label.window-seat {
    background: #dbeafe;
    border-color: #3b82f6;
}

.seat-label.pet-seat {
    background: #f3e8ff;
    border-color: #a855f7;
}

.seat-label.booked {
    background: #d1d5db;
    border-color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat-label:has(input:checked) {
    background: #fef3c7;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.3);
}

.seat-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.seat-number {
    font-weight: bold;
    font-size: 1.1rem;
    color: #1f2937;
}

.seat-icon {
    font-size: 0.8rem;
    margin-top: 2px;
}

@media (max-width: 768px) {
    .bus-layout {
        flex-direction: column;
        gap: 1rem;
    }
    
    .bus-aisle {
        width: 100%;
        height: 20px;
    }
    
    .aisle-line {
        width: 100%;
        height: 4px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const travelDateInput = document.getElementById('travelDate');
    const formDateInput = document.getElementById('formDate');
    const form = document.getElementById('bookingForm');
    const seatRadios = document.querySelectorAll('.seat-radio');
    const submitBtn = document.getElementById('submitBtn');
    const priceInfo = document.getElementById('priceInfo');
    const basePrice = {{ $route->price }};
    const windowPrice = 200;
    const petPrice = 300;
    
    // Обновление даты в форме
    travelDateInput.addEventListener('change', function() {
        formDateInput.value = this.value;
        // Перезагружаем страницу с новой датой
        window.location.href = '{{ route("route.bus", $route->id) }}?date=' + this.value;
    });
    
    // Обработка выбора места
    seatRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updatePrice();
            submitBtn.disabled = false;
            document.getElementById('selectedSeatId').value = this.value;
            
            // Обновляем визуальное выделение
            document.querySelectorAll('.seat-label').forEach(label => {
                label.classList.remove('selected');
            });
            this.closest('.seat-label').classList.add('selected');
        });
    });
    
    // Обновление цены при изменении опций
    document.getElementById('withPet').addEventListener('change', updatePrice);
    
    function updatePrice() {
        const selectedSeat = document.querySelector('.seat-radio:checked');
        if (!selectedSeat) {
            priceInfo.classList.add('hidden');
            return;
        }
        
        priceInfo.classList.remove('hidden');
        
        const seatWrapper = selectedSeat.closest('.seat-wrapper');
        const isWindow = seatWrapper.dataset.isWindow === '1';
        const withPet = document.getElementById('withPet').checked;
        const travelDate = new Date(travelDateInput.value);
        const dayOfWeek = travelDate.getDay(); // 0 = воскресенье, 6 = суббота
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
        
        let totalPrice = basePrice;
        
        // Место у окна
        if (isWindow) {
            totalPrice += windowPrice;
            document.getElementById('windowPrice').classList.remove('hidden');
        } else {
            document.getElementById('windowPrice').classList.add('hidden');
        }
        
        // Животное
        if (withPet) {
            totalPrice += petPrice;
            document.getElementById('petPrice').classList.remove('hidden');
        } else {
            document.getElementById('petPrice').classList.add('hidden');
        }
        
        // Выходные
        if (isWeekend) {
            const weekendIncrease = totalPrice * 0.15;
            totalPrice *= 1.15;
            document.getElementById('weekendAmount').textContent = weekendIncrease.toFixed(2);
            document.getElementById('weekendPrice').classList.remove('hidden');
        } else {
            document.getElementById('weekendPrice').classList.add('hidden');
        }
        
        document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);
    }
    
    // Обновление формы при отправке
    form.addEventListener('submit', function(e) {
        const selectedSeat = document.querySelector('.seat-radio:checked');
        if (!selectedSeat) {
            e.preventDefault();
            alert('Пожалуйста, выберите место');
            return;
        }
        
        // Устанавливаем правильный action формы
        form.action = '{{ url("/seat") }}/' + selectedSeat.value + '/reserve';
    });
});
</script>
@endsection
