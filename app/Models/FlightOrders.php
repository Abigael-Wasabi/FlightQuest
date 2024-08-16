<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightOrders extends Model
{
    use HasFactory;

    protected $table = 'flight_orders';

    protected $fillable = [
        'booking_id',
        'type',
        'order_id',
        'queuing_office_id',
        'reference',
        'creation_date',
        'origin_system_code',
        'flight_offer_id',
        'source',
        'non_homogeneous',
        'last_ticketing_date',
        'currency',
        'total',
        'base',
        'grand_total',
        'billing_currency',
        'fare_type',
        'included_checked_bags_only',
        'itineraries',
        'traveler_pricings',
        'travelers',
        'remarks',
        'ticketing_agreement',
        'automated_process',
        'contacts',
        'dictionaries',
    ];

    protected $casts = [
        'creation_date' => 'datetime',
        'last_ticketing_date' => 'date',
        'non_homogeneous' => 'boolean',
        'included_checked_bags_only' => 'boolean',
        'itineraries' => 'array',
        'traveler_pricings' => 'array',
        'travelers' => 'array',
        'remarks' => 'array',
        'ticketing_agreement' => 'array',
        'automated_process' => 'array',
        'contacts' => 'array',
        'dictionaries' => 'array',
    ];

    /**
     * Get the booking that owns the flight order.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
