@extends('layouts.main')

@section('title', 'Статистика - BusBooking')

@section('content')
<div class="space-y-6">
    <h2 class="text-3xl font-bold text-gray-900">Статистика продаж по маршрутам</h2>

    <!-- Общая статистика -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Всего билетов</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $totalStats['total_tickets'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Оплачено</div>
            <div class="mt-2 text-3xl font-bold text-green-600">{{ $totalStats['paid_tickets'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">В ожидании</div>
            <div class="mt-2 text-3xl font-bold text-yellow-600">{{ $totalStats['pending_tickets'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Общая выручка</div>
            <div class="mt-2 text-3xl font-bold text-indigo-600">{{ number_format($totalStats['total_revenue'], 2) }} ₽</div>
        </div>
    </div>

    <!-- Дополнительная статистика -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Дополнительные услуги</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Места у окна продано:</span>
                    <span class="font-bold text-blue-600">{{ $totalStats['window_seats_sold'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Билетов с животными:</span>
                    <span class="font-bold text-purple-600">{{ $totalStats['pet_tickets'] }}</span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Статистика по дням недели</h3>
            <div class="space-y-2">
                @php
                    $days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
                @endphp
                @foreach($weekdayStats as $stat)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">{{ $days[$stat->day_of_week - 1] ?? 'Неизвестно' }}:</span>
                        <span class="font-medium">{{ $stat->tickets_count }} билетов ({{ number_format($stat->revenue, 2) }} ₽)</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Популярность маршрутов -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Популярность маршрутов</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Маршрут</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Автобус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Всего билетов</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Оплачено</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">В ожидании</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Выручка</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Популярность</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($routeStats as $stat)
                        @php
                            $route = $routes[$stat->route_id] ?? null;
                            $maxTickets = $routeStats->max('paid_tickets');
                            $popularityPercent = $maxTickets > 0 ? ($stat->paid_tickets / $maxTickets) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $route ? ($route->from_station . ' → ' . $route->to_station) : 'Маршрут #' . $stat->route_id }}
                                </div>
                                @if($route)
                                    <div class="text-sm text-gray-500">
                                        {{ $route->from_station }} → {{ $route->to_station }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $route && $route->bus ? $route->bus->name : 'Не указан' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $stat->total_tickets }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-green-600">{{ $stat->paid_tickets }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-yellow-600">{{ $stat->pending_tickets }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                {{ number_format($stat->total_revenue, 2) }} ₽
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-4 mr-2" style="max-width: 200px;">
                                        <div class="bg-indigo-600 h-4 rounded-full" style="width: {{ $popularityPercent }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ number_format($popularityPercent, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                Нет данных о продажах
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- График продаж за последние 30 дней -->
    @if($recentStats->count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Продажи за последние 30 дней</h3>
            <div class="h-64 flex items-end justify-between gap-1">
                @php
                    $maxRevenue = $recentStats->max('revenue') ?: 1;
                @endphp
                @foreach($recentStats as $stat)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-indigo-200 rounded-t hover:bg-indigo-400 transition-colors cursor-pointer group relative" 
                             style="height: {{ ($stat->revenue / $maxRevenue) * 100 }}%"
                             title="{{ \Carbon\Carbon::parse($stat->date)->format('d.m.Y') }}: {{ number_format($stat->revenue, 0) }} ₽">
                            <div class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($stat->date)->format('d.m.Y') }}<br>
                                {{ number_format($stat->revenue, 0) }} ₽<br>
                                {{ $stat->tickets_count }} билетов
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left" style="writing-mode: vertical-rl;">
                            {{ \Carbon\Carbon::parse($stat->date)->format('d.m') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
