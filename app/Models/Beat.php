<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beat extends Model
{
    protected $fillable = ['name', 'salesman'];

    public function bills()
    {
        // return $this->hasMany(CustomerBill::class);
    }
}
