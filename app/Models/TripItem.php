<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripItem extends Model
{
    protected $fillable = [
        'trip_id',
        'party_sale_id',
        'payment_entry_id',
    ];
}
