# Requisitos del PDF y evidencia

Fuente: `Distribución por etapas_U3(1).pdf`, 15 páginas, y las dos capturas ERP. El alcance técnico detallado abarca Etapas 1–3. La página 15 enumera Carrito y Cierre/Defensa y deja vacíos los entregables adicionales.

| Requisito | Implementación | Evidencia |
|---|---|---|
| Clientes, CRUD, búsqueda y filtros; pp. 1–2 | `CrmController`, `ClienteRequest`, `/clientes`, `/crm` | `CrmTest`, recorrido UI |
| Interacciones, responsable, historial, evaluación; pp. 2–4 | `/interacciones`, historial del cliente y `/mi-actividad` | Pruebas y formularios UI |
| Etapas, métricas y riesgo; pp. 3–4 | `/metricas`, `/clientes/{id}/etapa` | Métricas reales, cambio de etapa |
| Login y acceso por rol; p. 4 | `/login`, `/auth/login`, sesión y permisos | Autenticación y CSRF real |
| Productos y proveedores; pp. 5–6 | `Product`/`Producto`, `Proveedor`, `/scm` | CRUD, duplicados y formularios |
| Inventario y trazabilidad; pp. 6–7 | `InventoryService`, movimientos por producto | Entrada, salida, alertas e historial |
| Push/Pull y pedidos; pp. 7–8 | Estrategia, `/pedidos`, reposición automática | Venta ERP dispara Push; Pull manual |
| Madurez y reportes SCM; pp. 8–9 | `/scm/estado`, `/scm/nivel`, reportes y CSV | `ScmTest`, madurez y recepción UI |
| Integración de datos existentes; p. 10 | FK a `clientes`, `products`, `users`; vínculo al movimiento SCM | Se conservan los mismos registros |
| Orden: cliente, fecha, estado, total y responsable; p. 11 | `Orden`, `ordenes_empresariales`, partidas | Precios del servidor y snapshots |
| Crear/consultar órdenes y cambiar estado; p. 11 | `/ordenes`, `/ordenes/{id}`, `/ordenes/{id}/estado` | Estados, filtros, paginación y permisos |
| Selección cliente/productos/cantidad/total; p. 11 y capturas 2–5 | `/erp#new`, asistente y alta CRM | Flujo UI con eventos y HTTP real |
| Crear → stock → movimiento → historial; p. 12 | Transacción `ErpOrders` y servicio SCM | Rollback y reintentos sin duplicados |
| Procesamiento, confirmación y éxito; p. 12 y capturas 6–7 | POST `/ordenes/procesar`, procesos guardados | Operación y persistencia verificadas |
| Recursos y procesos; p. 10 | `Recurso`, `ProcesoInterno`, `/erp/recursos` | Alta/edición y pasos por orden |
| EstadoERP y progreso; p. 12 | `EstadoERP`, GET/PUT `/erp/estado` | Evidencias reales y persistencia |
| KPIs, gráficas e indicadores; p. 13 y captura 9 | GET `/erp/metricas`, inicio ERP | Ventas, órdenes, unidades, clientes, stock |
| Roles administrador/ventas/logística; p. 14 y captura 10 | `Permissions`, middleware y `/usuarios/rol` | Menú dinámico y bloqueos 403 |
| Reportes empresariales; p. 10 y captura 9 | Cortes persistentes y CSV actual/histórico | Corte conserva cifras tras cancelar |
| Reposiciones a proveedores existentes; captura final | Pedidos SCM e indicador ERP | Integración sin repetir el módulo |
| Carrito y agregados; p. 15 | Tienda, cupón, checkout, publicaciones, reseñas, subasta y PDF | `StoreTest`, Alpine y HTTP real |
| Preparación para defensa; p. 15 | Guías, diagnóstico y escenario DEMO | Recorrido de presentación |

## Tablas nuevas

`ordenes_empresariales`, `orden_productos`, `orden_historial`, `procesos_internos`, `recursos_erp`, `reportes_erp`, `estado_erp`.

`movimientos_inventario` añade `orden_empresarial_id`. Clientes, usuarios, catálogo, inventario y pedidos SCM conservan sus tablas.

## Endpoints ERP

| Método | Ruta |
|---|---|
| GET | `/erp` |
| POST | `/auth/login` |
| GET / POST | `/ordenes` |
| GET | `/ordenes/{id}` |
| POST | `/ordenes/procesar` |
| PUT | `/ordenes/{id}/estado` |
| GET | `/erp/metricas` |
| GET / PUT | `/erp/estado` |
| GET / POST | `/erp/recursos` |
| PUT | `/erp/recursos/{id}` |
| GET | `/erp/responsables` |
| GET / POST | `/erp/reportes` |
| GET | `/erp/reportes/csv` |
| GET | `/erp/reportes/{id}/csv` |
| PUT / POST | `/usuarios/rol` |
| PUT | `/productos/{id}/restaurar` |
