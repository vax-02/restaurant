<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Product::getCategories();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'category' => ['required', Rule::in([Product::CATEGORY_PLATE, Product::CATEGORY_LIQUID])],
            'available' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $validated;
        $data['available'] = $request->has('available');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        $product = Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', "Producto '{$product->name}' creado correctamente");
    }

    public function edit(Product $product)
    {
        $categories = Product::getCategories();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'category' => ['required', Rule::in([Product::CATEGORY_PLATE, Product::CATEGORY_LIQUID])],
            'available' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $validated;
        $data['available'] = $request->has('available');

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        $product->update($data);

        return redirect()->route('admin.products.index')
            ->with('success', "Producto '{$product->name}' actualizado correctamente");
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $nombre = $product->name;
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Producto '{$nombre}' eliminado correctamente");
    }

    public function toggleAvailable(Product $product)
    {
        $product->available = !$product->available;
        $product->save();

        $estado = $product->available ? 'habilitado' : 'deshabilitado';
        return redirect()->route('admin.products.index')
            ->with('success', "Producto '{$product->name}' {$estado} correctamente");
    }
}
