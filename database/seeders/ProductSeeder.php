<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $products = [
            // Platos
            [
                'name' => 'Lomo Saltado',
                'description' => 'Tradicional plato peruano con lomo de res, cebolla, tomate y papas fritas',
                'price' => 25.00,
                'category' => Product::CATEGORY_PLATE,
                'available' => true,
            ],
            [
                'name' => 'Ceviche Clásico',
                'description' => 'Pescado fresco marinado en jugo de limón con cebolla roja, ají y cilantro',
                'price' => 28.00,
                'category' => Product::CATEGORY_PLATE,
                'available' => true,
            ],
            [
                'name' => 'Pollo a la Brasa',
                'description' => 'Pollo asado al estilo peruano con papas fritas y ensalada',
                'price' => 22.00,
                'category' => Product::CATEGORY_PLATE,
                'available' => true,
            ],
            [
                'name' => 'Tallarines Verdes',
                'description' => 'Pasta con salsa de albahaca, espinaca y queso parmesano',
                'price' => 18.00,
                'category' => Product::CATEGORY_PLATE,
                'available' => false, // No disponible hoy
            ],
            [
                'name' => 'Arroz con Pollo',
                'description' => 'Arroz cocido con pollo deshilachado, verduras y culantro',
                'price' => 20.00,
                'category' => Product::CATEGORY_PLATE,
                'available' => true,
            ],

            // Líquidos
            [
                'name' => 'Chicha Morada',
                'description' => 'Bebida tradicional peruana hecha de maíz morado con frutas',
                'price' => 8.00,
                'category' => Product::CATEGORY_LIQUID,
                'available' => true,
            ],
            [
                'name' => 'Limonada Frozen',
                'description' => 'Refrescante limonada con hielo y hierbabuena',
                'price' => 10.00,
                'category' => Product::CATEGORY_LIQUID,
                'available' => true,
            ],
            [
                'name' => 'Inca Kola',
                'description' => 'Gaseosa peruana de sabor único',
                'price' => 6.00,
                'category' => Product::CATEGORY_LIQUID,
                'available' => true,
            ],
            [
                'name' => 'Jugo de Naranja',
                'description' => 'Jugo natural de naranja recién exprimido',
                'price' => 9.00,
                'category' => Product::CATEGORY_LIQUID,
                'available' => false, // No disponible hoy
            ],
        ];
        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
