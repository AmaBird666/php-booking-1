<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
                $table->foreignId('seat_id')->constrained('seats')->onDelete('cascade');
                $table->decimal('price', 10, 2);
                $table->boolean('with_pet')->default(false);
                $table->enum('status', ['pending', 'paid', 'expired', 'cancelled'])->default('pending');
                $table->timestamp('reserved_until')->nullable();
                $table->date('travel_date')->nullable();
                $table->timestamps();
            });
        } else {
            // Если таблица уже существует, добавляем недостающие колонки
            Schema::table('tickets', function (Blueprint $table) {
                if (!Schema::hasColumn('tickets', 'reserved_until')) {
                    $table->timestamp('reserved_until')->nullable()->after('status');
                }
                if (!Schema::hasColumn('tickets', 'travel_date')) {
                    $table->date('travel_date')->nullable()->after('reserved_until');
                }
                if (!Schema::hasColumn('tickets', 'with_pet')) {
                    $table->boolean('with_pet')->default(false)->after('price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
