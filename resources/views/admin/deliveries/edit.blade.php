@extends('adminlte::page')

@section('title', 'Editar Delivery')

@section('content_header')
    <h1>Editar Delivery</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.deliveries.update', $delivery) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Nombre</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $delivery->name) }}" required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="lastname">Apellido</label>
                    <input type="text" class="form-control @error('lastname') is-invalid @enderror" 
                           id="lastname" name="lastname" value="{{ old('lastname', $delivery->lastname) }}" required>
                    @error('lastname')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="cellphone">Celular</label>
                    <input type="text" class="form-control @error('cellphone') is-invalid @enderror" 
                           id="cellphone" name="cellphone" value="{{ old('cellphone', $delivery->cellphone) }}" required maxlength="8">
                    @error('cellphone')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="user_telegram">Usuario de Telegram</label>
                    <input type="text" class="form-control @error('user_telegram') is-invalid @enderror" 
                           id="user_telegram" name="user_telegram" value="{{ old('user_telegram', $delivery->user_telegram) }}" >
                    @error('user_telegram')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="status" name="status" value="1" 
                               {{ old('status', $delivery->status) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="status">Activo</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Actualizar Delivery</button>
                <a href="{{ route('admin.deliveries.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
@stop