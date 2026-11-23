@extends('gestion.layout', ['module' => 'crm'])

@section('module-navigation')
    <button type="button" data-tab="dashboard" aria-controls="dashboard" aria-current="page">Resumen</button>
    <button type="button" data-tab="clients" aria-controls="clients">Clientes</button>
    <button type="button" data-tab="activity" aria-controls="activity">Mi actividad</button>
    <button type="button" data-tab="contacts" aria-controls="contacts">Mensajes de contacto</button>
    @if(auth()->user()->hasPermission('users'))
        <button type="button" data-tab="users" aria-controls="users">Equipo y permisos</button>
    @endif
@endsection

@section('content')
    <main class="management-content">
        <div id="message" role="status" hidden></div>
        <section id="dashboard" class="tab"><div class="heading"><div><span class="eyebrow">VISTA GENERAL</span><h1>La relación, en números.</h1></div><button id="refresh-metrics">Actualizar</button></div>
            <div id="counters" class="cards"></div><div class="two-col"><article><h2>Etapas CRM</h2><div id="chart" role="img" aria-label="Clientes por etapa CRM"></div></article>
            <article><h2>Clientes en riesgo</h2><p class="muted">Clientes activos sin interacciones o con último contacto hace más de 30 días.</p><div id="risks"></div></article></div>
            <article><h2>Interacciones por cliente</h2><div id="interaction-counts"></div></article>
        </section>
        <section id="clients" class="tab" hidden><div class="heading"><div><span class="eyebrow">DIRECTORIO</span><h1>Clientes</h1></div><button id="new-client" class="primary">+ Nuevo cliente</button></div>
            <form id="filters" class="filters"><label>Buscar<input name="q" placeholder="Nombre, correo o empresa" maxlength="120"></label>
                <label>Estado<select name="estado"><option value="">Todos</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></label>
                <label>Etapa<select name="etapa"><option value="">Todas</option><option>Prospecto</option><option>Activo</option><option>Frecuente</option><option>Inactivo</option></select></label><button>Filtrar</button></form>
            <article><div class="scroll"><table><thead><tr><th>Cliente</th><th>Contacto</th><th>Empresa</th><th>Estado / etapa</th><th>Interacciones</th><th>Acciones</th></tr></thead><tbody id="client-list"></tbody></table></div><div id="client-pages" class="pagination"></div></article>
        </section>
        <section id="activity" class="tab" hidden><div class="heading"><div><span class="eyebrow">TU SEGUIMIENTO</span><h1>Mi actividad</h1></div></div><article><div id="my-activity" class="timeline"></div><div id="activity-pages" class="pagination"></div></article></section>
        <section id="contacts" class="tab" hidden><div class="heading"><div><span class="eyebrow">BANDEJA DE ENTRADA</span><h1>Mensajes de contacto</h1></div></div><article><div id="contact-list"></div><div id="contact-pages" class="pagination"></div></article></section>
        @if(auth()->user()->hasPermission('users'))
        <section id="users" class="tab" hidden><div class="heading"><div><span class="eyebrow">ADMINISTRACIÓN</span><h1>Equipo y permisos</h1></div></div><div class="two-col"><article><h2>Crear cuenta del equipo</h2><form id="user-form">
            <label>Nombre<input name="name" required minlength="2" maxlength="120"></label><label>Correo<input name="email" type="email" required></label>
            <label>Contraseña<input name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></label>
            <label>Rol<select name="role"><option value="ventas">Ventas: CRM y órdenes ERP</option><option value="logistica">Logística: SCM y entregas</option><option value="usuario">Operador: CRM y operación</option><option value="admin">Administrador: todos los módulos</option></select></label><button type="submit" class="primary">Crear usuario</button></form><a href="/gestion/operaciones#users">Cambiar roles desde ERP</a></article>
            <article><h2>Cuentas registradas</h2><div id="user-list"></div></article></div></section>
        @endif
        <section id="detail" class="tab" hidden><button id="back-clients">← Volver a clientes</button><div class="heading"><div><span class="eyebrow">HISTORIAL CENTRALIZADO</span><h1 id="detail-title"></h1></div></div><article id="detail-info"></article>
            <div class="two-col"><article><h2>Registrar interacción</h2><form id="interaction-form"><label>Tipo<select name="tipo"><option value="llamada">Llamada</option><option value="correo">Correo</option><option value="reunión">Reunión</option></select></label>
                <label>Fecha y hora<input name="fecha" type="datetime-local" required></label><label>Descripción<textarea name="descripcion" required minlength="3" maxlength="3000" rows="4"></textarea></label><p class="muted">El responsable se registra automáticamente con tu cuenta.</p><button class="primary">Guardar interacción</button></form></article>
            <article><h2>Evaluar relación</h2><form id="evaluation-form"><label>Puntuación<select name="puntuacion"><option value="5">5 · Excelente</option><option value="4">4 · Buena</option><option value="3">3 · Regular</option><option value="2">2 · Débil</option><option value="1">1 · Crítica</option></select></label>
                <label>Observaciones<textarea name="observaciones" rows="3" maxlength="2000"></textarea></label><button class="primary">Guardar evaluación</button></form><div id="evaluation-list"></div></article></div>
            <article><h2>Línea de tiempo</h2><div id="history" class="timeline"></div></article>
        </section>
    </main>
<dialog id="client-dialog"><form id="client-form"><div class="heading"><h2 id="client-form-title">Nuevo cliente</h2><button type="button" id="close-client">Cerrar</button></div>
    <label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label><label>Correo<input name="correo" type="email" required maxlength="255"></label>
    <label>Teléfono<input name="telefono" maxlength="25" placeholder="+52 449 123 4567"></label><label>Empresa<input name="empresa" maxlength="150"></label>
    <div class="two-col"><label>Estado<select name="estado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></label><label>Etapa CRM<select name="etapa_crm"><option>Prospecto</option><option>Activo</option><option>Frecuente</option><option>Inactivo</option></select></label></div>
    <p id="client-error" role="alert"></p><button class="primary">Guardar cliente</button>
</form></dialog>
@endsection
