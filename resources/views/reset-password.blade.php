<!doctype html>
<html lang="es-MX"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HF Studios · Restablecer contraseña</title>
    <link rel="stylesheet" href="{{ asset('css/editorial.css') }}">
</head><body><main class="shell view">
    <form class="card form-stack" method="post" action="/auth/restablecer">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <h1>Restablecer contraseña</h1>
        @foreach ($errors->all() as $error)<p role="alert">{{ $error }}</p>@endforeach
        <label>Correo electrónico <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username"></label>
        <label>Contraseña nueva <input type="password" name="password" minlength="12" maxlength="72" required autocomplete="new-password"></label>
        <label>Confirmar contraseña <input type="password" name="password_confirmation" minlength="12" maxlength="72" required autocomplete="new-password"></label>
        <button class="button primary">Guardar contraseña</button>
        <a href="/?vista=cuenta">Volver a Mi cuenta</a>
    </form>
</main></body></html>
