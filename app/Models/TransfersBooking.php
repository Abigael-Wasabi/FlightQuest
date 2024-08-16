<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransfersBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'order_id',
        'agency',
        'transfers',
        'passengers',
        'passenger_characteristics',
        'extra_services',
        'quotation',
        'converted',
        'cancellation_rules',
        'distance',
    ];

    protected $casts = [
        'agency' => 'array',
        'transfers' => 'array',
        'passengers' => 'array',
        'passenger_characteristics' => 'array',
        'extra_services' => 'array',
        'quotation' => 'array',
        'converted' => 'array',
        'cancellation_rules' => 'array',
        'distance' => 'array',
    ];
}
