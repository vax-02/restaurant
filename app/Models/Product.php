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

    // Solo productos disponibles para hoy
    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
