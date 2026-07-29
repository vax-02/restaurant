<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyDetail extends Model
{
    protected $fillable = [
        'buy_id',
        'product_id',
        'price',
        'quantity'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function buy()
    {
        return $this->belongsTo(Buy::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
}