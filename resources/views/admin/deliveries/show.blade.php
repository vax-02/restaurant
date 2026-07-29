@extends('adminlte::page')

@section('title', 'Detalles del Delivery')

@section('content_header')
    <h1>Detalles del Delivery</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        {{-- Datos del Delivery --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información del Delivery</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.deliveries.edit', $delivery) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 40%">Nombre Completo</th>
                            <td>{{ $delivery->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Celular</th>
                            <td>{{ $delivery->cellphone }}</td>
                        </tr>
                        <tr>
                            <th>Usuario Telegram</th>
                            <td>{{ $delivery->user_telegram }}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                @if($delivery->status)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Registrado</th>
                            <td>{{ $delivery->created_at }}</td>
                        </tr>
                        <tr>
                            <th>Última actualización</th>
                            <td>{{ $delivery->updated_at }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Código del Delivery --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Código del Delivery</h3>
                </div>
                <div class="card-body text-center">
                    <div class="p-4 mb-3" style="background: #f8f9fa; border-radius: 10px; border: 2px dashed #6c757d;">
                        <h2 class="font-weight-bold" style="letter-spacing: 3px; font-family: monospace; font-size: 2rem;">
                            {{ $delivery->code }}
                        </h2>
                        <p class="text-muted mb-0">Código único de identificación</p>
                    </div>
                    <form action="{{ route('admin.deliveries.regenerate-code', $delivery) }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-info" onclick="return confirm('¿Estás seguro de regenerar el código? El código anterior dejará de ser válido.')">
                            <i class="fas fa-sync-alt"></i> Regenerar Código
                        </button>
                    </form>
                </div>
            </div>

            {{-- Estadísticas rápidas --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Estadísticas</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total entregas</span>
                                    <span class="info-box-number">{{ $delivery->total_buys_count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pendientes</span>
                                    <span class="info-box-number">{{ $delivery->pending_buys_count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-truck"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">En Camino</span>
                                    <span class="info-box-number">{{ $delivery->in_progress_buys_count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Entregados</span>
                                    <span class="info-box-number">{{ $delivery->completed_buys_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Listado de Compras (Buys) relacionadas --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historial de Compras (Entregas)</h3>
        </div>
        <div class="card-body table-responsive p-0">
            @if($buys->count() > 0)
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Comprobante</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($buys as $buy)
                            <tr>
                                <td>{{ $buy->id }}</td>
                                <td>{{ $buy->comprobante }}</td>
                                <td>{{ $buy->client ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-{{ $buy->type == 'delivery' ? 'primary' : 'secondary' }}">
                                        {{ ucfirst($buy->type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $buy->status_color }}">
                                        {{ $buy->status_text }}
                                    </span>
                                </td>
                                <td>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($buy->details as $detail)
                                            <li>
                                                <small>
                                                    {{ $detail->product->name ?? 'Producto' }} 
                                                    - Bs. {{ number_format($detail->price, 2) }}
                                                </small>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td><strong>Bs. {{ number_format($buy->total, 2) }}</strong></td>
                                <td>{{ $buy->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center p-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Este delivery no tiene compras registradas aún.</p>
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('admin.deliveries.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al listado
    </a>
@stop