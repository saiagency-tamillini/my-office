<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beat extends Model
{
    protected $fillable = ['name', 'salesman'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'beat_id', 'id');
    }
}
