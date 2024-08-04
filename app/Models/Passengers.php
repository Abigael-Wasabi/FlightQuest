<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passengers extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'travelerType',
        'travelerId',
        'firstName',
        'lastName',
        'dateOfBirth',
        'email',
        'phone_number',
        'passport_number',
        'birth_certificate_number',
        'nationality'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
