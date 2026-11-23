@if (session('password_reset'))
    <p class="account-status" role="status">{{ session('password_reset') }}</p>
@endif

<div id="account-guest" class="account-access" hidden>
    <div class="account-access-content">
        <div class="account-auth">
            <h1 class="account-title" tabindex="-1">Mi cuenta<span>.</span></h1>
            <nav class="account-auth-nav" aria-label="Acceso a Mi cuenta">
                <a href="/?vista=cuenta" data-account-mode="login">Iniciar sesión</a>
                <a href="/?vista=registro" data-account-mode="register">Crear cuenta</a>
            </nav>
            <form class="form-stack" id="login-form" aria-label="Iniciar sesión">
                <label>Correo electrónico <input type="email" name="email" autocomplete="username" required></label>
                <label>Contraseña <input type="password" name="password" autocomplete="current-password" required></label>
                <button class="button primary">Iniciar sesión</button>
                <button type="button" class="text-link account-recover" id="recover-password">¿Olvidaste tu contraseña?</button>
                @if ($demo)
                    <button type="button" class="button" id="demo-login">Acceso rápido con Cliente Demo</button>
                @endif
            </form>
            <form class="form-stack" id="register-form" aria-label="Crear cuenta" hidden>
                <label>Nombre <input name="name" autocomplete="name" minlength="2" maxlength="120" required></label>
                <label>Correo electrónico <input type="email" name="email" autocomplete="username" required></label>
                <label>Contraseña de 12 caracteres o más <input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required></label>
                <label>Confirmar contraseña <input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required></label>
                <button class="button primary">Crear cuenta</button>
            </form>
        </div>
    </div>
    <figure class="account-photo">
        <img src="{{ asset(\App\Support\Editorial::section('cuenta')) }}" alt="" width="1150" height="640" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallback }}'">
    </figure>
</div>

<div id="account-member" class="account-member" hidden>
    <div class="account-rail">
        <h1 class="account-title" tabindex="-1">Mi cuenta<span>.</span></h1>
        <div class="account-identity"><p id="account-name"></p><p id="account-email"></p></div>
        <nav class="account-nav" id="account-client" aria-label="Secciones de Mi cuenta" hidden>
            <a data-account-section="mis-datos" href="/?vista=cuenta#mis-datos">Mis datos</a>
            <a data-account-section="mi-monedero" href="/?vista=cuenta#mi-monedero">Mi monedero</a>
            <a data-account-section="mis-pedidos" href="/?vista=cuenta#mis-pedidos">Mis pedidos</a>
            <a data-account-section="mis-tickets" href="/?vista=cuenta#mis-tickets">Mis tickets</a>
        </nav>
        <div class="account-actions">
            <a class="button" href="/gestion" data-staff-link hidden>Gestión empresarial ↗</a>
            <button type="button" class="button" id="logout">Cerrar sesión</button>
        </div>
    </div>
    <div class="account-stage" id="account-stage" hidden>
        <section class="account-panel" id="mis-datos" data-account-panel aria-labelledby="account-profile-heading" hidden>
            <h2 id="account-profile-heading">Mis datos</h2>
            <form class="form-stack" id="profile-form">
                <label>Nombre <input name="nombre" autocomplete="name" required minlength="2" maxlength="120"></label>
                <label>Teléfono <input name="telefono" type="tel" autocomplete="tel" maxlength="25"></label>
                <label>Dirección <input name="direccion" autocomplete="street-address" maxlength="255"></label>
                <div class="account-fields">
                    <label>Ciudad <input name="ciudad" autocomplete="address-level2" maxlength="120"></label>
                    <label>Código postal <input name="codigo_postal" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5"></label>
                </div>
                <button class="button primary">Guardar perfil</button>
            </form>
        </section>
        <section class="account-panel" id="mi-monedero" data-account-panel aria-labelledby="account-wallet-heading" hidden>
            <div class="wallet-summary"><h2 id="account-wallet-heading">Mi monedero</h2><strong id="account-wallet"></strong></div>
            @if (config('commerce.simulate_payments'))
                <details class="ticket-reply" id="wallet-recharge">
                    <summary>Recargar saldo de demostración</summary>
                    <form class="form-stack" id="recharge-form">
                        <div class="actions" aria-label="Importes de recarga">
                            @foreach ([100, 250, 500, 1000] as $amount)
                                <button type="button" class="button" data-recharge-amount="{{ $amount }}">${{ number_format($amount) }}</button>
                            @endforeach
                        </div>
                        <label>Importe MXN <input name="importe" type="number" min="10" max="5000" step=".01" required></label>
                        <label>Método <select name="metodo"><option value="tarjeta">Tarjeta (simulación)</option><option value="paypal">PayPal (simulación)</option><option value="transferencia">Transferencia (simulación)</option></select></label>
                        <button class="button primary">Confirmar recarga</button>
                    </form>
                </details>
            @endif
            <h3 class="wallet-history-title">Movimientos del monedero</h3>
            <div id="wallet-history" aria-live="polite"></div>
        </section>
        <section class="account-panel" id="mis-pedidos" data-account-panel aria-labelledby="account-orders-heading" hidden>
            <h2 id="account-orders-heading">Mis pedidos</h2>
            <div class="stack" id="account-orders" aria-live="polite"></div>
        </section>
        <section class="account-panel" id="mis-tickets" data-account-panel aria-labelledby="account-tickets-heading" hidden>
            <h2 id="account-tickets-heading">Mis tickets</h2>
            <a class="text-link account-new-ticket" href="/?vista=ayuda">Crear un ticket ↗</a>
            <div class="stack" id="account-tickets" aria-live="polite"></div>
        </section>
    </div>
</div>
