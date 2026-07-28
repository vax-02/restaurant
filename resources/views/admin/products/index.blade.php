@extends('adminlte::page')

@section('title', 'Productos')

@section('content_header')
    <h1>Gestión de Productos</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de Productos</h3>
            <div class="card-tools">
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Disponible</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" 
                                         alt="{{ $product->name }}" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                @else
                                    <div class="bg-secondary text-white text-center" 
                                         style="width: 50px; height: 50px; line-height: 50px; border-radius: 4px;">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                <br><small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $product->category === 'plate' ? 'primary' : 'info' }}">
                                    {{ $product->category === 'plate' ? '🍽️ Plato' : '🥤 Líquido' }}
                                </span>
                            </td>
                            <td><strong>Bs/ {{ number_format($product->price, 2) }}</strong></td>
                            <td>
                                @if($product->available)
                                    <span class="badge badge-success">✅ Disponible</span>
                                @else
                                    <span class="badge badge-danger">❌ No disponible</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('admin.products.toggle', $product) }}" 
                                      method="POST" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-{{ $product->available ? 'warning' : 'success' }} btn-sm" 
                                            title="{{ $product->available ? 'Deshabilitar' : 'Habilitar' }}">
                                        <i class="fas fa-{{ $product->available ? 'times' : 'check' }}"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.products.edit', $product) }}" 
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        data-toggle="modal" data-target="#modalDelete{{ $product->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modales --}}
    @foreach($products as $product)
        <div class="modal fade" id="modalDelete{{ $product->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de eliminar el producto?</p>
                        <div class="alert alert-warning">
                            <strong>{{ $product->name }}</strong><br>
                            Precio: Bs/ {{ number_format($product->price, 2) }}<br>
                            Categoría: {{ $product->category === 'plate' ? 'Plato' : 'Líquido' }}
                        </div>
                        <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Sí, eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@stop

@section('js')
    <script>
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 1000);
            });
        }, 5000);
    </script>
@stop