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
        
        $updatedToday = Buy::whereDate('updated_at', today())->where('status', '2')->count();
        
        $cancelledOrders = Buy::where('status', '-1')->count();
        
        // Filtered revenue
        /*$revenueQuery = Buy::where('status', '2');
        if ($dateFilter[0] && $dateFilter[1]) {
            $revenueQuery->whereBetween('updated_at', [$dateFilter[0]->startOfDay(), $dateFilter[1]->endOfDay()]);
        }*/
        //$filteredRevenue = $revenueQuery->sum('total') ?? 0;
        
        $totalRevenue = Buy::where('status', '2')->count() ?? 0;
        
        $todayRevenue = Buy::where('status', '2')
            ->whereDate('updated_at', today())
            ->count() ?? 0;
        
        $monthRevenue = Buy::where('status', '2')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count() ?? 0;
        
        $totalDeliveries = Delivery::count();
        $activeDeliveries = Delivery::where('status', 1)
            ->whereNotNull('user_telegram')
            ->count();
        
        // Repartidor con más pedidos
        $topDelivery = Delivery::withCount('buys')
            ->orderBy('buys_count', 'desc')
            ->first();
        
        // Filtered top products
        $topProductsQuery = BuyDetail::select(
                'product_id',
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(price * quantity) as total_revenue')
            )
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
        
        // Filtered recent orders
        $recentOrdersQuery = Buy::with(['delivery'])
            ->orderBy('created_at', 'desc')
            ->limit(10);
        
        if ($dateFilter[0] && $dateFilter[1]) {
            $recentOrdersQuery->whereBetween('created_at', [$dateFilter[0]->startOfDay(), $dateFilter[1]->endOfDay()]);
        }
        $recentOrders = $recentOrdersQuery->get();
        
        $salesData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $salesData[] = [
                'date' => $date->format('d/m'),
                'count' => Buy::where('status', '2')
                    ->whereDate('updated_at', $date)
                    ->count(),
                'revenue' => Buy::where('status', '2')
                    ->whereDate('updated_at', $date)
                    ->count() ?? 0,
            ];
        }
        
        $statusData = [
            'pendientes' => Buy::where('status', '0')->count(),
            'en_camino' => Buy::where('status', '1')->count(),
            'entregados' => Buy::where('status', '2')->count(),
            'cancelados' => Buy::where('status', '-1')->count(),
        ];
        
        $monthlySales = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::today()->subMonths($i);
            $monthlySales[] = [
                'month' => $date->format('M'),
                'revenue' => Buy::where('status', '2')
                    ->whereMonth('updated_at', $date->month)
                    ->whereYear('updated_at', $date->year)
                    ->count() ?? 0,
            ];
        }
        
        return view('admin.dashboard', compact(
            'totalOrders',
            'todayOrders',
            'pendingOrders',
            'inProgressOrders',
            'updatedToday',
            'cancelledOrders',
            'totalRevenue',
            'todayRevenue',
            'monthRevenue',
            'totalDeliveries',
            'activeDeliveries',
            'topDelivery',
            'topProducts',
            'recentOrders',
            'salesData',
            'statusData',
            'monthlySales',
            'period'
        ));
    }
}