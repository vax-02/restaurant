<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Models\BuyDetail;
use App\Models\Delivery;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'all');
        
        // Base date filter
        $dateFilter = match($period) {
            'today' => [Carbon::today(), Carbon::today()],
            'week' => [Carbon::today()->subDays(6), Carbon::today()],
            'month' => [Carbon::today()->startOfMonth(), Carbon::today()],
            default => [null, null],
        };
        
        $totalOrders = Buy::count();
        $todayOrders = Buy::whereDate('created_at', today())->count();
        $pendingOrders = Buy::where('status', '0')->count();
        $inProgressOrders = Buy::where('status', '1')->where('type', 'delivery')->count();
        $deliveredToday = Buy::whereDate('updated_at', today())->where('status', '2')->count();
        $cancelledOrders = Buy::where('status', '-1')->count();
        
        // Cálculo de ingresos reales sumando los detalles de las compras completadas (status = 2)
        $totalRevenue = BuyDetail::whereHas('buy', function($q) {
            $q->where('status', '2');
        })->sum(DB::raw('price * quantity')) ?? 0;
        
        $todayRevenue = BuyDetail::whereHas('buy', function($q) {
            $q->where('status', '2')->whereDate('updated_at', today());
        })->sum(DB::raw('price * quantity')) ?? 0;
        
        $monthRevenue = BuyDetail::whereHas('buy', function($q) {
            $q->where('status', '2')
              ->whereMonth('updated_at', now()->month)
              ->whereYear('updated_at', now()->year);
        })->sum(DB::raw('price * quantity')) ?? 0;
        
        $totalDeliveries = Delivery::count();
        $activeDeliveries = Delivery::where('status', 1)
            ->whereNotNull('user_telegram')
            ->count();
        
        // Top 5 Repartidores con más pedidos entregados
        $topDeliveries = Delivery::withCount(['buys' => function($q) {
                $q->where('status', '2');
            }])
            ->orderBy('buys_count', 'desc')
            ->limit(5)
            ->get();
        
        // Top 5 Productos más vendidos
        $topProductsQuery = BuyDetail::select(
                'product_id',
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(price * quantity) as total_revenue')
            )
            ->whereHas('buy', function($q) {
                $q->where('status', '2');
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5);
        
        if ($dateFilter[0] && $dateFilter[1]) {
            $topProductsQuery->whereHas('buy', function($q) use ($dateFilter) {
                $q->where('status', '2')
                  ->whereBetween('updated_at', [$dateFilter[0]->startOfDay(), $dateFilter[1]->endOfDay()]);
            });
        }
        $topProducts = $topProductsQuery->get();
        
        // Pedidos Recientes
        $recentOrdersQuery = Buy::with(['delivery', 'details'])
            ->orderBy('created_at', 'desc')
            ->limit(10);
        
        if ($dateFilter[0] && $dateFilter[1]) {
            $recentOrdersQuery->whereBetween('created_at', [$dateFilter[0]->startOfDay(), $dateFilter[1]->endOfDay()]);
        }
        
        $recentOrders = $recentOrdersQuery->get()->map(function($order) {
            // Calcular total acumulado dinámicamente
            $order->total_amount = $order->details->sum(function($detail) {
                return $detail->price * $detail->quantity;
            });
            return $order;
        });

        return view('admin.dashboard', compact(
            'totalOrders',
            'todayOrders',
            'pendingOrders',
            'inProgressOrders',
            'deliveredToday',
            'cancelledOrders',
            'totalRevenue',
            'todayRevenue',
            'monthRevenue',
            'totalDeliveries',
            'activeDeliveries',
            'topDeliveries',
            'topProducts',
            'recentOrders',
            'period'
        ));
    }
}