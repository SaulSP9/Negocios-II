@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <a href="{{ route('clientes.index') }}" class="text-decoration-none text-dark fw-bold">← Regresar a Clientes</a>
    </div>

    <div class="card shadow-sm p-4 border-0">
        <h3 class="fw-bold mb-4">REGISTRAR NUEVO CLIENTE</h3>

        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nombre Completo *</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required placeholder="Ej. Alex Ramírez">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Correo Electrónico *</label>
                    <input type="email" name="correo" class="form-control" value="{{ old('correo') }}" required placeholder="alex@email.com">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}" placeholder="4491234567">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Empresa</label>
                    <input type="text" name="empresa" class="form-control" value="{{ old('empresa') }}" placeholder="Nombre de la empresa">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activo" {{ old('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Etapa CRM</label>
                    <select name="etapa_crm" class="form-select">
                        <option value="Prospecto" {{ old('etapa_crm') == 'Prospecto' ? 'selected' : '' }}>Prospecto</option>
                        <option value="Activo" {{ old('etapa_crm') == 'Activo' ? 'selected' : '' }}>Activo</option>
                        <option value="Frecuente" {{ old('etapa_crm') == 'Frecuente' ? 'selected' : '' }}>Frecuente</option>
                        <option value="Inactivo" {{ old('etapa_crm') == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark px-4 py-2 fw-bold">GUARDAR CLIENTE</button>
            </div>
        </form>
    </div>
</div>
@endsection