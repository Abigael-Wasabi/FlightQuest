<?php

namespace App\Models;

use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELED = 'canceled';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'offer_id',
        'flight_details',
        'passenger_info',
        'luggage_info',
        'seats_info',
        'booking_info',
        'status',
        'pnr',
    ];

    public function passengers()
    {
        return $this->hasMany(Passengers::class);
    }

    public function luggage()
    {
        return $this->hasMany(Luggage::class);
    }

    public function seats()
    {
        return $this->hasMany(Seats::class);
    }

    public function payments()
    {
        return $this->hasMany(Payments::class);
    }
}
