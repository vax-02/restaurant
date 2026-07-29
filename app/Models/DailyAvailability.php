<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAvailability extends Model
{
     protected $table = 'daily_availability';

    protected $fillable = [
        'product_id',
        'date',
        'stock'
    ];

    protected $casts = [
        'date' => 'date',
        'stock' => 'integer',
    ];

    // Relación con Producto
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scope para la fecha actual
    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    // Scope para productos con stock
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    // Obtener o crear disponibilidad para hoy
    public static function getTodayStock($productId)
    {
        $availability = self::where('product_id', $productId)
            ->where('date', today())
            ->first();

        if (!$availability) {
            $availability = self::create([
                'product_id' => $productId,
                'date' => today(),
                'stock' => 0,
            ]);
        }

        return $availability;
    }

    // Actualizar stock (descontar)
    public function decrementStock($quantity = 1)
    {
        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
            return true;
        }
        return false;
    }
}
