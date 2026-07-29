<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
 
  protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'image',
        'available'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available' => 'boolean',
    ];

    const CATEGORY_PLATE = 'plato';
    const CATEGORY_LIQUID = 'bebida';

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_PLATE => 'Plato',
            self::CATEGORY_LIQUID => 'Bebida',
        ];
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('images/default-product.png');
    }

    
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

      public function dailyAvailabilities()
    {
        return $this->hasMany(DailyAvailability::class);
    }
     public function todayAvailability()
    {
        return $this->hasOne(DailyAvailability::class)
            ->where('date', today());
    }

    public function getTodayStockAttribute(): int
    {
        $availability = $this->todayAvailability()->first();
        return $availability ? $availability->stock : 0;
    }

    // Verificar si tiene stock hoy
    public function hasStockToday($quantity = 1): bool
    {
        return $this->today_stock >= $quantity;
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', true)
            ->whereHas('todayAvailability', function ($q) {
                $q->where('stock', '>', 0);
            });
    }
    // Descontar stock
    public function decrementStock($quantity = 1): bool
    {
        $availability = $this->todayAvailability()->first();
        if ($availability && $availability->stock >= $quantity) {
            $availability->decrement('stock', $quantity);
            return true;
        }
        return false;
    }

}
