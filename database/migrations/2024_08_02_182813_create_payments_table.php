<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('MerchantRequestID');
            $table->string('CheckoutRequestID');
            $table->string('ResponseCode');
            $table->string('ResponseDescription');
            $table->string('CustomerMessage');
            $table->string('ResultCode')->nullable();
            $table->string('ResultDesc')->nullable();
            $table->decimal('Amount', 10, 2)->nullable();
            $table->string('MpesaReceiptNumber')->nullable();
            $table->string('Balance', 10, 2)->nullable();
            $table->string('TransactionDate')->nullable();
            $table->string('PhoneNumber')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
