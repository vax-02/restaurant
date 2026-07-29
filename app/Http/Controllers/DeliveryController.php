<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deliveries = Delivery::all();
        return view('admin.deliveries.index', compact('deliveries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.deliveries.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'cellphone' => 'required|string|max:8',
            'user_telegram' => 'required|string|max:50',
            'status' => 'nullable|boolean',
        ]);

        // Generate unique code
        $code = 'DLV-' . strtoupper(Str::random(8));
        while (Delivery::where('code', $code)->exists()) {
            $code = 'DLV-' . strtoupper(Str::random(8));
        }

        $validated['code'] = $code;
        $validated['status'] = $request->has('status') ? 1 : 0;

        Delivery::create($validated);

        return redirect()->route('admin.deliveries.index')
            ->with('success', 'Delivery creado correctamente');
    }

    /**
     * Display the specified resource with details, code and buys.
     */
    public function show(Delivery $delivery)
    {
        $buys = $delivery->buys()->with('details.product')->orderBy('created_at', 'desc')->get();
        return view('admin.deliveries.show', compact('delivery', 'buys'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Delivery $delivery)
    {
        return view('admin.deliveries.edit', compact('delivery'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'cellphone' => 'required|string|max:8',
            'user_telegram' => 'required|string|max:50',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status') ? 1 : 0;

        $delivery->update($validated);

        return redirect()->route('admin.deliveries.index')
            ->with('success', 'Delivery actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Delivery $delivery)
    {
        $delivery->delete();
        return redirect()->route('admin.deliveries.index')
            ->with('success', 'Delivery eliminado correctamente');
    }

    /**
     * Regenerate the delivery code.
     */
    public function regenerateCode(Delivery $delivery)
    {
        $code = 'DLV-' . strtoupper(Str::random(8));
        while (Delivery::where('code', $code)->where('id', '!=', $delivery->id)->exists()) {
            $code = 'DLV-' . strtoupper(Str::random(8));
        }

        $delivery->update(['code' => $code]);

        return redirect()->route('admin.deliveries.show', $delivery)
            ->with('success', 'Código regenerado correctamente');
    }
}