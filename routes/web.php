<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Manager\BusController;
use App\Http\Controllers\Manager\RouteController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Client\TripController;
use App\Http\Controllers\Client\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\StatsController;

// выбор места
Route::get('/route/{route}/bus', [BookingController::class, 'showBus'])->name('route.bus');
Route::post('/seat/{seat}/reserve', [BookingController::class, 'reserve'])->name('seat.reserve');

// оплата
Route::get('/payment/{order}', [PaymentController::class, 'page'])->name('payment.page');
Route::post('/payment/{order}', [PaymentController::class, 'process'])->name('payment.process');
Route::get('/payment-success', [PaymentController::class, 'success'])->name('payment.success');

// Главная страница
Route::get('/', [HomeController::class, 'index'])->name('home');

// Аутентификация
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Маршруты для администратора
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');

    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
});

// Маршруты для менеджера
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('dashboard');

    Route::resource('buses', BusController::class);

    Route::resource('routes', RouteController::class);
    Route::post('routes/{route}/approve', [RouteController::class, 'approve'])->name('routes.approve');
    Route::post('routes/{route}/schedule', [RouteController::class, 'storeSchedule'])->name('routes.schedule.store');
});

// Маршруты для клиентов
Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('dashboard');

    // Поиск рейсов
    Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');

    // Управление заказами
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create/{trip}', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/payment', [OrderController::class, 'payment'])->name('orders.payment');
    Route::post('/orders/{order}/payment', [OrderController::class, 'processPayment'])->name('orders.payment.process');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // Управление пассажирами
    Route::get('/passengers', [ClientController::class, 'passengers'])->name('passengers.index');
    Route::post('/passengers', [ClientController::class, 'storePassenger'])->name('passengers.store');
    Route::delete('/passengers/{passenger}', [ClientController::class, 'deletePassenger'])->name('passengers.destroy');
});
