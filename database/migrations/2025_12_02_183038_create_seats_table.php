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
        if (!Schema::hasTable('seats')) {
            Schema::create('seats', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('bus_id');
                $table->integer('number');
                $table->boolean('is_window')->default(false);
                $table->boolean('allows_pet')->default(false);
                $table->timestamps();
                
                $table->foreign('bus_id')->references('id')->on('buses')->onDelete('cascade');
                $table->unique(['bus_id', 'number']);
            });
        } else {
            // Если таблица уже существует, добавляем недостающие колонки
            Schema::table('seats', function (Blueprint $table) {
                if (!Schema::hasColumn('seats', 'allows_pet')) {
                    $table->boolean('allows_pet')->default(false)->after('is_window');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
