@extends('adminlte::page')

@section('title', 'Todos los Pedidos')

@section('content_header')
    <h1>Todos los Pedidos</h1>
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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado General de Pedidos</h3>
            <div class="card-tools">
                <a href="{{ route('admin.buys.index') }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-clock"></i> Pedidos Pendientes
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Delivery</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Comprobante</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buys as $buy)
                        <tr>
                            <td>{{ $buy->id }}</td>
                            <td>{{ $buy->client ?? 'Sin cliente' }}</td>
                            <td>
                                <span class="badge badge-{{ $buy->type == 'delivery' ? 'primary' : 'success' }}">
                                    {{ $buy->type == 'delivery' ? 'Delivery' : 'Restaurant' }}
                                </span>
                            </td>
                            <td>
                                @if($buy->delivery)
                                    {{ $buy->delivery->full_name }}
                                @else
                                    <span class="text-muted">No asignado</span>
                                @endif
                            </td>
                            <td><strong>Bs. {{ number_format($buy->total, 2) }}</strong></td>
                            <td>
                                <span class="badge badge-{{ $buy->status_color }}">{{ $buy->status_text }}</span>
                            </td>
                            <td>
                                @if($buy->comprobante)
                                    <a href="{{ asset('storage/' . $buy->comprobante) }}" target="_blank" class="btn btn-sm btn-success" title="Ver comprobante">
                                        <i class="fas fa-image"></i>
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $buy->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.buys.show', $buy) }}" class="btn btn-info btn-sm" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No hay pedidos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop