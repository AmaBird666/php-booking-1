<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function page(Ticket $order)
    {
        // Проверяем, не истекла ли резервация
        if ($order->isExpired()) {
            $order->update(['status' => 'expired']);
            return redirect()->route('home')->with('error', 'Время резервирования истекло. Место освобождено.');
        }

        return view('payment.page', compact('order'));
    }

    public function process(Request $request, Ticket $order)
    {
        // Проверяем, не истекла ли резервация
        if ($order->isExpired()) {
            $order->update(['status' => 'expired']);
            return redirect()->route('home')->with('error', 'Время резервирования истекло. Место освобождено.');
        }

        // 25% шанс отказа (0.25 вероятность)
        if (rand(1, 4) === 1) {
            // Место остается забронированным, можно попробовать снова
            return back()->with('error', 'Оплата не прошла. Попробуйте снова. У вас есть время до ' . $order->reserved_until->format('H:i'));
        }

        $order->update(['status' => 'paid', 'reserved_until' => null]);

        return redirect()->route('payment.success')->with('success', 'Оплата прошла успешно!');
    }

    public function success()
    {
        return view('payment.success');
    }
}
