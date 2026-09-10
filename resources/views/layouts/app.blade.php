<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Gestión de Clientes</title>
    <!-- Tailwind CSS desde CDN para estilos rápidos -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">
    <nav class="bg-black text-white p-4 mb-6 shadow-md">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="{{ url('/') }}" class="font-bold text-lg tracking-wider">SISTEMA CRM</a>
            <a href="{{ url('/') }}" class="text-xs bg-gray-800 hover:bg-gray-700 text-white px-3 py-1.5 rounded transition">
                ← Volver al Panel
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </main>
</body>
</html>