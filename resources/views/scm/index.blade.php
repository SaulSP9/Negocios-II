<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>HFSTUDIOS | SCM</title>
    <link rel="stylesheet" href="/css/scm.css">
    <script src="/js/http.js" defer></script><script src="/js/scm.js" defer></script>
</head>
<body data-role="{{ auth()->user()->role }}">
<header><a class="brand" href="/">HFSTUDIOS <small>SCM</small></a><div><a href="/crm">CRM</a><span>{{ auth()->user()->name }} · {{ auth()->user()->role }}</span><button id="logout">Salir</button></div></header>
<nav aria-label="Módulos SCM"><button data-tab="dashboard" aria-current="page">Resumen</button><button data-tab="catalog">Catálogo e inventario</button><button data-tab="suppliers">Proveedores</button><button data-tab="orders">Pedidos SCM</button><button data-tab="maturity">Madurez</button></nav>
<main>
    <div class="page-title"><div><p class="eyebrow">ETAPA 2 · CADENA DE SUMINISTRO</p><h1>Inventario con trazabilidad.</h1><p>Consulta existencias, recibe pedidos y compara las estrategias Push y Pull.</p></div><button id="refresh">Actualizar datos</button></div>
    <div id="notice" role="status" aria-live="polite" hidden></div>
    <section id="dashboard" class="panel">
        <div id="counters" class="cards" aria-label="Indicadores de inventario"></div>
        <div class="section-heading"><h2>Reportes</h2><a class="button" href="/scm/reportes/csv">Descargar CSV</a></div>
        <div class="two-col"><article><h3>Productos más vendidos</h3><p>Unidades históricas: ventas manuales surtidas y pedidos de tienda enviados o entregados.</p><div id="top-chart"></div></article><article><h3>Comparación logística</h3><p>Existencias disponibles por estrategia y cantidad de productos en nivel crítico.</p><div id="strategy-chart"></div></article></div>
        <div class="two-col"><article><h3>Inventario crítico</h3><p>Stock disponible menor o igual al mínimo configurado.</p><div id="critical"></div></article><article><h3>Rotación lenta</h3><p>Productos con stock y 0–2 unidades vendidas en los últimos 30 días.</p><div id="slow"></div></article></div>
    </section>
    <section id="catalog" class="panel" hidden>
        <div class="section-heading"><h2>Catálogo e inventario</h2>@if(auth()->user()->role === 'admin')<button id="new-product" class="primary">+ Producto</button>@endif</div>
        <form id="product-filter" class="filters"><label>Buscar<input name="q" maxlength="120" placeholder="Nombre o descripción"></label><label>Estrategia<select name="estrategia"><option value="">Todas</option><option>PUSH</option><option>PULL</option></select></label><label>Estado<select name="critico"><option value="">Todos</option><option value="1">Stock crítico</option></select></label><label>Archivo<select name="archivados"><option value="0">Vigentes</option><option value="1">Archivados</option></select></label><button>Aplicar filtros</button></form>
        <div class="table-wrap"><table><thead><tr><th>Producto / proveedor</th><th>Existencias</th><th>Mínimo / objetivo</th><th>Costo / precio</th><th>Estrategia</th><th>Acciones</th></tr></thead><tbody id="product-rows"></tbody></table></div><div id="product-pages" class="pagination"></div>
    </section>
    <section id="suppliers" class="panel" hidden>
        <div class="section-heading"><h2>Proveedores</h2>@if(auth()->user()->role === 'admin')<button id="new-supplier" class="primary">+ Proveedor</button>@endif</div>
        <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Contacto</th><th>Correo</th><th>Teléfono</th><th>Acciones</th></tr></thead><tbody id="supplier-rows"></tbody></table></div>
    </section>
    <section id="orders" class="panel" hidden>
        <div class="section-heading"><h2>Pedidos SCM</h2><button id="new-order" class="primary">Generar pedido</button></div>
        <p>Los pedidos SCM son de un producto. Los pedidos del carrito se gestionan en el panel de la tienda. Surtir una reposición agrega stock; surtir una venta lo descuenta.</p>
        <form id="order-filter" class="filters"><label>Estado<select name="estado"><option value="">Todos</option><option value="pendiente">Pendiente</option><option value="surtido">Surtido</option></select></label><label>Tipo<select name="tipo"><option value="">Todos</option><option value="reposicion">Reposición</option><option value="venta">Venta</option></select></label><button>Aplicar filtros</button></form>
        <div class="table-wrap"><table><thead><tr><th>Pedido / producto</th><th>Cantidad</th><th>Tipo / origen</th><th>Estado</th><th>Registro / recepción</th><th>Acciones</th></tr></thead><tbody id="order-rows"></tbody></table></div><div id="order-pages" class="pagination"></div>
    </section>
    <section id="maturity" class="panel" hidden>
        <article><h2>Nivel de madurez SCM</h2><p id="level-indicator" class="level"></p><p id="level-description"></p><div class="two-col"><div><h3>Evidencias de la operación</h3><p>El administrador confirma evidencias reales. Inicial no exige mínimos; En desarrollo exige al menos 3; Optimizado exige las 6.</p><form id="maturity-form"><label>Nivel<select name="nivel_scm"><option>Inicial</option><option>En desarrollo</option><option>Optimizado</option></select></label>
            @foreach(['catalogo'=>'Catálogo con mínimos, costos y ubicación revisados','proveedores'=>'Proveedores y contactos validados','inventario'=>'Entradas, salidas y saldos reconciliados','estrategias'=>'Pruebas Push y Pull completadas','pedidos'=>'Pedidos recibidos sin duplicar existencias','reportes'=>'Reportes revisados y acciones documentadas'] as $key => $label)
            <label class="check"><input type="checkbox" name="{{ $key }}">{{ $label }}</label>
            @endforeach
            @if(auth()->user()->role === 'admin')<button class="primary">Guardar nivel y evidencias</button>@else<p>Solo el administrador puede cambiar este nivel.</p>@endif
        </form></div><div><h3>Cómo funciona cada estrategia</h3><p><strong>Push:</strong> cuando stock ≤ mínimo, genera una reposición hasta el objetivo, descontando cantidades ya pedidas. La recepción física se confirma en Pedidos SCM.</p><p><strong>Pull:</strong> no genera reposiciones por el mínimo. El equipo solicita un pedido manual cuando existe demanda.</p><p>Los pedidos pendientes se conservan cuando cambia la estrategia. El sistema no envía solicitudes a proveedores externos.</p></div></div></article>
    </section>
