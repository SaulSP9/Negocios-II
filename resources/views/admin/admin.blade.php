<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-black" style="width: 250px; min-height: 100vh;">
    <!-- Logo y Saludo -->
    <a href="{{ route('admin.dashboard') }}" class="text-white text-decoration-none mb-1">
        <h3 class="fw-bold m-0" style="letter-spacing: -1px;">HF.ADMIN</h3>
    </a>
    <small class="text-secondary mb-4">HOLA, {{ strtoupper(Auth::user()->name ?? 'USUARIO') }}</small>

    <!-- Navegación Principal -->
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-1">
            <a href="{{ route('admin.dashboard') }}" class="nav-link text-white fw-bold {{ request()->routeIs('admin.dashboard') ? 'bg-dark' : '' }}">
                📦 PEDIDOS
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="#" class="nav-link text-secondary fw-bold">
                🏷️ PRODUCTOS
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="{{ route('clientes.index') }}" class="nav-link text-white fw-bold {{ request()->routeIs('clientes.*') ? 'bg-dark' : '' }}">
                👥 CLIENTES
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="#" class="nav-link text-secondary fw-bold">
                🧾 FACTURAS
            </a>
        </li>
    </ul>

    <hr class="border-secondary my-3">

    <!-- Botones de Acción -->
    <div class="d-grid gap-2">
        <a href="{{ route('tienda') }}" class="btn btn-outline-light w-100 fw-bold border-2">
            VER TIENDA
        </a>

        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-danger w-100 fw-bold">
                CERRAR SESIÓN
            </button>
        </form>
    </div>
</div>