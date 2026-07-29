@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Dashboard</h1>
        <div class="btn-group">
            <a href="{{ route('admin.dashboard', ['period' => 'today']) }}" class="btn btn-sm {{ $period == 'today' ? 'btn-secondary' : 'btn-outline-secondary' }}">Hoy</a>
            <a href="{{ route('admin.dashboard', ['period' => 'week']) }}" class="btn btn-sm {{ $period == 'week' ? 'btn-secondary' : 'btn-outline-secondary' }}">Esta Semana</a>
            <a href="{{ route('admin.dashboard', ['period' => 'month']) }}" class="btn btn-sm {{ $period == 'month' ? 'btn-secondary' : 'btn-outline-secondary' }}">Este Mes</a>
            <a href="{{ route('admin.dashboard', ['period' => 'all']) }}" class="btn btn-sm {{ $period == 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">Todo</a>
        </div>
    </div>
@stop

@section('content')
    <!-- KPI Cards -->
    <div class="row">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalOrders }}</h3>
                    <p>Total Pedidos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <a href="{{ route('admin.buys.index') }}" class="small-box-footer">
                    Ver todos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $todayOrders }}</h3>
                    <p>Pedidos Hoy</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <a href="{{ route('admin.buys.index') }}" class="small-box-footer">
                    Ver hoy <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>Bs {{ number_format($totalRevenue, 2) }}</h3>
                    <p>Ingresos Totales</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <a href="{{ route('admin.buys.index') }}" class="small-box-footer">
                    Más info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>Bs {{ number_format($todayRevenue, 2) }}</h3>
                    <p>Ingresos Hoy</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <a href="{{ route('admin.buys.index') }}" class="small-box-footer">
                    Más info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $activeDeliveries }}</h3>
                    <p>Repartidores Activos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-motorcycle"></i>
                </div>
                <a href="{{ route('admin.deliveries.index') }}" class="small-box-footer">
                    Ver repartidores <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $totalDeliveries }}</h3>
                    <p>Total Repartidores</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('admin.deliveries.index') }}" class="small-box-footer">
                    Ver todos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Top Products y Top Deliveries -->
    <div class="row">
        <!-- Top Productos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy mr-1"></i>
                        Top 5 Productos Más Vendidos
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Vendidos</th>
                                <th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $index => $product)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $product->product->name ?? 'Producto #' . $product->product_id }}</td>
                                    <td><span class="badge badge-success">{{ $product->total_sold }}</span></td>
                                    <td>Bs {{ number_format($product->total_revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay productos vendidos aún</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Repartidores -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-motorcycle mr-1"></i>
                        Top 5 Repartidores
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Entregas Realizadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topDeliveries as $index => $delivery)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $delivery->name }} {{ $delivery->lastname }}</td>
                                    <td><span class="badge badge-info">{{ $delivery->buys_count }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No hay entregas registradas aún</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Pedidos Recientes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock mr-1"></i>
                        Pedidos Recientes
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <a href="{{ route('admin.buys.index') }}" class="btn btn-sm btn-primary ml-2">
                            Ver todos <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Repartidor</th>
                                    <th>Fecha</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td>#{{ $order->id }}</td>
                                        <td>{{ $order->client ?? 'N/A' }}</td>
                                        <td>Bs {{ number_format($order->total_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $statusBadge = match((int)$order->status) {
                                                    0 => 'warning',
                                                    1 => 'info',
                                                    2 => 'success',
                                                    -1 => 'danger',
                                                    default => 'secondary'
                                                };
                                                $statusText = match((int)$order->status) {
                                                    0 => 'Pendiente',
                                                    1 => 'En Camino',
                                                    2 => 'Entregado',
                                                    -1 => 'Cancelado',
                                                    default => 'Desconocido'
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $statusBadge }}">{{ $statusText }}</span>
                                        </td>
                                        <td>{{ $order->delivery->name ?? 'Sin repartidor' }}</td>
                                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.buys.show', $order->id) }}" class="btn btn-xs btn-info">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay pedidos recientes</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards Row -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-hourglass-half"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pendientes</span>
                    <span class="info-box-number">{{ $pendingOrders }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-truck"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">En Camino</span>
                    <span class="info-box-number">{{ $inProgressOrders }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Entregados Hoy</span>
                    <span class="info-box-number">{{ $deliveredToday }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Cancelados</span>
                    <span class="info-box-number">{{ $cancelledOrders }}</span>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .small-box .inner h3 {
        font-size: 1.8rem;
        font-weight: 700;
    }
    .small-box .inner p {
        font-size: 0.9rem;
    }
    .info-box {
        min-height: 90px;
    }
    .info-box-icon {
        font-size: 1.5rem;
    }
    .table td, .table th {
        vertical-align: middle;
    }
    .badge {
        font-size: 0.8rem;
        padding: 0.4em 0.6em;
    }
    .btn-group .btn {
        font-size: 0.8rem;
    }
    @media (max-width: 768px) {
        .small-box .inner h3 {
            font-size: 1.3rem;
        }
        .content-header h1 {
            font-size: 1.5rem;
        }
    }
</style>
@stop