</main>
@if(auth()->user()->role === 'admin')
<dialog id="product-dialog"><div class="dialog-heading"><h2 id="product-title">Producto</h2><button data-close="product-dialog" type="button" aria-label="Cerrar">×</button></div><form id="product-form">
    <input type="hidden" name="id"><label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label><label>Descripción<textarea name="descripcion" required minlength="3" maxlength="3000"></textarea></label>
    <div class="two-col"><label>Categoría<select name="categoria"><option>Hoodie</option><option>T-Shirt</option><option>Pants</option><option>Accessory</option></select></label><label>Proveedor<select name="proveedor_id"><option value="">Sin proveedor</option></select></label></div>
    <div class="three-col"><label>Stock inicial<input name="stock_actual" type="number" min="0" max="1000000" step="1" required></label><label>Stock mínimo<input name="stock_minimo" type="number" min="0" max="999999" step="1" required></label><label>Stock objetivo<input name="stock_objetivo" type="number" min="1" max="1000000" step="1" required></label></div><p class="hint">Después del alta, cambia las existencias registrando movimientos desde el historial.</p>
    <div class="two-col"><label>Costo unitario (MXN)<input name="costo_unitario" type="number" min="0" max="1000000" step="0.01" required></label><label>Precio de venta (MXN)<input name="precio_venta" type="number" min="0.01" max="1000000" step="0.01" required></label></div>
    <div class="two-col"><label>Estrategia<select name="estrategia_logistica"><option>PULL</option><option>PUSH</option></select></label><label>Ubicación<input name="ubicacion" required maxlength="120"></label></div>
    <label>URL de imagen<input name="imagen" type="url" required maxlength="2048"></label><label>Color<input name="color" maxlength="100"></label><label class="check"><input name="activo" type="checkbox">Visible en tienda</label><button class="primary">Guardar producto</button>
</form></dialog>
<dialog id="supplier-dialog"><div class="dialog-heading"><h2>Proveedor</h2><button data-close="supplier-dialog" type="button" aria-label="Cerrar">×</button></div><form id="supplier-form"><input name="id" type="hidden"><label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label><label>Persona de contacto<input name="contacto" required minlength="2" maxlength="120"></label><label>Correo<input name="correo" type="email" required maxlength="255"></label><label>Teléfono<input name="telefono" type="tel" required maxlength="25"></label><button class="primary">Guardar proveedor</button></form></dialog>
@endif
<dialog id="history-dialog"><div class="dialog-heading"><h2 id="history-title">Movimientos</h2><button data-close="history-dialog" type="button" aria-label="Cerrar">×</button></div><p id="history-stock"></p><form id="movement-form" class="filters"><input type="hidden" name="producto_id"><label>Tipo<select name="tipo"><option value="entrada">Entrada</option><option value="salida">Salida</option></select></label><label>Motivo<select name="motivo"><option value="ajuste">Ajuste</option><option value="venta">Venta</option><option value="reposición">Reposición recibida</option></select></label><label>Cantidad<input name="cantidad" type="number" min="1" max="1000000" step="1" required></label><label>Referencia<input name="referencia" maxlength="255" placeholder="Conteo físico / documento"></label><button class="primary">Registrar movimiento</button></form><p id="movement-hint" class="hint">Para recibir un pedido ya pendiente, usa Pedidos SCM. Una reposición directa sin pedido pendiente crea y surte un pedido manual.</p><div class="table-wrap"><table><thead><tr><th>Fecha / responsable</th><th>Tipo / motivo</th><th>Cantidad</th><th>Saldo anterior → final</th><th>Referencia</th></tr></thead><tbody id="history-rows"></tbody></table></div><div id="history-pages" class="pagination"></div></dialog>
<dialog id="order-dialog"><div class="dialog-heading"><h2>Generar pedido manual</h2><button data-close="order-dialog" type="button" aria-label="Cerrar">×</button></div><form id="order-form"><label>Buscar producto<input id="order-search" maxlength="120" placeholder="Escribe nombre y pulsa Buscar"></label><button id="order-search-button" type="button">Buscar</button><label>Producto<select name="producto_id" required></select></label><label>Tipo<select name="tipo"><option value="reposicion">Reposición</option><option value="venta">Venta</option></select></label><label>Cantidad<input name="cantidad" type="number" min="1" max="1000000" step="1" required></label><p>La reposición requiere proveedor. El stock cambia al marcar el pedido surtido.</p><button class="primary">Generar pedido</button></form></dialog>
<footer>HFSTUDIOS · CRM y SCM · Etapas 1 y 2</footer>
</body></html>
