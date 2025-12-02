@extends('layouts.main')

@section('title', 'Создание заказа')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Создание заказа</h1>
</div>

<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">Информация о рейсе</h2>
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div><strong>Маршрут:</strong> {{ $trip->route->from_station }} → {{ $trip->route->to_station }}</div>
        <div><strong>Дата:</strong> {{ $trip->date->format('d.m.Y') }}</div>
        <div><strong>Время:</strong> {{ $trip->route->start }}</div>
        <div><strong>Автобус:</strong> {{ $trip->route->bus->name }}</div>
        <div><strong>Цена:</strong> {{ $trip->route->price }} ₽</div>
        <div><strong>Свободных мест:</strong> {{ $trip->free_places }}</div>
    </div>
</div>

<form method="POST" action="{{ route('client.orders.store') }}" class="space-y-6">
    @csrf
    <input type="hidden" name="trip_id" value="{{ $trip->id }}">

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Выбор пассажиров</h2>

        @if($passengers->count() == 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
                <p>У вас нет добавленных пассажиров. <a href="{{ route('client.passengers.index') }}" class="text-indigo-600 underline">Добавить пассажира</a></p>
            </div>
        @endif

        <div id="passenger-container" class="space-y-4 mb-6">
            <div class="passenger-row border border-gray-200 rounded-lg p-4" data-passenger-index="0">
                <div class="flex gap-4 mb-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Пассажир</label>
                        <select name="passengers[0][passenger_id]" required class="passenger-select w-full px-3 py-2 border border-gray-300 rounded-md" onchange="onPassengerChange(0)">
                            <option value="">Выберите пассажира</option>
                            @foreach($passengers as $passenger)
                                <option value="{{ $passenger->id }}">{{ $passenger->full_name }} ({{ $passenger->passport }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Выбранное место</label>
                        <div class="flex gap-2">
                            <div class="selected-place-display flex-1 px-3 py-2 border-2 border-gray-300 rounded-md bg-gray-50 text-center text-gray-500" id="selected-place-0">
                                Не выбрано
                            </div>
                            <button type="button" onclick="clearPlace(0)" class="clear-place-btn px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-md text-sm hidden" id="clear-place-0">
                                ✕
                            </button>
                        </div>
                        <input type="hidden" name="passengers[0][place_number]" class="place-input" value="">
                    </div>
                </div>
                <div class="flex items-center mb-2">
                    <input type="checkbox" name="passengers[0][with_pet]" id="passenger_0_with_pet" value="1" class="pet-checkbox mr-2 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" onchange="calculateTotal()">
                    <label for="passenger_0_with_pet" class="text-sm text-gray-700">Проезд с животным (+300 ₽)</label>
                </div>
                <div class="text-sm text-gray-600">
                    <span class="passenger-price">Цена: <span class="font-semibold">{{ number_format($trip->route->price, 2) }}</span> ₽</span>
                </div>
            </div>
        </div>

        @if($passengers->count() > 0)
            <button type="button" onclick="addPassenger()" class="mb-6 text-indigo-600 hover:text-indigo-700">
                + Добавить пассажира
            </button>
        @endif
    </div>

    <!-- Схема автобуса -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Выберите места в автобусе</h2>
        
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
                <div class="w-8 h-8 bg-gray-300 border-2 border-gray-500 rounded"></div>
                <span class="text-sm">Занято</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-yellow-100 border-2 border-yellow-500 rounded"></div>
                <span class="text-sm">Выбрано</span>
            </div>
        </div>

        <!-- Схема автобуса -->
        <div class="bus-container">
            <!-- Кабина водителя -->
            <div class="text-center mb-4">
                <div class="inline-block bg-gray-800 text-white px-6 py-2 rounded-t-lg">
                    <span class="text-sm">🚌 Кабина водителя</span>
                </div>
            </div>

            <!-- Места автобуса -->
            <div class="bus-layout">
                @php
                    $totalPlaces = $trip->route->bus->places;
                    $occupiedPlaces = $trip->places()->whereNotNull('passenger_id')->pluck('number_place')->toArray();
                    $seatsPerRow = 4; // 2+2 конфигурация
                    $totalRows = ceil($totalPlaces / $seatsPerRow);
                    
                    // Распределение мест: в каждом ряду 4 места
                    // Левая сторона (окна): места 1, 2, 5, 6, 9, 10... (нечетные ряды: 1,2; четные ряды: 5,6...)
                    // Правая сторона (проход): места 3, 4, 7, 8, 11, 12...
                    $leftSidePlaces = [];
                    $rightSidePlaces = [];
                    
                    for ($i = 1; $i <= $totalPlaces; $i++) {
                        $row = ceil($i / $seatsPerRow);
                        $positionInRow = (($i - 1) % $seatsPerRow) + 1;
                        
                        // В каждом ряду: позиции 1,2 - левая сторона, позиции 3,4 - правая сторона
                        if ($positionInRow <= 2) {
                            $leftSidePlaces[] = $i;
                        } else {
                            $rightSidePlaces[] = $i;
                        }
                    }
                @endphp

                <!-- Левая сторона (окна) -->
                <div class="bus-side bus-left">
                    @foreach($leftSidePlaces as $placeNum)
                        @php
                            $isOccupied = in_array($placeNum, $occupiedPlaces);
                            $positionInRow = (($placeNum - 1) % $seatsPerRow) + 1;
                            $isWindow = ($positionInRow == 1); // Позиция 1 в ряду - левый край (окно)
                        @endphp
                        <div class="seat-wrapper" 
                             data-place-number="{{ $placeNum }}" 
                             data-is-window="{{ $isWindow ? '1' : '0' }}"
                             data-is-occupied="{{ $isOccupied ? '1' : '0' }}">
                            <button type="button" 
                                    class="seat-button {{ $isWindow ? 'window-seat' : '' }} {{ $isOccupied ? 'booked' : 'available' }}"
                                    data-place-number="{{ $placeNum }}"
                                    {{ $isOccupied ? 'disabled' : '' }}>
                                <div class="seat-number">{{ $placeNum }}</div>
                                @if($isWindow)
                                    <div class="seat-icon">🪟</div>
                                @endif
                            </button>
                        </div>
                    @endforeach
                </div>

                <!-- Проход -->
                <div class="bus-aisle">
                    <div class="aisle-line"></div>
                </div>

                <!-- Правая сторона (проход) -->
                <div class="bus-side bus-right">
                    @foreach($rightSidePlaces as $placeNum)
                        @php
                            $isOccupied = in_array($placeNum, $occupiedPlaces);
                            $positionInRow = (($placeNum - 1) % $seatsPerRow) + 1;
                            $isWindow = ($positionInRow == $seatsPerRow); // Позиция 4 в ряду - правый край (окно)
                        @endphp
                        <div class="seat-wrapper" 
                             data-place-number="{{ $placeNum }}" 
                             data-is-window="{{ $isWindow ? '1' : '0' }}"
                             data-is-occupied="{{ $isOccupied ? '1' : '0' }}">
                            <button type="button" 
                                    class="seat-button {{ $isWindow ? 'window-seat' : '' }} {{ $isOccupied ? 'booked' : 'available' }}"
                                    data-place-number="{{ $placeNum }}"
                                    {{ $isOccupied ? 'disabled' : '' }}>
                                <div class="seat-number">{{ $placeNum }}</div>
                                @if($isWindow)
                                    <div class="seat-icon">🪟</div>
                                @endif
                            </button>
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

        <div class="mt-4 text-center text-sm text-gray-600" id="seat-selection-info">
            <p>Выберите пассажира, затем кликните на место в автобусе</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-600">Итого к оплате:</p>
                <p class="text-3xl font-bold text-indigo-600" id="total-price">{{ number_format($trip->route->price, 2) }} ₽</p>
            </div>
        </div>
        <div class="mt-4 text-xs text-gray-500">
            <p>* Места у окна (крайние места в ряду): +200 ₽</p>
            <p>* Проезд с животным: +300 ₽</p>
            @php
                $isWeekend = $trip->date->dayOfWeek == 0 || $trip->date->dayOfWeek == 6;
            @endphp
            @if($isWeekend)
                <p class="text-orange-600 font-semibold">* Выходной день: цена увеличена на 15%</p>
            @endif
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('client.trips.index') }}" class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Отмена
        </a>
        <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium">
            Оформить заказ
        </button>
    </div>
</form>

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

.seat-button {
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
    padding: 0;
}

.seat-button.available:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.seat-button.window-seat {
    background: #dbeafe;
    border-color: #3b82f6;
}

.seat-button.booked {
    background: #d1d5db;
    border-color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat-button.selected {
    background: #fef3c7;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.3);
}

.seat-button:disabled {
    cursor: not-allowed;
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

.selected-place-display {
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.selected-place-display.has-place {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
    font-weight: bold;
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
let passengerIndex = 1;
let currentSelectedPassengerIndex = null;
let selectedPlaces = new Set();
const basePrice = {{ $trip->route->price }};
const isWeekend = {{ ($trip->date->dayOfWeek == 0 || $trip->date->dayOfWeek == 6) ? 'true' : 'false' }};
const weekendMultiplier = 1.15;
const windowSeatPrice = 200;
const petPrice = 300;

// Инициализация обработчиков кликов по местам
document.addEventListener('DOMContentLoaded', function() {
    const seatButtons = document.querySelectorAll('.seat-button.available');
    seatButtons.forEach(button => {
        button.addEventListener('click', function() {
            const placeNumber = parseInt(this.dataset.placeNumber);
            selectPlaceForPassenger(placeNumber);
        });
    });
});

function onPassengerChange(index) {
    const select = document.querySelector(`[data-passenger-index="${index}"] .passenger-select`);
    if (select && select.value) {
        currentSelectedPassengerIndex = index;
        updateSeatSelectionInfo();
    } else {
        currentSelectedPassengerIndex = null;
        updateSeatSelectionInfo();
    }
}

function selectPlaceForPassenger(placeNumber) {
    // Проверяем, выбран ли пассажир
    if (currentSelectedPassengerIndex === null) {
        alert('Сначала выберите пассажира');
        return;
    }

    // Проверяем, не занято ли место
    const seatWrapper = document.querySelector(`[data-place-number="${placeNumber}"]`);
    if (seatWrapper && seatWrapper.dataset.isOccupied === '1') {
        alert('Это место уже занято');
        return;
    }

    // Получаем текущее место для этого пассажира
    const placeInput = document.querySelector(`[data-passenger-index="${currentSelectedPassengerIndex}"] .place-input`);
    const currentPlace = placeInput ? parseInt(placeInput.value) : null;
    
    // Если это место уже выбрано для этого пассажира, ничего не делаем
    if (currentPlace === placeNumber) {
        return;
    }

    // Проверяем, не выбрано ли уже это место другим пассажиром
    if (selectedPlaces.has(placeNumber) && currentPlace !== placeNumber) {
        alert('Это место уже выбрано для другого пассажира');
        return;
    }

    // Проверяем количество пассажиров
    const passengerRows = document.querySelectorAll('.passenger-row');
    const passengerCount = passengerRows.length;
    if (selectedPlaces.size >= passengerCount && !currentPlace) {
        alert('Нельзя выбрать больше мест, чем пассажиров в заказе');
        return;
    }

    // Убираем предыдущее место для этого пассажира, если было
    if (currentPlace) {
        selectedPlaces.delete(currentPlace);
        updateSeatVisualState(currentPlace, false);
    }

    // Устанавливаем новое место
    placeInput.value = placeNumber;
    selectedPlaces.add(placeNumber);
    
    // Обновляем отображение
    const placeDisplay = document.getElementById(`selected-place-${currentSelectedPassengerIndex}`);
    placeDisplay.textContent = `Место №${placeNumber}`;
    placeDisplay.classList.add('has-place');
    
    // Показываем кнопку очистки
    const clearBtn = document.getElementById(`clear-place-${currentSelectedPassengerIndex}`);
    if (clearBtn) {
        clearBtn.classList.remove('hidden');
    }
    
    // Обновляем визуальное состояние места
    updateSeatVisualState(placeNumber, true);
    
    // Переходим к следующему пассажиру
    moveToNextPassenger();
    
    // Пересчитываем цену
    calculateTotal();
}

function updateSeatVisualState(placeNumber, isSelected) {
    const seatButton = document.querySelector(`[data-place-number="${placeNumber}"] .seat-button`);
    if (seatButton) {
        if (isSelected) {
            seatButton.classList.add('selected');
        } else {
            seatButton.classList.remove('selected');
        }
    }
}

function clearPlace(passengerIndex) {
    const placeInput = document.querySelector(`[data-passenger-index="${passengerIndex}"] .place-input`);
    if (!placeInput || !placeInput.value) {
        return;
    }
    
    const placeNumber = parseInt(placeInput.value);
    selectedPlaces.delete(placeNumber);
    placeInput.value = '';
    
    // Обновляем отображение
    const placeDisplay = document.getElementById(`selected-place-${passengerIndex}`);
    if (placeDisplay) {
        placeDisplay.textContent = 'Не выбрано';
        placeDisplay.classList.remove('has-place');
    }
    
    // Скрываем кнопку очистки
    const clearBtn = document.getElementById(`clear-place-${passengerIndex}`);
    if (clearBtn) {
        clearBtn.classList.add('hidden');
    }
    
    // Обновляем визуальное состояние места
    updateSeatVisualState(placeNumber, false);
    
    // Пересчитываем цену
    calculateTotal();
    updateSeatSelectionInfo();
}

function moveToNextPassenger() {
    const passengerRows = document.querySelectorAll('.passenger-row');
    let nextIndex = null;
    
    for (let i = 0; i < passengerRows.length; i++) {
        const placeInput = passengerRows[i].querySelector('.place-input');
        const passengerSelect = passengerRows[i].querySelector('.passenger-select');
        
        if (!placeInput.value && passengerSelect.value) {
            nextIndex = i;
            break;
        }
    }
    
    if (nextIndex !== null) {
        currentSelectedPassengerIndex = nextIndex;
        passengerRows[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        currentSelectedPassengerIndex = null;
    }
    
    updateSeatSelectionInfo();
}

function updateSeatSelectionInfo() {
    const info = document.getElementById('seat-selection-info');
    const passengerRows = document.querySelectorAll('.passenger-row');
    const selectedCount = selectedPlaces.size;
    const totalCount = passengerRows.length;
    
    if (currentSelectedPassengerIndex !== null) {
        const passengerSelect = document.querySelector(`[data-passenger-index="${currentSelectedPassengerIndex}"] .passenger-select`);
        const passengerName = passengerSelect.options[passengerSelect.selectedIndex].text;
        if (selectedCount >= totalCount) {
            info.innerHTML = `<p class="text-green-600 font-semibold">✓ Все места выбраны для: ${passengerName}</p><p class="text-gray-500">Выбрано мест: ${selectedCount} из ${totalCount}</p>`;
        } else {
            info.innerHTML = `<p class="text-indigo-600 font-semibold">Выберите место для: ${passengerName}</p><p class="text-gray-500">Выбрано мест: ${selectedCount} из ${totalCount}</p>`;
        }
    } else {
        if (selectedCount >= totalCount) {
            info.innerHTML = `<p class="text-green-600 font-semibold">✓ Все места выбраны</p><p class="text-gray-500">Выбрано мест: ${selectedCount} из ${totalCount}</p>`;
        } else {
            info.innerHTML = `<p>Выберите пассажира, затем кликните на место в автобусе</p><p class="text-gray-500">Выбрано мест: ${selectedCount} из ${totalCount}</p>`;
        }
    }
}

function addPassenger() {
    const container = document.getElementById('passenger-container');
    const newRow = document.querySelector('.passenger-row').cloneNode(true);
    newRow.setAttribute('data-passenger-index', passengerIndex);
    
    newRow.querySelectorAll('select, input, label').forEach(element => {
        if (element.name) {
            element.name = element.name.replace('[0]', `[${passengerIndex}]`);
        }
        if (element.id) {
            element.id = element.id.replace('_0_', `_${passengerIndex}_`);
        }
        if (element.htmlFor) {
            element.htmlFor = element.htmlFor.replace('_0_', `_${passengerIndex}_`);
        }
        if (element.classList.contains('passenger-select')) {
            element.onchange = () => onPassengerChange(passengerIndex);
            element.value = '';
        }
        if (element.classList.contains('place-input')) {
            element.value = '';
        }
        if (element.classList.contains('pet-checkbox')) {
            element.onchange = calculateTotal;
            element.checked = false;
        }
    });
    
    // Обновляем отображение места
    const placeDisplay = newRow.querySelector('.selected-place-display');
    placeDisplay.id = `selected-place-${passengerIndex}`;
    placeDisplay.textContent = 'Не выбрано';
    placeDisplay.classList.remove('has-place');
    
    // Обновляем кнопку очистки
    const clearBtn = newRow.querySelector('.clear-place-btn');
    if (clearBtn) {
        clearBtn.id = `clear-place-${passengerIndex}`;
        clearBtn.onclick = () => clearPlace(passengerIndex);
        clearBtn.classList.add('hidden');
    }
    
    // Обновляем обработчик выбора пассажира
    const passengerSelect = newRow.querySelector('.passenger-select');
    if (passengerSelect) {
        passengerSelect.onchange = () => onPassengerChange(passengerIndex);
    }
    
    // Update price display
    const priceSpan = newRow.querySelector('.passenger-price span');
    if (priceSpan) {
        priceSpan.textContent = basePrice.toFixed(2);
    }
    
    container.appendChild(newRow);
    passengerIndex++;
    calculateTotal();
    updateSeatSelectionInfo();
}

function calculateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.passenger-row');
    
    rows.forEach((row, index) => {
        const placeInput = row.querySelector('.place-input');
        const petCheckbox = row.querySelector('.pet-checkbox');
        const priceDisplay = row.querySelector('.passenger-price span');
        
        if (!placeInput || !placeInput.value) {
            if (priceDisplay) {
                priceDisplay.textContent = basePrice.toFixed(2);
            }
            return;
        }
        
        const placeNumber = parseInt(placeInput.value);
        let price = basePrice;
        
        // Window seat (positions 1 and 4 in each row - edges)
        const seatWrapper = document.querySelector(`[data-place-number="${placeNumber}"]`);
        if (seatWrapper && seatWrapper.dataset.isWindow === '1') {
            price += windowSeatPrice;
        }
        
        // Pet option
        if (petCheckbox && petCheckbox.checked) {
            price += petPrice;
        }
        
        // Weekend multiplier
        if (isWeekend) {
            price *= weekendMultiplier;
        }
        
        price = Math.round(price * 100) / 100;
        
        if (priceDisplay) {
            priceDisplay.textContent = price.toFixed(2);
        }
        
        total += price;
    });
    
    document.getElementById('total-price').textContent = total.toFixed(2) + ' ₽';
}

// Валидация формы перед отправкой
document.querySelector('form').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('.passenger-row');
    let hasErrors = false;
    const errors = [];
    
    rows.forEach((row, index) => {
        const passengerSelect = row.querySelector('.passenger-select');
        const placeInput = row.querySelector('.place-input');
        
        if (!passengerSelect.value) {
            hasErrors = true;
            errors.push(`Пассажир #${index + 1} не выбран`);
        }
        
        if (!placeInput.value) {
            hasErrors = true;
            errors.push(`Место для пассажира #${index + 1} не выбрано`);
        }
    });
    
    if (hasErrors) {
        e.preventDefault();
        alert('Ошибки:\n' + errors.join('\n'));
        return false;
    }
    
    // Проверка на дубликаты мест
    const selectedPlacesArray = Array.from(selectedPlaces);
    if (selectedPlacesArray.length !== new Set(selectedPlacesArray).size) {
        e.preventDefault();
        alert('Ошибка: одно и то же место выбрано для нескольких пассажиров');
        return false;
    }
    
    return true;
});
</script>
@endsection

