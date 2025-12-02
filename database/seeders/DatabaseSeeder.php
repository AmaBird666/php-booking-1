<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Bus;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Models\Trip;
use App\Models\Passenger;
use App\Models\Seat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        User::truncate();
        Admin::truncate();
        Client::truncate();
        Seat::truncate();
        Bus::truncate();
        Route::truncate();
        Trip::truncate();
        RouteSchedule::truncate();
        Passenger::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $admin = User::create([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'patronymic' => 'Иванович',
            'login' => 'admin',
            'password' => Hash::make('password'),
            'user_type' => 'admin',
            'disabled' => false,
        ]);

        Admin::create([
            'id' => $admin->id,
            'position' => 'Главный администратор',
        ]);

        $manager = User::create([
            'first_name' => 'Петр',
            'last_name' => 'Петров',
            'patronymic' => 'Петрович',
            'login' => 'manager',
            'password' => Hash::make('password'),
            'user_type' => 'manager',
            'disabled' => false,
        ]);

        $buses = [
            ['id' => 100, 'name' => 'Mercedes Sprinter', 'places' => 20],
            ['id' => 101, 'name' => 'Volvo 9700', 'places' => 45],
            ['id' => 102, 'name' => 'Scania Touring', 'places' => 50],
            ['id' => 103, 'name' => 'MAN Lion\'s Coach', 'places' => 55],
        ];

        foreach ($buses as $busData) {
            $bus = Bus::create($busData);
            
            // Создаем места для автобуса
            // Распределение: места у окна (1, 2, 5, 6, 9, 10...), места с животными (последние 2-4 места)
            $places = $bus->places;
            $seatsPerRow = 4; // 2+2 конфигурация
            $petSeatsCount = min(4, max(2, floor($places * 0.1))); // 10% мест для животных, минимум 2, максимум 4
            
            for ($i = 1; $i <= $places; $i++) {
                // Определяем, является ли место у окна
                // Каждое четное место (2, 4, 6, 8...) - место у окна
                $isWindow = ($i % 2 == 0);
                
                // Последние места для животных
                $allowsPet = $i > ($places - $petSeatsCount);
                
                Seat::create([
                    'bus_id' => $bus->id,
                    'number' => $i,
                    'is_window' => $isWindow,
                    'allows_pet' => $allowsPet,
                ]);
            }
        }

        $routes = [
            [
                'bus_id' => 100,
                'from_station' => 'Москва',
                'to_station' => 'Санкт-Петербург',
                'start' => '08:00:00',
                'duration' => 480,
                'price' => 1500.00,
                'approved' => true,
            ],
            [
                'bus_id' => 101,
                'from_station' => 'Москва',
                'to_station' => 'Казань',
                'start' => '10:00:00',
                'duration' => 720,
                'price' => 1200.00,
                'approved' => true,
            ],
            [
                'bus_id' => 102,
                'from_station' => 'Санкт-Петербург',
                'to_station' => 'Москва',
                'start' => '09:00:00',
                'duration' => 480,
                'price' => 1500.00,
                'approved' => true,
            ],
            [
                'bus_id' => 103,
                'from_station' => 'Москва',
                'to_station' => 'Новгород',
                'start' => '12:00:00',
                'duration' => 360,
                'price' => 800.00,
                'approved' => false,
            ],
        ];

        foreach ($routes as $routeData) {
            $route = Route::create($routeData);

            if ($route->approved) {
                $fromDate = Carbon::today();
                $toDate = Carbon::today()->addDays(30);

                RouteSchedule::create([
                    'route_id' => $route->id,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'period' => 'daily',
                ]);

                for ($i = 0; $i < 30; $i++) {
                    Trip::create([
                        'route_id' => $route->id,
                        'date' => Carbon::today()->addDays($i),
                        'free_places' => Bus::find($route->bus_id)->places,
                    ]);
                }
            }
        }

        $this->command->info('База данных успешно заполнена тестовыми данными!');
        $this->command->info('');
        $this->command->info('Учетные данные для входа:');
        $this->command->info('Администратор (Иванов Иван Иванович): login=admin, password=password');
        $this->command->info('Менеджер (Петров Петр Петрович): login=manager, password=password');
    }
}
