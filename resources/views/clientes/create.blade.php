<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HF.ADMIN - Registrar Cliente</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Helvetica Neue', Arial, sans-serif; }
        body { background-color: #f4f4f4; color: #000; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #ffffff; width: 100%; max-width: 500px; padding: 40px; border: 2px solid #000; box-shadow: 6px 6px 0px #000; }
        h2 { font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: -0.5px; margin-bottom: 25px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; color: #333; }
        input, select { width: 100%; padding: 12px; border: 1px solid #000; background: #fafafa; font-size: 14px; font-weight: 600; outline: none; border-radius: 0; }
        input:focus, select:focus { background: #fff; border-width: 2px; }
        .btn-submit { width: 100%; background: #000; color: #fff; border: none; padding: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #333; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #000; text-decoration: none; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .alert-error { background: #ff0033; color: #fff; padding: 10px; font-size: 12px; font-weight: bold; margin-bottom: 20px; list-style: none; }
    </style>
</head>
<body>

    <div class="card">
        <h2>REGISTRAR NUEVO CLIENTE</h2>

        @if ($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label>Nombre Completo *</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Ej. ALEXIA RUIZ">
            </div>

            <div class="form-group">
                <label>Correo Electrónico *</label>
                <input type="email" name="correo" value="{{ old('correo') }}" required placeholder="cliente@hfstudios.com">
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono') }}" placeholder="4491234567">
            </div>

            <div class="form-group">
                <label>Empresa / Marca</label>
                <input type="text" name="empresa" value="{{ old('empresa') }}" placeholder="Opcional">
            </div>

            <div class="form-group">
                <label>Etapa CRM</label>
                <select name="etapa_crm">
                    <option value="Prospecto">Prospecto</option>
                    <option value="Activo" selected>Activo</option>
                    <option value="Frecuente">Frecuente</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">GUARDAR CLIENTE</button>
            <a href="{{ route('admin.dashboard') }}" class="btn-cancel">← CANCELAR Y VOLVER</a>
        </form>
    </div>

</body>
</html>