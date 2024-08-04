<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    use HasFactory;

    protected $table = 'airports';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'airport',
        'city',
        'country',
        'code',
        'late',
        'long',
        'region',
        'type',
        'status',
    ];
    /**
     * The attributes that shud be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'status' => 'boolean',
    ];
}
