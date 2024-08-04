<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'MerchantRequestID',
        'CheckoutRequestID',
        'ResponseCode',
        'ResponseDescription',
        'CustomerMessage',
        'ResultCode',
        'ResultDesc',
        'Amount',
        'MpesaReceiptNumber',
        'Balance',
        'TransactionDate',
        'PhoneNumber'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
