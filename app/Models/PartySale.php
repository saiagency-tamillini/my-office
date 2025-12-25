<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'beat_id',
        's_no',
        'customer_id',
        'bill_no',
        'bill_date',
        'aging',
        'amount',
        'cd',
        'product_return',
        'online_payment',
        'amount_received',
        'balance',
        'remarks',
        'modified'
    ];
    protected $casts = [
        'bill_date' => 'date',
    ];

    protected $dates = ['bill_date'];

    // Relation with Beat
    public function beat()
    {
        return $this->belongsTo(Beat::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
