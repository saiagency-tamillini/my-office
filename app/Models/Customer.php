<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Customer extends Model
{
    protected $fillable = ['name', 'beat_id'];

    public function beat()
    {
        return $this->belongsTo(Beat::class);
    }

    public function sales()
    {
        return $this->hasMany(PartySale::class);
    }
}

