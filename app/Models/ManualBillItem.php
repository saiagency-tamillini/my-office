<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ManualBillItem extends Model
{
        use HasFactory;

    protected $fillable = [
        'party_sale_id',
        'product_id',
        'box',
        'pcs',
    ];

    public function partySale()
    {
        return $this->belongsTo(PartySale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
