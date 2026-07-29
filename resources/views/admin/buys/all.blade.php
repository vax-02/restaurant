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
                            <td>
                                
                                <span class="badge badge-{{ $buy->status_color }}">{{ $buy->status_text }}</span>
                                @if($buy->status == '-1' && $buy->cancel_reason)
                                    <br><small class="text-danger"><i class="fas fa-info-circle"></i> {{ $buy->cancel_reason }}</small>
                                @endif
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

    <!-- Cancel Modals -->
    @foreach($buys as $buy)
        @if($buy->status != '-1')
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
        @endif
    @endforeach
@stop
