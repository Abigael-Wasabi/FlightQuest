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
        Schema::create('transfers_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->string('order_id')->unique();
            $table->json('agency')->nullable();
            $table->json('transfers')->nullable();
            $table->json('passengers')->nullable();
            $table->json('passenger_characteristics')->nullable();
            $table->json('extra_services')->nullable();
            $table->json('quotation')->nullable();
            $table->json('converted')->nullable();
            $table->json('cancellation_rules')->nullable();
            $table->json('distance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers_bookings');
    }
};
