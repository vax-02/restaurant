<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Http\Request;

class BuyController extends Controller
{
    /**
     * Display pending buys (status=0) for assignment.
     */
    public function index()
    {
        $buys = Buy::with('details.product')
            ->where('status', '0')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Deliveries that are active (status=1 = libre)
        $deliveries = Delivery::where('status', '1')->get();
        
        return view('admin.buys.index', compact('buys', 'deliveries'));
    }

    /**
     * Show all buys with details.
     */
    public function all()
    {
        $buys = Buy::with('details.product', 'delivery')->orderBy('created_at', 'desc')->get();
        return view('admin.buys.all', compact('buys'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource with details and receipt.
     */
    public function show(Buy $buy)
    {
        $buy->load('details.product', 'delivery');
        return view('admin.buys.show', compact('buy'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Buy $buy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Buy $buy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Buy $buy)
    {
        //
    }

    /**
     * Assign a delivery to a pending buy.
     */
    public function assignDelivery(Request $request, Buy $buy)
    {
        $request->validate([
            'delivery_id' => 'required|exists:deliveries,id',
        ]);

        $delivery = Delivery::findOrFail($request->delivery_id);

        // Check delivery is available
        if ($delivery->status != '1') {
            return redirect()->back()->with('error', 'El delivery seleccionado no está disponible');
        }

        $buy->update([
            'delivery_id' => $delivery->id,
            'status' => '1', // En camino
        ]);

        return redirect()->route('admin.buys.index')
            ->with('success', 'Pedido asignado a ' . $delivery->full_name . ' correctamente');
    }
}