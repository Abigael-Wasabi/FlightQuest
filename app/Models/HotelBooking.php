<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_offer_id',
        'booking_id',
        'confirmation_number',
        'hotel_id',
        'hotel_name',
        'hotel_chain_code',
        'room_type',
        'check_in_date',
        'check_out_date',
        'room_quantity',
        'room_description',
        'base_price',
        'total_price',
        'currency',
        'guests',
        'room_associations',
        'payment',
        'travel_agent_id',
        'associated_records'
    ];

    protected $casts = [
        'guests' => 'array',
        'room_associations' => 'array',
        'payment' => 'array',
        'associated_records' => 'array',
    ];
}
