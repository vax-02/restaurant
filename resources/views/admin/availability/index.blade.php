@extends('adminlte::page')

@section('title', 'Disponibilidad del Día')

@section('content_header')
    <h1>
        <i class="fas fa-calendar-day"></i> Disponibilidad del Día
        <small>{{ today()->format('d/m/Y') }}</small>
    </h1>
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

    {{-- Resumen rápido --}}
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $products->count() }}</h3>
                    <p>Total de productos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $products->where('available', true)->count() }}</h3>
                    <p>Productos disponibles</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $products->filter(fn($p) => $p->today_stock > 0)->count() }}</h3>
                    <p>Con stock hoy</p>
                </div>
                <div class="icon">
                    <i class="fas fa-utensils"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Stock disponible para hoy</h3>
            <div class="card-tools">
                <form action="{{ route('admin.availability.reset') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm" 
                            onclick="return confirm('¿Reiniciar stock a 0 para todos los productos?')">
                        <i class="fas fa-sync"></i> Reiniciar stock
                    </button>
                </form>
                <a href="{{ route('admin.products.index') }}" class="btn btn-info btn-sm">
                    <i class="fas fa-utensils"></i> Ver productos
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.availability.update') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock hoy</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" 
                                                 alt="{{ $product->name }}" 
                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        @else
                                            <div class="bg-secondary text-white text-center" 
                                                 style="width: 40px; height: 40px; line-height: 40px; border-radius: 4px;">
                                                <i class="fas fa-utensils"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $product->name }}</strong>
                                        @if(!$product->available)
                                            <span class="badge badge-danger">No disponible</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $product->category === 'plate' ? 'primary' : 'info' }}">
                                            {{ $product->category === 'plate' ? '🍽️ Plato' : '🥤 Líquido' }}
                                        </span>
                                    </td>
                                    <td><strong>Bs/ {{ number_format($product->price, 2) }}</strong></td>
                                    <td>
                                        <div style="max-width: 120px;">
                                            <input type="number" 
                                                   name="stock[{{ $product->id }}]" 
                                                   class="form-control form-control-sm @error('stock.'.$product->id) is-invalid @enderror"
                                                   value="{{ $product->today_stock }}"
                                                   min="0"
                                                   {{ !$product->available ? 'disabled' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        @if($product->available && $product->today_stock > 0)
                                            <span class="badge badge-success">✅ Disponible</span>
                                        @elseif($product->available && $product->today_stock == 0)
                                            <span class="badge badge-warning">⚠️ Sin stock</span>
                                        @else
                                            <span class="badge badge-danger">❌ Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No hay productos registrados. 
                                        <a href="{{ route('admin.products.create') }}">Crear producto</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($products->count() > 0)
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar disponibilidad
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

@stop

@section('js')
    <script>
        // Auto cerrar mensajes después de 5 segundos
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