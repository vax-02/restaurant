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
                        @if($buy->status != '-1')
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#cancelModal{{ $buy->id }}">
                                <i class="fas fa-ban"></i> Anular Pedido
                            </button>
                        @endif
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
                                        @if($buy->status == '-1' && $buy->cancel_reason)
                                            <br><small class="text-danger"><i class="fas fa-info-circle"></i> Motivo: {{ $buy->cancel_reason }}</small>
                                        @endif
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

    @if($buy->status != '-1')
        <!-- Cancel Modal -->
        <div class="modal fade" id="cancelModal{{ $buy->id }}" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title" id="cancelModalLabel">
                            <i class="fas fa-ban"></i> Anular Pedido #{{ $buy->id }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.buys.cancel', $buy) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Esta acción anulará el pedido y <strong>restaurará el stock</strong> de los productos.
                            </div>
                            <div class="form-group">
                                <label for="cancel_reason">Motivo de anulación:</label>
                                <select name="cancel_reason" id="cancel_reason" class="form-control" required>
                                    <option value="">Seleccionar motivo...</option>
                                    <option value="Comprobante inválido">Comprobante inválido</option>
                                    <option value="Cliente canceló">Cliente canceló</option>
                                    <option value="Error en el pedido">Error en el pedido</option>
                                    <option value="Producto no disponible">Producto no disponible</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de anular este pedido? El stock será restaurado.')">
                                <i class="fas fa-ban"></i> Anular Pedido
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@stop
