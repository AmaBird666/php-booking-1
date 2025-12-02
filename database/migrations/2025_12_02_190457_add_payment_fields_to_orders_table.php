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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_price', 10, 2)->default(0)->after('client_id');
            $table->boolean('with_pet')->default(false)->after('total_price');
            $table->string('status')->default('pending')->after('with_pet'); // pending, paid, expired
            $table->dateTime('reserved_until')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total_price', 'with_pet', 'status', 'reserved_until']);
        });
    }
};
