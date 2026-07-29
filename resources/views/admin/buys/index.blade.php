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
                    <tr class="text-center">
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Comprobante</th>
                        <th>Fecha</th>
                        <th>Asignar Delivery</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buys as $buy)
                        <tr class="text-center">
                            <td>{{ $buy->id }}</td>
                            <td>{{ $buy->client ?? 'Sin cliente' }}</td>
                            <td>
                                <span class="badge badge-{{ $buy->type == 'delivery' ? 'primary' : 'success' }}">
                                    {{ $buy->type == 'delivery' ? 'Delivery' : 'Restaurant' }}
                                </span>
                            </td>
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
                                    @if ( $buy->type == 'delivery' )
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
                                            
                                    @else
                                        <form action="{{ route('admin.buys.atender', $buy->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-check"></i> Atendido
                                            </button>
                                        </form>
                                            
                                    @endif
                                    <button type="button" class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#cancelModal{{ $buy->id }}" title="Anular pedido">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr class="text-center">
                            <th colspan="2">Producto</th>
                            <th colspan="2">Precio</th>
                            <th colspan="2">Cantidad</th>
                        </tr>
                        @php
                            $total = 0;
                        @endphp
                        @foreach($buy->details as $detail)
                            @php
                                $total += $detail->price * $detail->quantity
                            @endphp
                            <tr class="text-center">
                                <td colspan="2">{{ $detail->product->name ?? 'Producto eliminado' }}</td>
                                <td colspan="2">Bs. {{ number_format($detail->price, 2) }}</td>
                                <td colspan="2">{{ $detail->quantity }}</td>
                            </tr>
                            @endforeach
                            <tr class="text-center " style="border-bottom: 2px dashed black;">
                                <th colspan="4">Total</th>
                                <th colspan="2">Bs. {{ number_format($total, 2) }}</th>
                            </tr>
                                          
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay pedidos pendientes</td>
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
