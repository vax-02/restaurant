@extends('adminlte::page')

@section('title', 'Detalle del Pedido #' . $buy->id)

@section('content_header')
    <h1>Detalle del Pedido #{{ $buy->id }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información del Pedido</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.buys.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 40%">ID Pedido</th>
                                    <td>{{ $buy->id }}</td>
                                </tr>
                                <tr>
                                    <th>Cliente</th>
                                    <td>{{ $buy->client ?? 'Sin cliente' }}</td>
                                </tr>
                                <tr>
                                    <th>Tipo</th>
                                    <td>
                                        <span class="badge badge-{{ $buy->type == 'delivery' ? 'primary' : 'success' }}">
                                            {{ $buy->type == 'delivery' ? 'Delivery' : 'Restaurant' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        <span class="badge badge-{{ $buy->status_color }}">{{ $buy->status_text }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Delivery Asignado</th>
                                    <td>
                                        @if($buy->delivery)
                                            {{ $buy->delivery->full_name }} ({{ $buy->delivery->code }})
                                        @else
                                            <span class="text-muted">No asignado</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha</th>
                                    <td>{{ $buy->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($buy->comprobante)
                                <div class="text-center">
                                    <strong>Comprobante:</strong>
                                    <a href="{{ asset('storage/' . $buy->comprobante) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $buy->comprobante) }}" alt="Comprobante" class="img-fluid img-thumbnail" style="max-height: 300px; cursor: pointer;">
                                    </a>
                                    <br>
                                    <small class="text-muted">Click para abrir en nueva pestaña</small>
                                </div>
                            @else
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle"></i> Sin comprobante
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <h4>Productos del Pedido</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($buy->details as $index => $detail)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $detail->product->name ?? 'Producto eliminado' }}</td>
                                        <td>
                                            @if($detail->product && $detail->product->category)
                                                <span class="badge badge-{{ $detail->product->category == 'bebida' ? 'info' : 'warning' }}">
                                                    {{ $detail->product->category == 'bebida' ? 'Bebida' : 'Plato' }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>Bs. {{ number_format($detail->price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">TOTAL</th>
                                    <th>Bs. {{ number_format($buy->total, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop