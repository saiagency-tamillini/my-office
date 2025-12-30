<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PaymentEntry extends Model
{
     use HasFactory;

    protected $table = 'payment_entries';

    protected $fillable = [
        'part_sale_id',
        'customer_id',
        'bill_no',
        'payment_date',
        'amount',
        'cd',
        'product_return',
        'online_payment',
        'amount_received',
        'balance',
        'remarks',
        'status',
    ];

    protected $casts = [
        'payment_date'   => 'date',
    ];

    /**
     * Automatically set payment_date to today if not provided
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->payment_date)) {
                $model->payment_date = Carbon::today();
            }
        });
    }



    public function partySale()
    {
        return $this->belongsTo(PartySale::class, 'part_sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
