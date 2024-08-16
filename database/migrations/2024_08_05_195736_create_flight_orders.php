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
        Schema::create('flight_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id'); // Foreign key to bookings table
            $table->string('type');
            $table->string('order_id');
            $table->string('queuing_office_id')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('creation_date')->nullable();
            $table->string('origin_system_code')->nullable();
            $table->string('flight_offer_id')->nullable();
            $table->string('source')->nullable();
            $table->boolean('non_homogeneous')->default(false);
            $table->date('last_ticketing_date')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('total', 8, 2)->nullable();
            $table->decimal('base', 8, 2)->nullable();
            $table->decimal('grand_total', 8, 2)->nullable();
            $table->string('billing_currency')->nullable();
            $table->string('fare_type')->nullable();
            $table->boolean('included_checked_bags_only')->default(false);
            $table->json('itineraries')->nullable(); // JSON to store itineraries
            $table->json('traveler_pricings')->nullable(); // JSON to store traveler pricing
            $table->json('travelers')->nullable(); // JSON to store travelers details
            $table->json('remarks')->nullable(); // JSON to store remarks
            $table->json('ticketing_agreement')->nullable(); // JSON to store ticketing agreement
            $table->json('automated_process')->nullable(); // JSON to store automated process
            $table->json('contacts')->nullable(); // JSON to store contacts
            $table->json('dictionaries')->nullable(); // JSON to store dictionaries
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_orders');
    }
};
