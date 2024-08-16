<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Booking;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('offer_id');
            $table->json('flight_details');//store the flight offer details
            $table->json('passenger_info');//JSON columns
            $table->json('luggage_info');
            $table->json('seats_info');
            $table->json('booking_info')->nullable(); //the order confirmation details
            $table->string('status')->default(Booking::STATUS_PENDING); //booking status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
