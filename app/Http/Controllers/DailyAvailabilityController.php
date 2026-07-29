<?php

namespace App\Http\Controllers;

use App\Models\DailyAvailability;
use App\Models\Product;
use Illuminate\Http\Request;

class DailyAvailabilityController extends Controller
{
     public function index()
    {
        $products = Product::with('todayAvailability')->get();
        return view('admin.availability.index', compact('products'));
    }

    public function update(Request $request)
    {
        foreach ($request->stock as $productId => $stock) {
            $stockValue = ($stock === '' || $stock === null) ? 0 : (int)$stock;
            $availability = DailyAvailability::getTodayStock($productId);
            $availability->update(['stock' => $stockValue]);
        }

        return redirect()->route('admin.availability.index')
            ->with('success', '✅ Disponibilidad actualizada correctamente');
    }

    // Actualizar stock de un solo producto (AJAX)
    public function updateStock(Request $request, Product $product)
    {
        $request->validate([
            'stock' => 'required|integer|min:0'
        ]);

        $availability = DailyAvailability::getTodayStock($product->id);
        $availability->update(['stock' => $request->stock]);

        return response()->json([
            'success' => true,
            'message' => "Stock de '{$product->name}' actualizado a {$request->stock}"
        ]);
    }

    // Resetear disponibilidad para todos los productos (para nuevo día)
    public function resetToday()
    {
        // Eliminar disponibilidad de días anteriores (opcional)
        DailyAvailability::where('date', '<', today())->delete();

        // Crear disponibilidad para hoy con stock 0 para todos los productos
        $products = Product::where('available', true)->get();
        foreach ($products as $product) {
            DailyAvailability::getTodayStock($product->id);
        }

        return redirect()->route('admin.availability.index')
            ->with('success', '🔄 Disponibilidad reiniciada para hoy');
    }
}
