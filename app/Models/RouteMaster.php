<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteMaster extends Model
{
    protected $table = 'routes';

    protected $fillable = ['name'];
}
