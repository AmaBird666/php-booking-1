<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function index()
    {
        // Статистика по популярности маршрутов
        $routeStats = DB::table('tickets')
            ->select('route_id', 
                DB::raw('count(*) as total_tickets'),
                DB::raw('count(CASE WHEN status = "paid" THEN 1 END) as paid_tickets'),
                DB::raw('count(CASE WHEN status = "pending" THEN 1 END) as pending_tickets'),
                DB::raw('sum(CASE WHEN status = "paid" THEN price ELSE 0 END) as total_revenue')
            )
            ->groupBy('route_id')
            ->orderByDesc('paid_tickets')
            ->get();

        $routes = Route::with('bus')->get()->keyBy('id');

        // Общая статистика
        $totalStats = [
            'total_tickets' => Ticket::count(),
            'paid_tickets' => Ticket::where('status', 'paid')->count(),
            'pending_tickets' => Ticket::where('status', 'pending')->count(),
            'total_revenue' => Ticket::where('status', 'paid')->sum('price'),
            'window_seats_sold' => Ticket::where('status', 'paid')
                ->whereHas('seat', function($q) {
                    $q->where('is_window', true);
                })->count(),
            'pet_tickets' => Ticket::where('status', 'paid')->where('with_pet', true)->count(),
        ];

        // Статистика по дням недели
        $weekdayStats = DB::table('tickets')
            ->select(DB::raw('DAYOFWEEK(travel_date) as day_of_week'),
                DB::raw('count(*) as tickets_count'),
                DB::raw('sum(CASE WHEN status = "paid" THEN price ELSE 0 END) as revenue')
            )
            ->whereNotNull('travel_date')
            ->where('status', 'paid')
            ->groupBy('day_of_week')
            ->get();

        // Статистика за последние 30 дней
        $recentStats = DB::table('tickets')
            ->select(DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as tickets_count'),
                DB::raw('sum(CASE WHEN status = "paid" THEN price ELSE 0 END) as revenue')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.stats', compact('routeStats', 'routes', 'totalStats', 'weekdayStats', 'recentStats'));
    }
}
