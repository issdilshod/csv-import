<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $fillable = [
        'name',
        'description',
        'stock_level',
        'price',
        'discontinued_at'
    ];

    protected $attributes = [
        'stock_level' => 0,
        'price' => 0.00
    ];
}
