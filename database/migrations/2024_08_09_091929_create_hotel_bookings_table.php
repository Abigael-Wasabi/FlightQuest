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
        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_offer_id');
            $table->string('booking_id')->unique();
            $table->string('confirmation_number')->nullable();
            $table->string('hotel_id');
            $table->string('hotel_name');
            $table->string('hotel_chain_code')->nullable();
            $table->string('room_type');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('room_quantity');
            $table->text('room_description');
            $table->decimal('base_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('currency');
            $table->json('guests');
            $table->json('room_associations');
            $table->json('payment');
            $table->string('travel_agent_id')->nullable();
            $table->json('associated_records')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
    }
};
