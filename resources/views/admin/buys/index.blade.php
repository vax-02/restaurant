@extends('adminlte::page')

@section('title', 'Pedidos Pendientes')

@section('content_header')
    <h1>Pedidos Pendientes <small>(para asignar delivery)</small></h1>
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
            <h3 class="card-title">Pedidos con estado Pendiente </h3>
            <div class="card-tools">
                <a href="{{ route('admin.buys.all') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-list"></i> Todos los Pedidos
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
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Comprobante</th>
                        <th>Fecha</th>
                        <th>Asignar Delivery</th>
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
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailsModal{{ $buy->id }}">
                                    <i class="fas fa-eye"></i> Ver detalles
                                </button>
                            </td>
                            <td><strong>Bs. {{ number_format($buy->total, 2) }}</strong></td>
                            <td>
                                @if($buy->comprobante)
                                    <a href="{{ asset('storage/' . $buy->comprobante) }}" target="_blank" class="btn btn-sm btn-success">
                                        <i class="fas fa-image"></i> Ver
                                    </a>
                                @else
                                    <span class="text-muted">Sin comprobante</span>
                                @endif
                            </td>
                            <td>{{ $buy->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <form action="{{ route('admin.buys.assign-delivery', $buy) }}" method="POST" class="form-inline">
                                        @csrf
                                        @method('PUT')
                                        <select name="delivery_id" class="form-control form-control-sm mr-1" required>
                                            <option value="">Seleccionar...</option>
                                            @foreach($deliveries as $delivery)
                                                <option value="{{ $delivery->id }}">
                                                    {{ $delivery->full_name }} ({{ $delivery->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-motorcycle"></i> Asignar
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#cancelModal{{ $buy->id }}" title="Anular pedido">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Detalles del Pedido -->
                        <div class="modal fade" id="detailsModal{{ $buy->id }}" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel{{ $buy->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="detailsModalLabel{{ $buy->id }}">
                                            Detalles del Pedido #{{ $buy->id }}
                                        </h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <strong>Cliente:</strong> {{ $buy->client ?? 'Sin cliente' }}<br>
                                                <strong>Tipo:</strong> {{ $buy->type == 'delivery' ? 'Delivery' : 'Restaurant' }}<br>
                                                <strong>Fecha:</strong> {{ $buy->created_at->format('d/m/Y H:i') }}
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Estado:</strong> 
                                                <span class="badge badge-{{ $buy->status_color }}">{{ $buy->status_text }}</span><br>
                                                <strong>Total:</strong> Bs. {{ number_format($buy->total, 2) }}
                                            </div>
                                        </div>

                                        @if($buy->comprobante)
                                            <div class="text-center mb-3">
                                                <strong>Comprobante:</strong><br>
                                                <a href="{{ asset('storage/' . $buy->comprobante) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $buy->comprobante) }}" alt="Comprobante" class="img-fluid img-thumbnail" style="max-height: 300px;">
                                                </a>
                                            </div>
                                        @endif

                                        <h6>Productos:</h6>
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Precio</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($buy->details as $detail)
                                                    <tr>
                                                        <td>{{ $detail->product->name ?? 'Producto eliminado' }}</td>
                                                        <td>Bs. {{ number_format($detail->price, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Total</th>
                                                    <th>Bs. {{ number_format($buy->total, 2) }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay pedidos pendientes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cancel Modals -->
    @foreach($buys as $buy)
        <div class="modal fade" id="cancelModal{{ $buy->id }}" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel{{ $buy->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title" id="cancelModalLabel{{ $buy->id }}">
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
                                <label for="cancel_reason{{ $buy->id }}">Motivo de anulación:</label>
                                <select name="cancel_reason" id="cancel_reason{{ $buy->id }}" class="form-control" required>
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
    @endforeach
@stop
