# HFSTUDIOS · Entrega CRM + SCM del PDF U2

Documento aplicado: **Distribución por etapas_U2.pdf**. El PDF especifica CRM (Etapa 1) y SCM (Etapa 2). Esta entrega conserva el CRM anterior, añade SCM e integra ambos con la tienda del repositorio `henryknot6/HFSTUDIOS`, base `f380aefba6cccca39a594290b92144d5e7852421`. Fecha: 2 de octubre de 2026.

## 1. Empieza aquí según tu caso

| Tu situación | Qué hacer |
|---|---|
| Ya instalaste la entrega CRM anterior | En la guía, filtra **Solo cambios U2**. Crea o reemplaza esos archivos completos. Luego sigue la actualización de la sección 3. Los archivos que no cambian se conservan. |
| Tienes únicamente el repositorio original | En la guía, elige **Todos los archivos** y copia cada archivo en su ruta una sola vez. Después sigue la instalación nueva de la sección 2. |
| Prefieres instalar el proyecto completo | Descomprime **HFSTUDIOS-U2-CRM-SCM.zip** en una carpeta nueva. Ya contiene todos los archivos y recursos compilados. Sigue la sección 2. |

Todas las rutas parten de la carpeta que contiene `artisan`, `composer.json` y `package.json`. `app/Models/Proveedor.php`, por ejemplo, corresponde a `HFSTUDIOS/app/Models/Proveedor.php`. Abre esa carpeta en VS Code; crea las subcarpetas que falten; pega **solo el contenido del bloque** del archivo indicado, reemplaza su contenido completo y guarda. Cada bloque aparece una sola vez. El botón **Copiar archivo** copia el código sin títulos ni delimitadores Markdown.

No mezcles la carpeta `public` con la raíz: los controladores van en `app/Http/Controllers`, las vistas en `resources/views` y los archivos CSS/JS directos en `public/css` y `public/js`. Los archivos que no aparecen en el listado permanecen como estaban en el repositorio original.

## 2. Instalación nueva: todo el proyecto

Requisitos: PHP 8.2 o superior; extensiones `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, `xml`, `dom`, `curl`; Composer; Node.js 20.19+ o 22.12+; npm. La entrega se verificó con PHP 8.3, Node 24, Laravel 12.50.0, Vite 7.3.1 y SQLite. Las versiones quedan fijadas en los archivos lock incluidos.

En VS Code → Terminal → Nueva terminal, confirma que estás donde está `artisan`. Ejecuta cada línea en orden. Si una falla, corrige el error antes de continuar. El siguiente bloque funciona en PowerShell y en Bash:

```sh
composer install
npm ci
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm run build
php artisan hf:crear-usuario --role=admin
php artisan test
php artisan serve --host=127.0.0.1 --port=8000
```

El comando `hf:crear-usuario` solicita nombre, correo y contraseña de al menos 12 caracteres. No hay cuenta ni contraseña predeterminadas. Al escribir la contraseña no se muestra en terminal. Crea un operador con `php artisan hf:crear-usuario --role=usuario`, o desde CRM → Equipo y permisos con la cuenta admin.

Abre **http://127.0.0.1:8000/login**, entra con tu admin y pulsa **Inventario SCM** desde CRM. La pantalla nueva está en **http://127.0.0.1:8000/scm**. Tienda: `/`; CRM: `/crm`; SCM: `/scm`. Mantén abierta la terminal del servidor. Ctrl+C lo detiene. Para volver a trabajar, basta con ejecutar otra vez `php artisan serve --host=127.0.0.1 --port=8000`.

La base inicial incorpora las seis prendas de la tienda con 30 unidades de muestra cada una. Sus estrategias son Pull, el mínimo es 0 y el costo es 0 hasta que el administrador configure datos reales. No se inventan proveedores para esos productos. Al crearlos se registra el saldo inicial en el historial. El seeder conserva los precios y existencias ya modificados.

## 3. Actualizar la entrega CRM anterior sin perder datos

Haz una copia de la carpeta y de la base de datos antes de actualizar. Detén el servidor mientras reemplazas archivos y migras. Si partes de la entrega anterior, copia únicamente los archivos marcados **U2**, completos, desde esta guía. Conserva los demás.

Se agrega una migración nueva: `database/migrations/2026_10_02_000001_create_scm_tables.php`. Conserva las migraciones anteriores; no modifiques las que ya ejecutaste. La nueva migración añade columnas y tablas y registra el stock existente como saldo inicial. No cambia clientes, usuarios, pedidos previos ni cantidades disponibles.

Desde la raíz ejecuta:

```sh
composer install
npm ci
php artisan optimize:clear
php artisan migrate --seed
npm run build
php artisan test
php artisan serve --host=127.0.0.1 --port=8000
```

Conserva `.env`, la `APP_KEY` y tu archivo SQLite actual. **No ejecutes `migrate:fresh` ni vuelvas a generar APP_KEY si estás actualizando una instalación con datos.** No copies el ZIP encima de la base existente sin una copia previa. No necesitas volver a crear el admin. Un pedido de tienda creado antes de U2 conserva su historial comercial; su reserva ya está incluida en el saldo inicial. Los movimientos detallados nuevos comienzan desde la migración.

Los recursos `public/js/scm.js` y `public/css/scm.css` se sirven directamente. Después de editar esos archivos, recarga sin caché. Al cambiar vistas o clases Tailwind de la tienda, vuelve a ejecutar `npm run build`. No necesitas iniciar un segundo servidor Vite.

## 4. Configuración: dónde ponerla

En **HFSTUDIOS/.env**, verifica este subconjunto de valores. Conserva el resto del archivo y su clave:

```dotenv
APP_NAME=HFSTUDIOS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_LOCALE=es
DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Para instalación nueva con SQLite, deja `DB_DATABASE` sin configurar: se usa `database/database.sqlite`. Si ya tienes otra ruta SQLite en `.env`, conserva esa ruta. La migración se diseñó con el schema builder de Laravel; **las pruebas realizadas aquí fueron sobre SQLite**, no sobre MySQL.

No hay cambios de dependencias respecto a la entrega CRM anterior. El ZIP excluye `.env`, bases de datos, cuentas de pruebas, `vendor` y `node_modules`; se instalan con los comandos anteriores. Incluye ambos lock y `public/build`.

## 5. Qué implementa cada requisito del PDF

| Apartado | Implementación y comprobación |
|---|---|
| Etapa 1: clientes, interacciones, etapas, evaluaciones, métricas, login, roles y Mi actividad | Se conserva el CRM integrado en `/crm`, sus endpoints y sus pruebas. |
| 2.1: Producto y Proveedor | Catálogo compartido con tienda; nombre, descripción, categoría, stock, mínimo, proveedor y costo. Alta/edición admin, asociación, búsqueda y filtros. Proveedores con contacto, correo único normalizado y teléfono. |
| 2.1: POST/GET/PUT/DELETE productos; POST/GET proveedores | Rutas exactas del documento. DELETE archiva un producto y conserva su trazabilidad; exige stock cero y ningún pedido abierto. Se añaden edición y eliminación de proveedores sin uso. |
| 2.2: MovimientoInventario y trazabilidad | Entradas/salidas, cantidad, motivo, fecha de servidor, responsable, saldo anterior y final. Formulario e historial paginado por producto. |
| 2.2: stock bajo y alertas | Etiqueta CRÍTICO, lista del dashboard y filtro: stock ≤ mínimo. |
| 2.3.1–2.3.2: Push/Pull | Estrategia por producto, selector de edición, botón de cambio, endpoint dedicado y filtro por estrategia. Comparación de unidades, productos y críticos. |
| 2.3.3: pedidos automáticos y manuales | Push genera reposición pendiente hasta el objetivo; Pull requiere pedido manual. Botón Generar pedido, listado, filtros y recepción con estado surtido. |
| 2.4: nivel de madurez | Registro global Inicial / En desarrollo / Optimizado, descripción e indicador, checklist persistente de seis evidencias. Admin actualiza; operador consulta. |
| 2.5: simulación comercial y reportes | Más vendidos, rotación lenta, inventario crítico, barras simples y comparación Push/Pull con registros reales. Exportación CSV opcional implementada. |
| Inventario integrado a tienda | Reservar un pedido registra salida; cancelarlo registra entrada. Ajustar stock en el panel antiguo genera movimiento. Reposición física se confirma al surtir. |
| Exposición y defensa | Recorrido de aceptación, escenarios numéricos y reparto de explicación en la sección 10. Las evidencias y defensa oral las prepara el equipo. |

El PDF deja vacíos los entregables de Etapas 3, 4 y 5. Esta entrega cubre los requisitos técnicos descritos de Etapas 1 y 2. La tienda registra pedidos y comprobantes; no realiza cobros bancarios ni CFDI. No envía pedidos ni correos a proveedores externos. CSV cubre la exportación opcional SCM; no se añade un segundo exportador PDF SCM.

## 6. Estructura: un solo catálogo y un solo saldo

| Entidad del PDF | Implementación | Uso |
|---|---|---|
| Producto | `app/Models/Producto.php`, hereda `Product`; tabla `products` | Conserva los IDs y catálogo de la tienda. La API SCM presenta campos en español. |
| Proveedor | `app/Models/Proveedor.php`; tabla `proveedores` | Contactos y relación de productos. |
| Inventario | `app/Models/Inventario.php`; tabla `inventarios` | Una fila por producto: ubicación y unidad. Las existencias se consultan del producto asociado. |
| MovimientoInventario | `app/Models/MovimientoInventario.php`; tabla `movimientos_inventario` | Libro de movimientos y saldos; relaciones a pedido SCM o pedido de tienda. |
| Pedido | `app/Models/Pedido.php`; tabla `pedidos_scm` | Una solicitud logística de un producto, separada de pedidos de carrito `orders`. |
| Nivel SCM | `app/Models/ScmSetting.php`; tabla `scm_settings` | Registro global ID 1: nivel y evidencias. |
| Reglas de inventario | `app/Services/InventoryService.php` | Transacciones, movimientos, reposiciones, recepción e idempotencia. |
| Reportes | `app/Services/ScmReports.php` | Agregados de ventas, rotación, críticos y comparación. |
| Validación de producto | `app/Http/Requests/ScmProductRequest.php` | Datos, categorías, cantidades, importes y proveedor obligatorio para Push. |
| API SCM | `app/Http/Controllers/ScmController.php`, `routes/web.php` | Endpoints con sesión, CSRF y autorización. |
| Pantalla SCM | `resources/views/scm/index.blade.php`, `public/js/scm.js`, `public/css/scm.css` | Interfaz, solicitudes y estilos adaptables. |
| Integración tienda | `StoreController.php`, `Product.php`, `storefront.js`, vistas tienda/CRM | Misma existencia, historial y enlaces SCM. |
| Pruebas SCM | `tests/Feature/ScmTest.php` | Casos de reglas, permisos, stock, pedidos, reportes y persistencia. |

Mapeo: `nombre → name`, `descripcion → description`, `categoria → category`, `stock_actual → stock`, `costo_unitario → costo_unitario_cents / 100`. Los importes se guardan en centavos. No se crea otra tabla de productos ni otro stock que pueda desincronizarse. Las categorías compartidas son `Hoodie`, `T-Shirt`, `Pants`, `Accessory`.

## 7. Reglas exactas de la operación

**Movimientos.** Cantidad entera entre 1 y 1,000,000. Entrada aumenta; salida disminuye. Se rechaza stock negativo o superior a 1,000,000. Venta exige salida; reposición exige entrada; ajuste admite ambos. Fecha y responsable salen del servidor. El libro no permite editar ni borrar movimientos desde la API: corrige un error registrando un ajuste documentado. Cambiar el stock de un producto ya creado se hace mediante movimientos, no mediante la edición SCM del catálogo.

**Push.** Exige proveedor. Si stock ≤ mínimo, se calcula `cantidad necesaria = máximo(0, objetivo − stock − reposiciones pendientes)`. El objetivo debe ser mayor que el mínimo. Se crea un pedido automático pendiente; mientras exista, nuevas salidas pueden aumentar su cantidad sin duplicarlo. No se suma stock hasta recibirlo. El chequeo ocurre al crear/configurar un producto, cambiar estrategia o registrar un movimiento. No requiere cron.

Ejemplo: stock 5, mínimo 2, objetivo 10. Venta de 3 → stock 2; aparece pedido automático de 8. Otra venta de 1 → stock 1; el mismo pedido pasa a 9. Surtirlo → stock 10. Si ya hay una reposición manual pendiente de 4 y stock 2, Push pide solo otras 4.

**Pull.** Alcanzar el mínimo solo genera la alerta. El equipo decide cuándo generar un pedido manual. Al surtir una reposición se suma cantidad; al surtir una venta se descuenta. Crear un pedido SCM de venta no reserva stock: se verifica al surtir; si falta, permanece pendiente y la operación completa se revierte. El carrito de tienda sí reserva al registrar el pedido.

**Reposición directa.** `POST /inventario/movimiento` con entrada y motivo `reposición` funciona cuando hay proveedor y no existe reposición pendiente: crea y surte un pedido manual dentro de la misma transacción y enlaza el movimiento. Si ya hay una pendiente, el endpoint pide recibirla desde Pedidos SCM para evitar registrar dos veces la misma entrega. Esto mantiene Pull bajo pedido también en la recepción directa.

**Surtido e idempotencia.** Un pedido surtido no vuelve a pendiente. Repetir la confirmación no vuelve a mover stock. Los formularios de movimientos y pedidos manuales mandan `request_key` UUID; un reintento idéntico devuelve el registro existente. Cambiar sus datos con la misma clave devuelve 409. Se bloquea/valida el saldo dentro de transacciones; no se ha realizado una prueba de carga concurrente.

**Cancelaciones y cambio de estrategia.** Cancelar un pedido de tienda pendiente restaura una sola vez su stock. Una reposición ya pendiente se conserva si cancelas una venta o cambias Push a Pull: no se anula una solicitud registrada por inferencia. Puede recibirse por encima del objetivo (siempre dentro del límite), y el equipo debe revisar sus pedidos. El objetivo guía nuevas solicitudes; no es un techo físico de stock.

**Archivo.** El administrador puede archivar productos con stock 0, sin pedidos SCM pendientes ni pedidos de tienda Pendiente/Enviado. No aparecen en la tienda. Catálogo → Archivo → Archivados permite consultar su historial. Los proveedores asociados, incluso a productos archivados, no se eliminan para preservar referencias.

**Madurez.** Las seis evidencias las declara el administrador. La regla local elegida exige al menos tres para En desarrollo y las seis para Optimizado; Inicial admite cualquier avance parcial. El PDF pide niveles y checklist, pero no impone estos umbrales. No se asigna automáticamente una certificación al marcar casillas.

**Reportes.** Más vendidos: top 10 por unidades históricas registradas desde U2. Se incluyen salidas manuales de venta y ventas SCM surtidas; pedidos de tienda solo si están Enviado o Entregado. Se excluyen reservas pendientes y canceladas. Rotación lenta: productos vigentes con stock > 0 y 0–2 unidades vendidas durante los últimos 30 días. Crítico: stock ≤ mínimo. La comparación y valor a costo consideran productos vigentes; el historial de archivados sigue disponible. Los pedidos anteriores a U2 no se reconstruyen como ventas logísticas históricas: forman parte del saldo de incorporación.

## 8. Permisos

| Acción | Admin | Usuario del equipo | Cliente / invitado |
|---|---|---|---|
| Consultar SCM, proveedores, historial, reportes y CSV | Sí | Sí | No |
| Registrar movimientos, generar pedidos, confirmar surtido | Sí | Sí | No |
| Crear/editar/archivar producto y cambiar estrategia | Sí | No | No |
| Crear/editar/eliminar proveedor | Sí | No | No |
| Guardar nivel y checklist | Sí | No | No |
| CRM y operaciones de tienda | Se conservan los permisos de la entrega anterior | Se conservan | Registro público crea cliente, no equipo |

Ocultar un botón es solo presentación. El middleware valida el rol en el servidor para cada ruta. Invitado: 401 en JSON. Cuenta sin permiso: 403. Solicitud de escritura sin token CSRF: 419. Datos inválidos: 422. Conflicto de trazabilidad/idempotencia: 409.

## 9. Rutas y ejemplos de uso

Todas las rutas están en **routes/web.php** para compartir la sesión. No hace falta crear `routes/api.php` ni instalar un paquete de autenticación adicional. Las respuestas paginadas incluyen `data`, `current_page`, `last_page`, `total`: 15 productos/pedidos y 20 movimientos por página.

| Método | Ruta | Uso |
|---|---|---|
| GET | `/scm` | Pantalla protegida del módulo |
| GET/POST | `/productos` | Buscar/listar o crear producto |
| GET/PUT/DELETE | `/productos/{id}` | Detalle/edición/archivo lógico |
| PUT | `/productos/{id}/estrategia` | `estrategia_logistica`: PUSH o PULL |
| GET | `/productos?estrategia=PUSH&q=&critico=1&page=1` | Filtros; `archivados=1` consulta archivo |
| GET/POST | `/proveedores` | Lista sin paginar / alta |
| PUT/DELETE | `/proveedores/{id}` | Edición/eliminación si no está asociado |
| POST | `/inventario/movimiento` | Entrada/salida con motivo y cantidad |
| GET | `/productos/{id}/movimientos?page=1` | Libro por producto, incluidos archivados |
| GET/POST | `/pedidos` | Pedidos logísticos, filtros `estado`, `tipo`, `page` / alta manual |
| PUT | `/pedidos/{id}/estado` | Confirmar `surtido`; repetir no duplica |
| GET | `/scm/estado` | Nivel y checklist |
| PUT | `/scm/nivel` | Guardar nivel y checklist |
| GET | `/scm/reportes` | Métricas y colecciones del dashboard |
| GET | `/scm/reportes/csv` | Archivo CSV descargable |

Los siguientes ejemplos se pegan **en la consola del navegador (F12 → Console), estando en `/scm` con sesión admin**. No van en un archivo PHP. El helper `HF` incluye cookie, encabezado JSON y token CSRF. Ejecuta los ejemplos uno por uno. Cambia los datos de muestra antes de usarlos en una operación real.

Crear proveedor y producto Push; `var` permite volver a ejecutar la demostración cambiando el correo para no duplicarlo:

```javascript
var proveedorDemo = await HF.request('/proveedores', 'POST', {
  nombre: 'Textiles Demo', contacto: 'Ana Demo',
  correo: 'textiles-demo@example.com', telefono: '+52 449 123 4567'
});
var productoDemo = await HF.request('/productos', 'POST', {
  nombre: 'Hoodie Push Demo', descripcion: 'Producto para demostrar SCM',
  categoria: 'Hoodie', stock_actual: 5, stock_minimo: 2, stock_objetivo: 10,
  proveedor_id: proveedorDemo.id, costo_unitario: 200, precio_venta: 400,
  estrategia_logistica: 'PUSH', imagen: 'https://placehold.co/600x800',
  color: 'Negro', activo: true, ubicacion: 'Estante Demo'
});
```

Vender tres unidades y consultar el pedido automático; no hace falta pasar fecha ni responsable:

```javascript
await HF.request('/inventario/movimiento', 'POST', {
  producto_id: productoDemo.id, tipo: 'salida', cantidad: 3,
  motivo: 'venta', referencia: 'Demostración Push', request_key: crypto.randomUUID()
});
console.log(await HF.request('/productos/' + productoDemo.id));
console.log(await HF.request('/pedidos?estado=pendiente'));
```

Para recibir el pedido usa su ID logístico real en la lista (no el folio HF del carrito). Ejemplo de consola que encuentra el pedido del producto recién creado:

```javascript
var pendientesDemo = await HF.request('/pedidos?estado=pendiente');
var pedidoDemo = pendientesDemo.data.find(p => p.producto_id === productoDemo.id);
if (pedidoDemo) await HF.request('/pedidos/' + pedidoDemo.id + '/estado', 'PUT', {estado:'surtido'});
console.log(await HF.request('/productos/' + productoDemo.id + '/movimientos'));
```

Si ese pedido no está en la primera página, búscalo en la interfaz o consulta la página siguiente. Para cambiar a Pull usa `await HF.request('/productos/' + productoDemo.id + '/estrategia', 'PUT', {estrategia_logistica:'PULL'})`. Genera uno manual desde el botón de la pantalla; debe quedar pendiente hasta surtirlo.

## 10. Recorrido de aceptación y explicación

1. Entra como admin; abre SCM → Proveedores. Crea uno, recarga y edita su contacto. El correo repetido debe rechazarse.
2. Crea **Hoodie Push**, proveedor asignado, stock 5, mínimo 2, objetivo 10, costo 200, venta 400, ubicación A. Debe aparecer también en el catálogo de tienda.
3. Abre su historial. Debe existir entrada inicial 5, saldo 0 → 5. Registra salida venta 3. Debe mostrar 5 → 2 y etiqueta CRÍTICO.
4. Abre Pedidos SCM. Debe existir una reposición automática pendiente de 8, sin haber aumentado todavía el stock.
5. Registra otra salida de 1. El stock será 1 y el mismo pedido automático quedará en 9. Pulsa Actualizar; no debe aparecer un duplicado.
6. Marca ese pedido surtido: stock 10; entrada reposición 9 enlazada. Volver a solicitar surtido al mismo ID devuelve el estado sin duplicar stock.
7. Crea **Hoodie Pull**, proveedor asignado, stock 3, mínimo 2, objetivo 8. Vende 1: stock 2 y alerta; no debe generarse pedido automático.
8. Generar pedido → producto Pull → reposición 6. Debe permanecer stock 2 hasta surtir; entonces debe quedar stock 8. Demuestra también un pedido manual de venta.
9. Intenta salida mayor que stock o cantidad 0: error; stock y libro permanecen iguales. Intenta surtir una venta SCM sin existencias: el pedido continúa pendiente.
10. Cambia estrategia y prueba filtros Push, Pull, críticos, búsqueda, paginación y consulta de archivados. Un producto con stock no se archiva.
11. Comprueba tienda: una compra registra salida en su historial; cancelación de Pendiente registra entrada una vez. El carrito usa el mismo stock.
12. En Resumen, verifica más vendidos, críticos, rotación lenta y barras comparativas; descarga CSV. Una reserva pendiente de tienda no aparece como venta surtida.
13. En Madurez, confirma tres evidencias y guarda En desarrollo. Optimizado con menos de seis debe rechazarse. Confirma las seis solo después de revisar sus evidencias y vuelve a guardar.
14. Entra como `usuario`: puede operar movimientos y pedidos; no puede configurar catálogo, proveedor, estrategia ni nivel. Un cliente público no puede abrir SCM.
15. Recarga y reinicia el servidor: datos, pedidos, estrategias y nivel permanecen. Revisa también la tienda y CRM. En tu navegador comprueba la disposición a 390 px y 1280 px.

Para la mini exposición: una persona explica modelo y relación producto–proveedor; otra demuestra saldos y rollback; otra ejecuta Push/Pull con los números anteriores; otra demuestra roles y recepción idempotente; otra explica reportes y checklist. Cada alumno debe ejecutar su parte y mostrar entrada → validación → transacción → respuesta → cambio visible. Guarda capturas con los saldos, IDs y estados que realmente obtuvo el equipo.

## 11. Verificación y límites comprobados

- **30 pruebas Laravel aprobadas, 232 assertions**: CRM/tienda anteriores y 12 casos nuevos SCM con validaciones, permisos, stock, reposiciones, idempotencia, rollback, archivo, madurez e integración de reportes.
- **Vite build aprobado**; JavaScript SCM sin errores de sintaxis.
- **Interfaz DOM comprobada**: gráficas y escape de texto, filtros, clics de envío reales, alta/edición, historial, movimientos, proveedores, pedido manual/surtido y nivel. También se conservaron las comprobaciones DOM de tienda y CRM. Las solicitudes de estas pruebas de interfaz se simulan; la lógica del servidor se prueba aparte en Laravel.
- **HTTP real comprobado** con servidor PHP, cookies y CSRF: autenticación, rutas SCM, movimiento, repetición idempotente, historial, restauración del stock de prueba y logout.
- Se verificó que los bloques de código y el ZIP correspondan al contenido de los archivos y que no se incluyan credenciales ni base local.
- **No se completó inspección visual en Chrome**, porque el navegador no pudo iniciarse en este entorno. El CSS incorpora tablas desplazables y diseño móvil; revisa visualmente tu instalación. No se afirma haber probado carga concurrente, MySQL, despliegue ni pagos reales.

## 12. Errores: solución concreta

| Error | Qué revisar |
|---|---|
| PHP/Composer/npm no reconocido | Ejecuta `php -v`, `composer --version`, `node --version`, `npm --version`; instala y agrega su carpeta a PATH. |
| `could not find driver` | En el archivo que muestra `php --ini`, activa `pdo_sqlite` y `sqlite3`. |
| No existe proveedor o Push devuelve 422 | Crea proveedor primero; asígnalo al producto. Objetivo debe ser mayor que mínimo. |
| No existe tabla o columna SCM | Copia la nueva migración y ejecuta `php artisan migrate --seed`. No uses fresh. |
| Stock no cambia al generar pedido | Es correcto: el pedido queda pendiente; confirma recepción con Marcar surtido. |
| No se puede recibir reposición directa | Si ya existe un pedido pendiente, recíbelo desde Pedidos SCM; no registres otra recepción del mismo suministro. |
| 419 | Recarga y entra otra vez. Usa el mismo host/puerto; no mezcles localhost con 127.0.0.1. |
| 403 | La cuenta es cliente o la acción requiere admin; crea equipo por el comando o panel autorizado. |
| 409 al archivar | Requiere stock 0 y ningún pedido SCM o de tienda abierto. |
| 422 en movimiento | Revisa stock suficiente, cantidades enteras y combinación tipo/motivo. |
| 422 en nivel | En desarrollo requiere al menos tres evidencias; Optimizado las seis. |
| Cambios invisibles | `php artisan optimize:clear`, `npm run build`, recarga sin caché. |
| Vite manifest faltante | Ejecuta `npm ci` y `npm run build`. |
| Teléfono rechazado | Usa 7–25 caracteres entre dígitos, +, paréntesis, espacios, punto o guion. |
| Imagen no carga | Revisa la URL HTTP/HTTPS y acceso a internet; imágenes de tienda conservan proveedores externos. |

La instalación base y detalles CRM se conservan en `docs/INSTALACION.md`, incluidos contratos de CRM, creación de equipo y flujo del carrito. El código completo por ruta está en esta guía y dentro de `docs/CODIGO_POR_RUTA_U2.md` del ZIP.


## 13. Código completo por ruta

Cada archivo de implementación necesario para pasar del repositorio original a CRM+SCM aparece completo una sola vez. Las rutas son relativas a la raíz HFSTUDIOS. Si ya instalaste la entrega CRM anterior, copia solo las filas U2; las filas Base CRM no cambian. El ZIP incluye todo el proyecto y los recursos compilados; no requiere pegar código.

| Ruta | Desde repositorio original | Desde entrega CRM anterior | Grupo |
|---|---|---|---|
| `.env.example` | Reemplazar completo | Conservar anterior | Base CRM |
| `app/Console/Commands/CreateStaff.php` | Crear | Conservar anterior | Base CRM |
| `app/Http/Controllers/AuthController.php` | Crear | Conservar anterior | Base CRM |
| `app/Http/Controllers/CrmController.php` | Crear | Conservar anterior | Base CRM |
| `app/Http/Controllers/ScmController.php` | Crear | Crear | U2 |
| `app/Http/Controllers/StoreController.php` | Crear | Reemplazar completo | U2 |
| `app/Http/Middleware/RequireRole.php` | Crear | Conservar anterior | Base CRM |
| `app/Http/Requests/ClienteRequest.php` | Crear | Conservar anterior | Base CRM |
| `app/Http/Requests/InteraccionRequest.php` | Crear | Conservar anterior | Base CRM |
| `app/Http/Requests/ScmProductRequest.php` | Crear | Crear | U2 |
| `app/Models/Auction.php` | Crear | Conservar anterior | Base CRM |
| `app/Models/Cliente.php` | Crear | Conservar anterior | Base CRM |
| `app/Models/Evaluacion.php` | Crear | Conservar anterior | Base CRM |
| `app/Models/Interaccion.php` | Crear | Conservar anterior | Base CRM |
| `app/Models/Inventario.php` | Crear | Crear | U2 |
| `app/Models/MovimientoInventario.php` | Crear | Crear | U2 |
| `app/Models/Order.php` | Crear | Conservar anterior | Base CRM |
| `app/Models/OrderItem.php` | Crear | Conservar anterior | Base CRM |
| `app/Models/Pedido.php` | Crear | Crear | U2 |
| `app/Models/Product.php` | Crear | Reemplazar completo | U2 |
| `app/Models/Producto.php` | Crear | Crear | U2 |
| `app/Models/Proveedor.php` | Crear | Crear | U2 |
| `app/Models/Publication.php` | Crear | Conservar anterior | Base CRM |
| `app/Models/ScmSetting.php` | Crear | Crear | U2 |
| `app/Models/User.php` | Reemplazar completo | Conservar anterior | Base CRM |
| `app/Services/CrmMetrics.php` | Crear | Conservar anterior | Base CRM |
| `app/Services/InventoryService.php` | Crear | Crear | U2 |
| `app/Services/ScmReports.php` | Crear | Crear | U2 |
| `bootstrap/app.php` | Reemplazar completo | Conservar anterior | Base CRM |
| `database/migrations/2026_10_01_000001_create_crm_tables.php` | Crear | Conservar anterior | Base CRM |
| `database/migrations/2026_10_01_000002_create_store_tables.php` | Crear | Conservar anterior | Base CRM |
| `database/migrations/2026_10_02_000001_create_scm_tables.php` | Crear | Crear | U2 |
| `database/seeders/DatabaseSeeder.php` | Reemplazar completo | Conservar anterior | Base CRM |
| `database/seeders/products.json` | Crear | Conservar anterior | Base CRM |
| `lang/es/validation.php` | Crear | Reemplazar completo | U2 |
| `package-lock.json` | Reemplazar completo | Conservar anterior | Base CRM |
| `package.json` | Reemplazar completo | Conservar anterior | Base CRM |
| `public/css/crm.css` | Crear | Conservar anterior | Base CRM |
| `public/css/scm.css` | Crear | Crear | U2 |
| `public/js/crm.js` | Crear | Conservar anterior | Base CRM |
| `public/js/http.js` | Crear | Conservar anterior | Base CRM |
| `public/js/scm.js` | Crear | Crear | U2 |
| `public/js/storefront.js` | Crear | Reemplazar completo | U2 |
| `resources/js/app.js` | Reemplazar completo | Conservar anterior | Base CRM |
| `resources/views/crm/index.blade.php` | Crear | Reemplazar completo | U2 |
| `resources/views/scm/index.blade.php` | Crear | Crear | U2 |
| `resources/views/welcome.blade.php` | Reemplazar completo | Reemplazar completo | U2 |
| `routes/web.php` | Reemplazar completo | Reemplazar completo | U2 |
| `tests/Feature/CrmTest.php` | Crear | Conservar anterior | Base CRM |
| `tests/Feature/ScmTest.php` | Crear | Crear | U2 |
| `tests/Feature/StoreTest.php` | Crear | Conservar anterior | Base CRM |

### 1. .env.example

**Base CRM.** Desde original: Reemplazar completo. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/.env.example`.

```dotenv
APP_NAME=HFSTUDIOS
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

# PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

### 2. app/Console/Commands/CreateStaff.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Console/Commands/CreateStaff.php`.

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateStaff extends Command
{
    protected $signature = 'hf:crear-usuario {--role=admin} {--name=} {--email=}';

    protected $description = 'Crea una cuenta del equipo; solicita la contraseña sin mostrarla.';

    public function handle(): int
    {
        $role = $this->option('role');
        $name = $this->option('name') ?: $this->ask('Nombre');
        $email = strtolower(trim($this->option('email') ?: $this->ask('Correo')));
        $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        $validator = Validator::make(compact('role', 'name', 'email', 'password'), ['role' => 'required|in:admin,usuario', 'name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        $user = new User(compact('name', 'email', 'password'));
        $user->role = $role;
        $user->save();
        $this->info('Cuenta creada.');

        return self::SUCCESS;
    }
}
```

### 3. app/Http/Controllers/AuthController.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Http/Controllers/AuthController.php`.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request)
    {
        return ['user' => $request->user(), 'csrf_token' => csrf_token()];
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string|max:128']);
        $credentials['email'] = strtolower(trim($credentials['email']));
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }
        $request->session()->regenerate();

        return $this->session($request);
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->email))]);
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128']);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            Cliente::firstOrCreate(['correo' => $data['email']], ['nombre' => $data['name']]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($this->session($request), 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->session($request);
    }
}
```

### 4. app/Http/Controllers/CrmController.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Http/Controllers/CrmController.php`.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Http\Requests\InteraccionRequest;
use App\Models\Cliente;
use App\Models\Evaluacion;
use App\Models\Interaccion;
use App\Models\User;
use App\Services\CrmMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:120', 'estado' => 'nullable|in:activo,inactivo', 'etapa' => 'nullable|in:Prospecto,Activo,Frecuente,Inactivo', 'page' => 'nullable|integer|min:1']);

        return Cliente::withCount('interacciones')->withMax('interacciones', 'fecha')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('nombre', 'like', "%$s%")->orWhere('correo', 'like', "%$s%")->orWhere('empresa', 'like', "%$s%")))
            ->when($filters['estado'] ?? null, fn ($q, $s) => $q->where('estado', $s))
            ->when($filters['etapa'] ?? null, fn ($q, $s) => $q->where('etapa_crm', $s))->latest('id')->paginate(15);
    }

    public function store(ClienteRequest $request)
    {
        return response()->json(Cliente::create($request->validated()), 201);
    }

    public function show(Cliente $cliente)
    {
        return $cliente->loadCount('interacciones')->load(['evaluaciones.usuario:id,name']);
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return $cliente->fresh();
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->noContent();
    }

    public function etapa(Request $request, Cliente $cliente)
    {
        $cliente->update($request->validate(['etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo']));

        return $cliente->fresh();
    }

    public function interaction(InteraccionRequest $request)
    {
        return response()->json(Interaccion::create([...$request->validated(), 'usuario_id' => $request->user()->id])->load('usuario:id,name'), 201);
    }

    public function history(Cliente $cliente)
    {
        return $cliente->interacciones()->with('usuario:id,name')->orderByDesc('fecha')->orderByDesc('id')->get();
    }

    public function activity(Request $request)
    {
        return Interaccion::with(['cliente:id,nombre', 'usuario:id,name'])->where('usuario_id', $request->user()->id)->orderByDesc('fecha')->paginate(15);
    }

    public function metrics(CrmMetrics $metrics)
    {
        return $metrics->summary();
    }

    public function evaluate(Request $request, Cliente $cliente)
    {
        $data = $request->validate(['puntuacion' => 'required|integer|between:1,5', 'observaciones' => 'nullable|string|max:2000']);

        return response()->json(Evaluacion::create([...$data, 'cliente_id' => $cliente->id, 'usuario_id' => $request->user()->id]), 201);
    }

    public function users()
    {
        return User::select('id', 'name', 'email', 'role')->orderBy('name')->get();
    }

    public function createUser(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128', 'role' => 'required|in:admin,usuario']);
        $user = new User($data);
        $user->role = $data['role'];
        $user->save();

        return response()->json($user, 201);
    }

    public function contacts()
    {
        return DB::table('contact_messages')->orderByDesc('id')->paginate(15);
    }

    public function handleContact(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $contact = DB::table('contact_messages')->where('id', $id)->lockForUpdate()->first();
            abort_unless($contact, 404);
            abort_if($contact->handled, 409, 'El mensaje ya fue registrado en el CRM.');
            $client = Cliente::firstOrCreate(['correo' => $contact->email], ['nombre' => $contact->name]);
            Interaccion::create(['cliente_id' => $client->id, 'usuario_id' => $request->user()->id, 'tipo' => 'correo', 'descripcion' => $contact->message, 'fecha' => $contact->created_at]);
            DB::table('contact_messages')->where('id', $id)->update(['handled' => true, 'updated_at' => now()]);

            return response()->json($client);
        });
    }
}
```

### 5. app/Http/Controllers/ScmController.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Http/Controllers/ScmController.php`.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScmProductRequest;
use App\Models\Pedido;
use App\Models\Product;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\ScmSetting;
use App\Services\InventoryService;
use App\Services\ScmReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScmController extends Controller
{
    public function products(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:120', 'estrategia' => 'nullable|in:PUSH,PULL', 'critico' => 'nullable|boolean', 'archivados' => 'nullable|boolean', 'page' => 'nullable|integer|min:1']);
        $query = Producto::with(['proveedor', 'inventario']);
        if ($request->boolean('archivados')) {
            $query->onlyTrashed();
        }
        $result = $query->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('description', 'like', "%$s%")))
            ->when($f['estrategia'] ?? null, fn ($q, $s) => $q->where('estrategia_logistica', $s))
            ->when($request->boolean('critico'), fn ($q) => $q->whereColumn('stock', '<=', 'stock_minimo'))->orderBy('id')->paginate(15);
        $result->through(fn ($p) => $p->scmPresentation());

        return $result;
    }

    public function product(Producto $producto)
    {
        return $producto->scmPresentation();
    }

    public function saveProduct(ScmProductRequest $request, InventoryService $inventory, ?Producto $producto = null)
    {
        return DB::transaction(function () use ($request, $inventory, $producto) {
            $d = $request->validated();
            $creating = ! $producto;
            if ($producto) {
                $producto = Producto::lockForUpdate()->findOrFail($producto->id);
            } else {
                $producto = new Producto;
            }
            $producto->fill(['name' => $d['nombre'], 'description' => $d['descripcion'], 'category' => $d['categoria'],
                'color' => $d['color'] ?? '', 'image' => $d['imagen'], 'active' => $d['activo'],
                'price_cents' => (int) round($d['precio_venta'] * 100), 'costo_unitario_cents' => (int) round($d['costo_unitario'] * 100),
                'stock_minimo' => $d['stock_minimo'], 'stock_objetivo' => $d['stock_objetivo'],
                'proveedor_id' => $d['proveedor_id'] ?? null, 'estrategia_logistica' => $d['estrategia_logistica']]);
            if ($creating) {
                $producto->stock = $d['stock_actual'];
            }
            $producto->save();
            $producto->inventario()->update(['ubicacion' => $d['ubicacion']]);
            $inventory->checkPush($producto->id, $request->user()->id);

            return response()->json($producto->fresh()->scmPresentation(), $creating ? 201 : 200);
        }, 3);
    }

    public function deleteProduct(Producto $producto)
    {
        return DB::transaction(function () use ($producto) {
            $product = Product::lockForUpdate()->findOrFail($producto->id);
            abort_if($product->stock > 0, 409, 'Agota o ajusta el stock antes de archivar.');
            abort_if(Pedido::where('producto_id', $product->id)->where('estado', 'pendiente')->exists(), 409, 'El producto tiene pedidos SCM pendientes.');
            abort_if(DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->where('product_id', $product->id)->whereIn('status', ['Pendiente', 'Enviado'])->exists(), 409, 'El producto tiene pedidos de tienda abiertos.');
            $product->delete();

            return response()->noContent();
        }, 3);
    }

    public function suppliers()
    {
        return Proveedor::orderBy('nombre')->get();
    }

    public function saveSupplier(Request $request, ?Proveedor $proveedor = null)
    {
        $request->merge(['correo' => strtolower(trim((string) $request->correo))]);
        $d = $request->validate(['nombre' => 'required|string|min:2|max:120', 'contacto' => 'required|string|min:2|max:120',
            'correo' => ['required', 'email', 'max:255', Rule::unique('proveedores', 'correo')->ignore($proveedor)],
            'telefono' => ['required', 'string', 'max:25', 'regex:/^[0-9+() .-]{7,25}$/']]);
        $creating = ! $proveedor;
        $proveedor ??= new Proveedor;
        $proveedor->fill($d)->save();

        return response()->json($proveedor, $creating ? 201 : 200);
    }

    public function deleteSupplier(Proveedor $proveedor)
    {
        abort_if(Product::withTrashed()->where('proveedor_id', $proveedor->id)->exists(), 409, 'El proveedor está asociado a productos; conserva su trazabilidad.');
        $proveedor->delete();

        return response()->noContent();
    }

    public function movement(Request $request, InventoryService $inventory)
    {
        $d = $request->validate(['producto_id' => 'required|integer|exists:products,id', 'tipo' => 'required|in:entrada,salida', 'cantidad' => 'required|integer|between:1,1000000',
            'motivo' => 'required|in:venta,ajuste,reposición', 'referencia' => 'nullable|string|max:255', 'request_key' => 'nullable|uuid']);
        if (($d['motivo'] === 'venta' && $d['tipo'] !== 'salida') || ($d['motivo'] === 'reposición' && $d['tipo'] !== 'entrada')) {
            abort(422, 'Venta requiere salida y reposición requiere entrada.');
        }

        $extra = array_intersect_key($d, array_flip(['referencia', 'request_key']));
        $movement = $d['motivo'] === 'reposición' ? $inventory->receiveDirect($d, $request->user()->id) : $inventory->move($d['producto_id'], $d['tipo'], $d['cantidad'], $d['motivo'], $request->user()->id, $extra);

        return response()->json($movement->load('usuario:id,name'), 201);
    }

    public function history(Producto $producto)
    {
        return $producto->movimientos()->with('usuario:id,name')->orderByDesc('id')->paginate(20);
    }

    public function strategy(Request $request, Producto $producto, InventoryService $inventory)
    {
        $d = $request->validate(['estrategia_logistica' => 'required|in:PUSH,PULL']);

        return DB::transaction(function () use ($d, $producto, $inventory, $request) {
            $p = Product::lockForUpdate()->findOrFail($producto->id);
            abort_if($d['estrategia_logistica'] === 'PUSH' && ! $p->proveedor_id, 422, 'Asocia un proveedor antes de activar Push.');
            $p->update($d);
            $inventory->checkPush($p->id, $request->user()->id);

            return $p->fresh()->scmPresentation();
        }, 3);
    }

    public function orders(Request $request)
    {
        $f = $request->validate(['estado' => 'nullable|in:pendiente,surtido', 'tipo' => 'nullable|in:reposicion,venta', 'page' => 'nullable|integer|min:1']);

        return Pedido::with(['producto:id,name,stock,deleted_at', 'usuario:id,name'])
            ->when($f['estado'] ?? null, fn ($q, $s) => $q->where('estado', $s))->when($f['tipo'] ?? null, fn ($q, $s) => $q->where('tipo', $s))->latest('id')->paginate(15);
    }

    public function createOrder(Request $request, InventoryService $inventory)
    {
        $d = $request->validate(['producto_id' => 'required|integer|exists:products,id', 'cantidad' => 'required|integer|between:1,1000000', 'tipo' => 'required|in:reposicion,venta', 'request_key' => 'nullable|uuid']);

        return response()->json($inventory->createOrder($d, $request->user()->id)->load('producto:id,name,stock'), 201);
    }

    public function orderStatus(Request $request, Pedido $pedido, InventoryService $inventory)
    {
        $d = $request->validate(['estado' => 'required|in:pendiente,surtido']);
        abort_if($d['estado'] === 'pendiente' && $pedido->estado === 'surtido', 422, 'Un pedido surtido no puede volver a pendiente.');

        return $d['estado'] === 'surtido' ? $inventory->fulfill($pedido->id, $request->user()->id) : $pedido;
    }

    public function reports(ScmReports $reports)
    {
        return $reports->summary();
    }

    public function export(ScmReports $reports)
    {
        $data = $reports->summary();

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reporte', 'Producto', 'Cantidad'], ',', '"', '');
            foreach ($data['productos_mas_vendidos'] as $p) {
                fputcsv($out, ['Más vendidos', $this->csvValue($p['nombre']), $p['unidades']], ',', '"', '');
            }
            foreach ($data['rotacion_lenta'] as $p) {
                fputcsv($out, ['Rotación lenta (30 días)', $this->csvValue($p['nombre']), $p['unidades_30_dias']], ',', '"', '');
            }
            foreach ($data['inventario_critico'] as $p) {
                fputcsv($out, ['Stock crítico', $this->csvValue($p['nombre']), $p['stock_actual']], ',', '"', '');
            }fclose($out);
        }, 'HFSTUDIOS-reportes-SCM.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue(string $value): string
    {
        return preg_match('/^[=+@\-\t\r]/u', $value) ? "'".$value : $value;
    }

    public function state()
    {
        return ScmSetting::findOrFail(1);
    }

    public function maturity(Request $request)
    {
        $d = $request->validate(['nivel_scm' => 'required|in:Inicial,En desarrollo,Optimizado', 'checklist' => 'required|array:catalogo,proveedores,inventario,estrategias,pedidos,reportes',
            'checklist.catalogo' => 'required|boolean', 'checklist.proveedores' => 'required|boolean', 'checklist.inventario' => 'required|boolean',
            'checklist.estrategias' => 'required|boolean', 'checklist.pedidos' => 'required|boolean', 'checklist.reportes' => 'required|boolean']);
        $checked = count(array_filter($d['checklist']));
        abort_if(($d['nivel_scm'] === 'Optimizado' && $checked !== 6) || ($d['nivel_scm'] === 'En desarrollo' && $checked < 3), 422, 'Optimizado requiere 6 evidencias marcadas; En desarrollo requiere al menos 3.');
        $setting = ScmSetting::findOrFail(1);
        $setting->update($d);

        return $setting;
    }
}
```

### 6. app/Http/Controllers/StoreController.php

**U2.** Desde original: Crear. Desde CRM anterior: Reemplazar completo. Destino: `HFSTUDIOS/app/Http/Controllers/StoreController.php`.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Cliente;
use App\Models\Order;
use App\Models\Product;
use App\Models\Publication;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreController extends Controller
{
    public function products(Request $request)
    {
        return Product::when($request->user()?->role !== 'admin', fn ($q) => $q->where('active', true))->orderBy('id')->get()->map->presentation();
    }

    public function saveProduct(Request $request, InventoryService $inventory, ?Product $product = null)
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'color' => 'nullable|string|max:100', 'category' => 'required|in:Hoodie,T-Shirt,Pants,Accessory',
            'price' => 'required|numeric|min:0.01|max:1000000|decimal:0,2', 'stock' => 'required|integer|min:0|max:1000000',
            'image' => 'required|url:http,https|max:2048', 'description' => 'required|string|min:3|max:3000', 'active' => 'required|boolean',
            'badge' => 'nullable|string|max:40', 'originalPrice' => 'nullable|numeric|gte:price|max:1000000|decimal:0,2']);
        $data['price_cents'] = (int) round($data['price'] * 100);
        $data['original_price_cents'] = isset($data['originalPrice']) ? (int) round($data['originalPrice'] * 100) : null;
        unset($data['price'],$data['originalPrice']);
        $data['color'] = $data['color'] ?? '';

        return DB::transaction(function () use ($data, $product, $request, $inventory) {
            $desired = (int) $data['stock'];
            if ($product) {
                $product = Product::lockForUpdate()->findOrFail($product->id);
                unset($data['stock']);
                $product->fill($data)->save();
                $inventory->setStock($product->id, $desired, $request->user()->id);
            } else {
                $product = Product::create($data);
            }
            $inventory->checkPush($product->id, $request->user()->id);

            return $product->fresh()->presentation();
        }, 3);
    }

    public function toggleProduct(Product $product)
    {
        $product->update(['active' => ! $product->active]);

        return $product->presentation();
    }

    public function orders(Request $request)
    {
        return Order::with(['items', 'user'])->when($request->user()->role !== 'admin', fn ($q) => $q->where('user_id', $request->user()->id))->latest('id')->get()->map->presentation();
    }

    public function checkout(Request $request, InventoryService $inventory)
    {
        $data = $request->validate(['items' => 'required|array|min:1|max:50', 'items.*.id' => 'required|integer|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|between:1,99', 'address' => 'required|string|min:5|max:255',
            'city' => 'required|string|min:2|max:120', 'zip' => 'required|digits:5', 'checkout_key' => 'required|uuid',
            'discount_code' => 'nullable|in:HF10']);

        return DB::transaction(function () use ($data, $request, $inventory) {
            $existing = Order::where('user_id', $request->user()->id)->where('checkout_key', $data['checkout_key'])->first();
            if ($existing) {
                return $existing->presentation();
            }
            $subtotal = 0;
            $items = [];
            // Siempre se calculan precio y existencia con datos del servidor.
            foreach (collect($data['items'])->sortBy('id') as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                if (! $product->active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => "Sin existencias suficientes: {$product->name}."]);
                }
                $subtotal += $product->price_cents * $item['quantity'];
                $items[] = ['product_id' => $product->id, 'name' => $product->name, 'price_cents' => $product->price_cents, 'quantity' => $item['quantity']];
            }
            $client = Cliente::firstOrCreate(['correo' => $request->user()->email], ['nombre' => $request->user()->name]);
            $discount = ($data['discount_code'] ?? '') === 'HF10' ? (int) round($subtotal * 0.10) : 0;
            $order = Order::create(['folio' => 'HF-'.strtoupper((string) Str::ulid()), 'user_id' => $request->user()->id, 'cliente_id' => $client->id,
                'checkout_key' => $data['checkout_key'], 'address' => $data['address'], 'city' => $data['city'], 'zip' => $data['zip'],
                'subtotal_cents' => $subtotal, 'discount_cents' => $discount, 'total_cents' => $subtotal - $discount]);
            $order->items()->createMany($items);
            foreach ($items as $item) {
                $inventory->move($item['product_id'], 'salida', $item['quantity'], 'venta', $request->user()->id,
                    ['order_id' => $order->id, 'referencia' => 'Reserva de tienda '.$order->folio]);
            }

            return response()->json($order->presentation(), 201);
        });
    }

    public function orderStatus(Request $request, Order $order, InventoryService $inventory)
    {
        $data = $request->validate(['status' => 'required|in:Enviado,Entregado,Cancelado']);

        return DB::transaction(function () use ($data, $order, $inventory, $request) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $allowed = ['Pendiente' => ['Enviado', 'Cancelado'], 'Enviado' => ['Entregado'], 'Entregado' => [], 'Cancelado' => []];
            abort_unless(in_array($data['status'], $allowed[$order->status] ?? [], true), 422, 'Transición de pedido no permitida.');
            if ($data['status'] === 'Cancelado') {
                foreach ($order->items()->orderBy('product_id')->get() as $item) {
                    $inventory->move($item->product_id, 'entrada', $item->quantity, 'ajuste', $request->user()->id,
                        ['order_id' => $order->id, 'referencia' => 'Cancelación de tienda '.$order->folio]);
                }
            }
            $order->update($data);

            return $order->presentation();
        });
    }

    public function stats()
    {
        return ['sales' => '$'.number_format(Order::where('status', '!=', 'Cancelado')->sum('total_cents') / 100, 2),
            'orders' => Order::whereIn('status', ['Pendiente', 'Enviado'])->count(), 'users' => Cliente::count()];
    }

    public function publications(Request $request)
    {
        return Publication::where('user_id', $request->user()->id)->latest('id')->get()->map->presentation();
    }

    public function savePublication(Request $request, ?Publication $publication = null)
    {
        if ($publication) {
            abort_unless($publication->user_id === $request->user()->id, 403);
        }
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'price' => 'required|numeric|min:0.01|max:1000000|decimal:0,2',
            'description' => 'required|string|min:3|max:3000', 'image' => 'required|url:http,https|max:2048']);
        $data['price_cents'] = (int) round($data['price'] * 100);
        unset($data['price']);
        $publication ??= new Publication(['user_id' => $request->user()->id]);
        $publication->fill($data)->save();

        return $publication->presentation();
    }

    public function deletePublication(Request $request, Publication $publication)
    {
        abort_unless($publication->user_id === $request->user()->id, 403);
        $publication->delete();

        return response()->noContent();
    }

    public function comments()
    {
        return DB::table('comments')->join('users', 'users.id', '=', 'comments.user_id')->select('comments.id', 'comments.text', 'users.name')->orderByDesc('comments.id')->limit(100)->get();
    }

    public function comment(Request $request)
    {
        $data = $request->validate(['text' => 'required|string|min:3|max:2000']);
        DB::table('comments')->insert([...$data, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return $this->comments();
    }

    public function auction()
    {
        $auction = Auction::latest('id')->first();
        if (! $auction) {
            return ['timerSeconds' => 0, 'product' => ['name' => 'Sin subasta abierta', 'image' => '', 'currentBid' => 0], 'offers' => []];
        }

        return ['id' => $auction->id, 'timerSeconds' => max(0, (int) now()->diffInSeconds($auction->ends_at, false)),
            'product' => ['name' => $auction->name, 'image' => $auction->image, 'currentBid' => $auction->current_bid_cents / 100],
            'offers' => DB::table('bids')->join('users', 'users.id', '=', 'bids.user_id')->where('auction_id', $auction->id)->orderByDesc('bids.id')->limit(50)->get(['users.name as user', 'amount_cents'])->map(fn ($b) => ['user' => $b->user, 'amount' => $b->amount_cents / 100])];
    }

    public function bid(Request $request, Auction $auction)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:1000000|decimal:0,2']);

        return DB::transaction(function () use ($data, $request, $auction) {
            $auction = Auction::lockForUpdate()->findOrFail($auction->id);
            $cents = (int) round($data['amount'] * 100);
            abort_if($auction->ends_at->isPast(), 422, 'La subasta terminó.');
            abort_if($cents <= $auction->current_bid_cents, 422, 'La oferta debe superar la actual.');
            // Comparación atómica para evitar ofertas concurrentes con importe inferior.
            $changed = Auction::whereKey($auction->id)->where('current_bid_cents', $auction->current_bid_cents)->update(['current_bid_cents' => $cents, 'updated_at' => now()]);
            abort_unless($changed, 409, 'La oferta actual cambió. Actualiza e inténtalo de nuevo.');
            DB::table('bids')->insert(['auction_id' => $auction->id, 'user_id' => $request->user()->id, 'amount_cents' => $cents, 'created_at' => now(), 'updated_at' => now()]);

            return $this->auction();
        });
    }

    public function contact(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255', 'message' => 'required|string|min:3|max:3000']);
        $data['email'] = strtolower(trim($data['email']));
        DB::table('contact_messages')->insert([...$data, 'created_at' => now(), 'updated_at' => now()]);

        return ['message' => 'Mensaje guardado para atención del equipo.'];
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        DB::table('subscriptions')->insertOrIgnore(['email' => strtolower(trim($data['email'])), 'created_at' => now(), 'updated_at' => now()]);

        return ['message' => 'Suscripción guardada.'];
    }
}
```

### 7. app/Http/Middleware/RequireRole.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Http/Middleware/RequireRole.php`.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        abort_unless($request->user() && in_array($request->user()->role, $roles, true), 403, 'No tienes permiso para esta operación.');

        return $next($request);
    }
}
```

### 8. app/Http/Requests/ClienteRequest.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Http/Requests/ClienteRequest.php`.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'usuario'], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['correo' => strtolower(trim((string) $this->correo)), 'nombre' => trim((string) $this->nombre)]);
    }

    public function rules(): array
    {
        return ['nombre' => 'required|string|min:2|max:120',
            'correo' => ['required', 'email', 'max:255', Rule::unique('clientes', 'correo')->ignore($this->route('cliente'))],
            'telefono' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+() .-]{7,25}$/'],
            'empresa' => 'nullable|string|max:150', 'estado' => 'required|in:activo,inactivo',
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo'];
    }
}
```

### 9. app/Http/Requests/InteraccionRequest.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Http/Requests/InteraccionRequest.php`.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InteraccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'usuario'], true);
    }

    public function rules(): array
    {
        return ['cliente_id' => 'required|integer|exists:clientes,id', 'tipo' => 'required|in:llamada,correo,reunión',
            'descripcion' => 'required|string|min:3|max:3000', 'fecha' => 'required|date|before_or_equal:now'];
    }
}
```

### 10. app/Http/Requests/ScmProductRequest.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Http/Requests/ScmProductRequest.php`.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScmProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['nombre' => 'required|string|min:2|max:120', 'descripcion' => 'required|string|min:3|max:3000',
            'categoria' => 'required|in:Hoodie,T-Shirt,Pants,Accessory', 'stock_actual' => $this->isMethod('POST') ? 'required|integer|between:0,1000000' : 'prohibited',
            'stock_minimo' => 'required|integer|between:0,999999', 'stock_objetivo' => 'required|integer|gt:stock_minimo|max:1000000',
            'proveedor_id' => ['nullable', Rule::requiredIf($this->estrategia_logistica === 'PUSH'), 'integer', 'exists:proveedores,id'],
            'costo_unitario' => 'required|numeric|between:0,1000000|decimal:0,2', 'precio_venta' => 'required|numeric|min:0.01|max:1000000|decimal:0,2',
            'estrategia_logistica' => 'required|in:PUSH,PULL', 'imagen' => 'required|url:http,https|max:2048',
            'color' => 'nullable|string|max:100', 'activo' => 'required|boolean', 'ubicacion' => 'required|string|max:120'];
    }
}
```

### 11. app/Models/Auction.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/Auction.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ends_at' => 'datetime'];
    }
}
```

### 12. app/Models/Cliente.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/Cliente.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = ['nombre', 'correo', 'telefono', 'empresa', 'estado', 'etapa_crm'];

    protected function casts(): array
    {
        return ['fecha_registro' => 'datetime'];
    }

    public function interacciones()
    {
        return $this->hasMany(Interaccion::class);
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class);
    }
}
```

### 13. app/Models/Evaluacion.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/Evaluacion.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';

    protected $fillable = ['cliente_id', 'usuario_id', 'puntuacion', 'observaciones'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
```

### 14. app/Models/Interaccion.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/Interaccion.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interaccion extends Model
{
    protected $table = 'interacciones';

    protected $fillable = ['cliente_id', 'usuario_id', 'tipo', 'descripcion', 'fecha'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
```

### 15. app/Models/Inventario.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Models/Inventario.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $fillable = ['producto_id', 'ubicacion', 'unidad'];

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id')->withTrashed();
    }
}
```

### 16. app/Models/MovimientoInventario.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Models/MovimientoInventario.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id')->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
```

### 17. app/Models/Order.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/Order.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function presentation(): array
    {
        return ['id' => $this->folio, 'database_id' => $this->id, 'user' => $this->user->name, 'date' => $this->created_at->format('d/m/Y'),
            'status' => $this->status, 'total' => $this->total_cents / 100, 'discount' => $this->discount_cents / 100,
            'items' => $this->items->map(fn ($i) => ['name' => $i->name, 'price' => $i->price_cents / 100, 'quantity' => $i->quantity])->all()];
    }
}
```

### 18. app/Models/OrderItem.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/OrderItem.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];
}
```

### 19. app/Models/Pedido.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Models/Pedido.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos_scm';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['automatico' => 'boolean', 'fecha_surtido' => 'datetime'];
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id')->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
```

### 20. app/Models/Product.php

**U2.** Desde original: Crear. Desde CRM anterior: Reemplazar completo. Destino: `HFSTUDIOS/app/Models/Product.php`.

```php
<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'stock' => 'integer', 'stock_minimo' => 'integer', 'stock_objetivo' => 'integer', 'inventory_version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::created(fn (Product $p) => app(InventoryService::class)->initialize($p, auth()->id()));
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function inventario()
    {
        return $this->hasOne(Inventario::class, 'producto_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function presentation(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'color' => $this->color, 'category' => $this->category,
            'price' => $this->price_cents / 100, 'originalPrice' => $this->original_price_cents ? $this->original_price_cents / 100 : null,
            'stock' => $this->stock, 'image' => $this->image, 'badge' => $this->badge, 'description' => $this->description, 'active' => $this->active,
            'inventory_version' => $this->inventory_version];
    }

    public function scmPresentation(): array
    {
        return ['id' => $this->id, 'nombre' => $this->name, 'descripcion' => $this->description, 'categoria' => $this->category,
            'stock_actual' => $this->stock, 'stock_minimo' => $this->stock_minimo, 'stock_objetivo' => $this->stock_objetivo,
            'proveedor_id' => $this->proveedor_id, 'proveedor' => $this->proveedor?->nombre, 'costo_unitario' => $this->costo_unitario_cents / 100,
            'precio_venta' => $this->price_cents / 100, 'imagen' => $this->image, 'color' => $this->color, 'activo' => $this->active,
            'estrategia_logistica' => $this->estrategia_logistica, 'stock_bajo' => $this->stock <= $this->stock_minimo,
            'ubicacion' => $this->inventario?->ubicacion, 'inventory_version' => $this->inventory_version, 'deleted_at' => $this->deleted_at];
    }
}
```

### 21. app/Models/Producto.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Models/Producto.php`.

```php
<?php

namespace App\Models;

// Comparte producto y stock con la tienda: no se crea un catálogo paralelo.
class Producto extends Product
{
    protected $table = 'products';

    public function getNombreAttribute()
    {
        return $this->name;
    }

    public function getDescripcionAttribute()
    {
        return $this->description;
    }

    public function getCategoriaAttribute()
    {
        return $this->category;
    }

    public function getStockActualAttribute()
    {
        return $this->stock;
    }

    public function getCostoUnitarioAttribute()
    {
        return $this->costo_unitario_cents / 100;
    }
}
```

### 22. app/Models/Proveedor.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Models/Proveedor.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'contacto', 'correo', 'telefono'];

    public function productos()
    {
        return $this->hasMany(Product::class, 'proveedor_id');
    }
}
```

### 23. app/Models/Publication.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/Publication.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $guarded = ['id'];

    public function presentation(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'price' => $this->price_cents / 100, 'description' => $this->description, 'image' => $this->image];
    }
}
```

### 24. app/Models/ScmSetting.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Models/ScmSetting.php`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScmSetting extends Model
{
    protected $table = 'scm_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['checklist' => 'array'];
    }
}
```

### 25. app/Models/User.php

**Base CRM.** Desde original: Reemplazar completo. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Models/User.php`.

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $attributes = ['role' => 'cliente'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### 26. app/Services/CrmMetrics.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/app/Services/CrmMetrics.php`.

```php
<?php

namespace App\Services;

use App\Models\Cliente;

class CrmMetrics
{
    public function summary(): array
    {
        $cutoff = now()->subDays(30);
        $clients = Cliente::withCount('interacciones')->withMax('interacciones', 'fecha')->get();
        $risk = $clients->filter(fn ($c) => $c->estado === 'activo' && (! $c->interacciones_max_fecha || $c->interacciones_max_fecha < $cutoff->toDateTimeString()));

        return ['total' => $clients->count(), 'activos' => $clients->where('estado', 'activo')->count(),
            'inactivos' => $clients->where('estado', 'inactivo')->count(), 'interacciones' => $clients->sum('interacciones_count'),
            'dias_sin_contacto' => 30, 'clientes_en_riesgo' => $risk->values(),
            'interacciones_por_cliente' => $clients->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'total' => $c->interacciones_count])->values(),
            'por_etapa' => collect(['Prospecto', 'Activo', 'Frecuente', 'Inactivo'])->map(fn ($s) => ['etapa' => $s, 'total' => $clients->where('etapa_crm', $s)->count()])->values()];
    }
}
```

### 27. app/Services/InventoryService.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Services/InventoryService.php`.

```php
<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function initialize(Product $product, ?int $userId = null): void
    {
        Inventario::firstOrCreate(['producto_id' => $product->id]);
        if ($product->stock > 0 && ! MovimientoInventario::where('producto_id', $product->id)->exists()) {
            MovimientoInventario::create(['producto_id' => $product->id, 'tipo' => 'entrada', 'cantidad' => $product->stock, 'motivo' => 'ajuste', 'fecha' => now(), 'stock_anterior' => 0, 'stock_resultante' => $product->stock, 'referencia' => 'Inventario inicial', 'usuario_id' => $userId]);
        }
    }

    public function move(int $productId, string $type, int $quantity, string $reason, ?int $userId = null, array $extra = []): MovimientoInventario
    {
        return DB::transaction(function () use ($productId, $type, $quantity, $reason, $userId, $extra) {
            if (! in_array($type, ['entrada', 'salida'], true) || $quantity < 1 || ! in_array($reason, ['venta', 'ajuste', 'reposición'], true)) {
                throw ValidationException::withMessages(['cantidad' => 'Movimiento de inventario inválido.']);
            }
            $product = Product::lockForUpdate()->findOrFail($productId);
            if ($key = $extra['request_key'] ?? null) {
                $existing = MovimientoInventario::where('request_key', $key)->first();
                if ($existing) {
                    abort_unless($existing->producto_id === $productId && $existing->tipo === $type && $existing->cantidad === $quantity && $existing->motivo === $reason && $existing->usuario_id === $userId, 409, 'La clave pertenece a un movimiento diferente.');

                    return $existing;
                }
            }
            $before = (int) $product->stock;
            $after = $before + ($type === 'entrada' ? $quantity : -$quantity);
            if ($after < 0 || $after > 1000000) {
                throw ValidationException::withMessages(['cantidad' => 'El movimiento dejaría existencias fuera del rango de 0 a 1,000,000.']);
            }
            $changed = Product::whereKey($productId)->where('inventory_version', $product->inventory_version)->update(['stock' => $after, 'inventory_version' => $product->inventory_version + 1, 'updated_at' => now()]);
            abort_unless($changed, 409, 'El inventario cambió. Actualiza y reintenta.');
            $movement = MovimientoInventario::create(['producto_id' => $productId, 'tipo' => $type, 'cantidad' => $quantity, 'motivo' => $reason, 'fecha' => now(), 'stock_anterior' => $before, 'stock_resultante' => $after, 'usuario_id' => $userId, ...$extra]);
            $this->checkPush($productId, $userId);

            return $movement;
        }, 3);
    }

    public function setStock(int $productId, int $desired, ?int $userId = null): void
    {
        $product = Product::lockForUpdate()->findOrFail($productId);
        $difference = $desired - $product->stock;
        if ($difference !== 0) {
            $this->move($productId, $difference > 0 ? 'entrada' : 'salida', abs($difference), 'ajuste', $userId, ['referencia' => 'Ajuste desde administración de catálogo']);
        }
    }

    public function checkPush(int $productId, ?int $userId = null): ?Pedido
    {
        return DB::transaction(function () use ($productId, $userId) {
            $product = Product::lockForUpdate()->findOrFail($productId);
            if ($product->estrategia_logistica !== 'PUSH' || $product->stock > $product->stock_minimo || ! $product->proveedor_id) {
                return null;
            }
            $pending = Pedido::where('producto_id', $productId)->where('tipo', 'reposicion')->where('estado', 'pendiente')->sum('cantidad');
            $needed = max(0, $product->stock_objetivo - $product->stock - $pending);
            if (! $needed) {
                return null;
            }
            $order = Pedido::where('automatico_key', 'push:'.$productId)->lockForUpdate()->first();
            if ($order) {
                $order->increment('cantidad', $needed);

                return $order->fresh();
            }

            return Pedido::create(['producto_id' => $productId, 'cantidad' => $needed, 'tipo' => 'reposicion', 'estado' => 'pendiente', 'automatico' => true, 'automatico_key' => 'push:'.$productId, 'usuario_id' => $userId]);
        }, 3);
    }

    public function createOrder(array $data, ?int $userId): Pedido
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::lockForUpdate()->findOrFail($data['producto_id']);
            if ($key = $data['request_key'] ?? null) {
                $existing = Pedido::where('request_key', $key)->first();
                if ($existing) {
                    abort_unless($existing->producto_id === $product->id && $existing->tipo === $data['tipo'] && $existing->cantidad === (int) $data['cantidad'] && $existing->usuario_id === $userId, 409, 'La clave corresponde a otro pedido.');

                    return $existing;
                }
            }
            if ($data['tipo'] === 'reposicion' && ! $product->proveedor_id) {
                throw ValidationException::withMessages(['producto_id' => 'Asocia un proveedor antes de solicitar reposición.']);
            }
            if ($data['tipo'] === 'reposicion') {
                $pending = Pedido::where('producto_id', $product->id)->where('tipo', 'reposicion')->where('estado', 'pendiente')->sum('cantidad');
                if ($product->stock + $pending + (int) $data['cantidad'] > 1000000) {
                    throw ValidationException::withMessages(['cantidad' => 'La reposición excede la capacidad máxima.']);
                }
            }

            return Pedido::create([...$data, 'usuario_id' => $userId, 'automatico' => false, 'estado' => 'pendiente']);
        }, 3);
    }

    public function receiveDirect(array $data, int $userId): MovimientoInventario
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::lockForUpdate()->findOrFail($data['producto_id']);
            if ($key = $data['request_key'] ?? null) {
                $existing = MovimientoInventario::where('request_key', $key)->first();
                if ($existing) {
                    abort_unless($existing->producto_id === $product->id && $existing->cantidad === (int) $data['cantidad'] && $existing->motivo === 'reposición' && $existing->usuario_id === $userId, 409, 'La clave pertenece a otro movimiento.');

                    return $existing;
                }
            }
            abort_if(Pedido::where('producto_id', $product->id)->where('tipo', 'reposicion')->where('estado', 'pendiente')->exists(), 422, 'Ya existe una reposición pendiente. Recíbela desde Pedidos SCM para evitar duplicarla.');
            $order = $this->createOrder(['producto_id' => $product->id, 'cantidad' => $data['cantidad'], 'tipo' => 'reposicion'], $userId);
            $this->fulfill($order->id, $userId);
            $movement = MovimientoInventario::where('pedido_id', $order->id)->firstOrFail();
            $movement->update(array_intersect_key($data, array_flip(['referencia', 'request_key'])));

            return $movement;
        }, 3);
    }

    public function fulfill(int $orderId, ?int $userId): Pedido
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $lookup = Pedido::findOrFail($orderId);
            Product::whereKey($lookup->producto_id)->lockForUpdate()->firstOrFail();
            $order = Pedido::lockForUpdate()->findOrFail($orderId);
            if ($order->estado === 'surtido') {
                return $order;
            }
            $changed = Pedido::whereKey($orderId)->where('estado', 'pendiente')->update(['estado' => 'surtido', 'automatico_key' => null, 'fecha_surtido' => now(), 'updated_at' => now()]);
            abort_unless($changed, 409, 'El pedido cambió. Actualiza e inténtalo de nuevo.');
            $this->move($order->producto_id, $order->tipo === 'reposicion' ? 'entrada' : 'salida', $order->cantidad, $order->tipo === 'reposicion' ? 'reposición' : 'venta', $userId, ['pedido_id' => $orderId, 'referencia' => 'Pedido SCM #'.$orderId]);

            return $order->fresh();
        }, 3);
    }
}
```

### 28. app/Services/ScmReports.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/app/Services/ScmReports.php`.

```php
<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ScmReports
{
    public function summary(): array
    {
        $products = Product::with(['proveedor', 'inventario'])->orderBy('id')->get();
        $cutoff = now()->subDays(30);
        $sales = MovimientoInventario::query()->where('tipo', 'salida')->where('motivo', 'venta')
            ->where(fn ($q) => $q->whereNull('order_id')->orWhereIn('order_id', DB::table('orders')->select('id')->whereIn('status', ['Enviado', 'Entregado'])));
        $allSales = (clone $sales)->selectRaw('producto_id, SUM(cantidad) as total')->groupBy('producto_id')->pluck('total', 'producto_id');
        $recent = (clone $sales)->where('fecha', '>=', $cutoff)->selectRaw('producto_id, SUM(cantidad) as total')->groupBy('producto_id')->pluck('total', 'producto_id');
        $top = $products->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'unidades' => (int) ($allSales[$p->id] ?? 0)])->filter(fn ($p) => $p['unidades'] > 0)->sortByDesc('unidades')->take(10)->values();
        $slow = $products->filter(fn ($p) => $p->stock > 0 && (int) ($recent[$p->id] ?? 0) <= 2)->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'stock_actual' => $p->stock, 'unidades_30_dias' => (int) ($recent[$p->id] ?? 0)])->values();

        return ['total_productos' => $products->count(), 'unidades_stock' => $products->sum('stock'),
            'valor_inventario_cents' => $products->sum(fn ($p) => $p->stock * $p->costo_unitario_cents),
            'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
            'inventario_critico' => $products->filter(fn ($p) => $p->stock <= $p->stock_minimo)->map->scmPresentation()->values(),
            'productos_mas_vendidos' => $top, 'rotacion_lenta' => $slow, 'periodo_rotacion_dias' => 30, 'umbral_rotacion_unidades' => 2,
            'comparacion' => collect(['PUSH', 'PULL'])->map(function ($strategy) use ($products) {
                $group = $products->where('estrategia_logistica', $strategy);

                return ['estrategia' => $strategy, 'productos' => $group->count(), 'stock' => $group->sum('stock'), 'criticos' => $group->filter(fn ($p) => $p->stock <= $p->stock_minimo)->count()];
            })->values()];
    }
}
```

### 29. bootstrap/app.php

**Base CRM.** Desde original: Reemplazar completo. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/bootstrap/app.php`.

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => \App\Http\Middleware\RequireRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

### 30. database/migrations/2026_10_01_000001_create_crm_tables.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/database/migrations/2026_10_01_000001_create_crm_tables.php`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cliente')->index();
        });
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('correo')->unique();
            $table->string('telefono', 25)->nullable();
            $table->string('empresa', 150)->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->string('estado')->default('activo')->index();
            $table->string('etapa_crm')->default('Prospecto')->index();
            $table->timestamps();
        });
        Schema::create('interacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo');
            $table->text('descripcion');
            $table->timestamp('fecha')->index();
            $table->timestamps();
        });
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('puntuacion');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('interacciones');
        Schema::dropIfExists('clientes');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
```

### 31. database/migrations/2026_10_01_000002_create_store_tables.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/database/migrations/2026_10_01_000002_create_store_tables.php`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('color')->default('');
            $table->string('category', 40);
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('original_price_cents')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image', 2048);
            $table->string('badge')->nullable();
            $table->text('description');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->uuid('checkout_key');
            $table->unique(['user_id', 'checkout_key']);
            $table->string('address', 255);
            $table->string('city', 120);
            $table->string('zip', 5);
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->string('status')->default('Pendiente');
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('price_cents');
            $table->text('description');
            $table->string('image', 2048);
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image', 2048);
            $table->unsignedInteger('current_bid_cents');
            $table->timestamp('ends_at');
            $table->timestamps();
        });
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email');
            $table->text('message');
            $table->boolean('handled')->default(false);
            $table->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['subscriptions', 'contact_messages', 'bids', 'auctions', 'comments', 'publications', 'order_items', 'orders', 'products'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
```

### 32. database/migrations/2026_10_02_000001_create_scm_tables.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/database/migrations/2026_10_02_000001_create_scm_tables.php`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120);
            $t->string('contacto', 120);
            $t->string('correo')->unique();
            $t->string('telefono', 25);
            $t->timestamps();
        });
        Schema::table('products', function (Blueprint $t) {
            $t->foreignId('proveedor_id')->nullable()->constrained('proveedores')->restrictOnDelete();
            $t->unsignedInteger('stock_minimo')->default(0);
            $t->unsignedInteger('stock_objetivo')->default(1);
            $t->unsignedInteger('costo_unitario_cents')->default(0);
            $t->string('estrategia_logistica')->default('PULL')->index();
            $t->unsignedInteger('inventory_version')->default(0);
            $t->softDeletes();
        });
        Schema::create('inventarios', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->unique()->constrained('products')->restrictOnDelete();
            $t->string('ubicacion')->default('Almacén principal');
            $t->string('unidad')->default('pieza');
            $t->timestamps();
        });
        Schema::create('pedidos_scm', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('products')->restrictOnDelete();
            $t->unsignedInteger('cantidad');
            $t->string('tipo');
            $t->string('estado')->default('pendiente')->index();
            $t->boolean('automatico')->default(false);
            $t->string('automatico_key')->nullable()->unique();
            $t->uuid('request_key')->nullable()->unique();
            $t->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('fecha_surtido')->nullable();
            $t->timestamps();
        });
        Schema::create('movimientos_inventario', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('products')->restrictOnDelete();
            $t->string('tipo');
            $t->unsignedInteger('cantidad');
            $t->string('motivo');
            $t->timestamp('fecha')->index();
            $t->unsignedInteger('stock_anterior');
            $t->unsignedInteger('stock_resultante');
            $t->string('referencia', 255)->nullable();
            $t->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('pedido_id')->nullable()->constrained('pedidos_scm')->restrictOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $t->uuid('request_key')->nullable()->unique();
            $t->timestamps();
        });
        Schema::create('scm_settings', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->string('nivel_scm')->default('Inicial');
            $t->json('checklist');
            $t->timestamps();
        });
        $now = now();
        DB::table('scm_settings')->insert(['id' => 1, 'nivel_scm' => 'Inicial', 'checklist' => json_encode(['catalogo' => false, 'proveedores' => false, 'inventario' => false, 'estrategias' => false, 'pedidos' => false, 'reportes' => false]), 'created_at' => $now, 'updated_at' => $now]);
        // Preserva el inventario existente como saldo inicial de trazabilidad.
        DB::table('products')->orderBy('id')->chunkById(200, function ($products) use ($now) {
            foreach ($products as $p) {
                DB::table('inventarios')->insert(['producto_id' => $p->id, 'ubicacion' => 'Almacén principal', 'unidad' => 'pieza', 'created_at' => $now, 'updated_at' => $now]);
                if ($p->stock > 0) {
                    DB::table('movimientos_inventario')->insert(['producto_id' => $p->id, 'tipo' => 'entrada', 'cantidad' => $p->stock, 'motivo' => 'ajuste', 'fecha' => $now, 'stock_anterior' => 0, 'stock_resultante' => $p->stock, 'referencia' => 'Saldo inicial al incorporar SCM', 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }

    public function down(): void
    {
        foreach (['scm_settings', 'movimientos_inventario', 'pedidos_scm', 'inventarios'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('products', function (Blueprint $t) {
            $t->dropConstrainedForeignId('proveedor_id');
            $t->dropColumn(['stock_minimo', 'stock_objetivo', 'costo_unitario_cents', 'estrategia_logistica', 'inventory_version', 'deleted_at']);
        });
        Schema::dropIfExists('proveedores');
    }
};
```

### 33. database/seeders/DatabaseSeeder.php

**Base CRM.** Desde original: Reemplazar completo. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/database/seeders/DatabaseSeeder.php`.

```php
<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = json_decode(file_get_contents(database_path('seeders/products.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($products as $p) {
            Product::firstOrCreate(['id' => $p['id']], ['name' => $p['name'], 'color' => $p['color'], 'category' => $p['category'],
                'price_cents' => (int) round($p['price'] * 100), 'original_price_cents' => (int) round($p['originalPrice'] * 100),
                'stock' => 30, 'image' => $p['image'], 'badge' => $p['badge'], 'description' => $p['description'], 'active' => true]);
        }
        Auction::firstOrCreate(['id' => 1], ['name' => 'HF Signature Hoodie (Sample 1/1)', 'image' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=800', 'current_bid_cents' => 350000, 'ends_at' => now()->addDays(7)]);
    }
}
```

### 34. database/seeders/products.json

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/database/seeders/products.json`.

```json
[
  {
    "id": 1,
    "name": "Hoodie Esencial",
    "color": "Gris Carbón",
    "originalPrice": 1890,
    "price": 1512,
    "image": "https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?q=80&w=800&auto=format&fit=crop",
    "badge": "PROMO -20%",
    "category": "Hoodie",
    "description": "Hoodie premium de alta calidad con ajuste relajado y diseño minimalista. Algodón de alto gramaje para durabilidad excepcional."
  },
  {
    "id": 2,
    "name": "Tee Oversized",
    "color": "Blanco Hueso",
    "originalPrice": 1190,
    "price": 952,
    "image": "https://images.unsplash.com/photo-1622445275576-721325763afe?q=80&w=800&auto=format&fit=crop",
    "badge": "PROMO -20%",
    "category": "T-Shirt",
    "description": "Camiseta oversized de algodón 100% con corte holgado y cómodo. Cuello reforzado."
  },
  {
    "id": 3,
    "name": "Hoodie Gráfico",
    "color": "Negro Lavado",
    "originalPrice": 2090,
    "price": 1672,
    "image": "https://images.unsplash.com/photo-1556821840-3a63f95609a7?q=80&w=800&auto=format&fit=crop",
    "badge": "PROMO -20%",
    "category": "Hoodie",
    "description": "Hoodie con gráficos exclusivos en serigrafía de alta densidad y acabado lavado para look desgastado."
  },
  {
    "id": 4,
    "name": "Tee Vintage",
    "color": "Gris",
    "originalPrice": 1390,
    "price": 1112,
    "image": "https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?q=80&w=800&auto=format&fit=crop",
    "badge": "PROMO -20%",
    "category": "T-Shirt",
    "description": "Camiseta con lavado vintage en ácido y gráfico retro craquelado intencionalmente."
  },
  {
    "id": 5,
    "name": "Cargo Pants",
    "color": "Negro",
    "originalPrice": 2490,
    "price": 1992,
    "image": "https://images.unsplash.com/photo-1618354691373-d851c5c3a990?q=80&w=800&auto=format&fit=crop",
    "badge": "PROMO -20%",
    "category": "Pants",
    "description": "Pantalones cargo utilitarios con múltiples bolsillos tridimensionales y tela resistente al desgarro."
  },
  {
    "id": 6,
    "name": "Beanie Premium",
    "color": "Negro",
    "originalPrice": 690,
    "price": 552,
    "image": "https://images.unsplash.com/photo-1576871337622-98d48d1cf531?q=80&w=800&auto=format&fit=crop",
    "badge": "PROMO -20%",
    "category": "Accessory",
    "description": "Gorro de punto premium con logo frontal bordado en 3D. Mezcla de lana y acrílico para retención térmica."
  }
]
```

### 35. lang/es/validation.php

**U2.** Desde original: Crear. Desde CRM anterior: Reemplazar completo. Destino: `HFSTUDIOS/lang/es/validation.php`.

```php
<?php

return [
    'required' => 'El campo :attribute es obligatorio.', 'email' => 'El campo :attribute debe ser un correo válido.',
    'unique' => 'El valor de :attribute ya está registrado.', 'exists' => 'El registro seleccionado en :attribute no existe.',
    'in' => 'El valor de :attribute no está permitido.', 'integer' => 'El campo :attribute debe ser un entero.',
    'numeric' => 'El campo :attribute debe ser numérico.', 'date' => 'El campo :attribute debe ser una fecha válida.',
    'before_or_equal' => 'El campo :attribute no debe superar :date.', 'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'min' => ['string' => 'El campo :attribute necesita al menos :min caracteres.', 'numeric' => 'El campo :attribute debe ser mayor o igual a :min.', 'array' => 'El campo :attribute necesita al menos :min elemento(s).'],
    'max' => ['string' => 'El campo :attribute admite hasta :max caracteres.', 'numeric' => 'El campo :attribute debe ser menor o igual a :max.', 'array' => 'El campo :attribute admite hasta :max elementos.'],
    'gt' => ['numeric' => 'El campo :attribute debe ser mayor que :value.'],
    'gte' => ['numeric' => 'El campo :attribute debe ser mayor o igual a :value.'],
    'prohibited' => 'El campo :attribute no se edita aquí. Registra un movimiento de inventario.',
    'array' => 'El campo :attribute debe contener una lista válida.',
    'between' => ['numeric' => 'El campo :attribute debe estar entre :min y :max.'],
    'regex' => 'El formato de :attribute no es válido.', 'url' => 'El campo :attribute debe ser una URL HTTP o HTTPS válida.',
    'decimal' => 'El campo :attribute debe tener entre :min y :max decimales.', 'uuid' => 'El campo :attribute debe ser un UUID válido.',
    'distinct' => 'El campo :attribute contiene elementos repetidos.', 'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'attributes' => ['nombre' => 'nombre', 'correo' => 'correo', 'telefono' => 'teléfono', 'etapa_crm' => 'etapa CRM', 'password' => 'contraseña', 'name' => 'nombre', 'email' => 'correo', 'description' => 'descripción', 'price' => 'precio', 'stock' => 'existencias', 'zip' => 'código postal', 'address' => 'dirección', 'city' => 'ciudad', 'items' => 'productos', 'descripcion' => 'descripción', 'fecha' => 'fecha', 'puntuacion' => 'puntuación', 'amount' => 'oferta', 'stock_actual' => 'stock actual', 'stock_minimo' => 'stock mínimo', 'stock_objetivo' => 'stock objetivo', 'proveedor_id' => 'proveedor', 'costo_unitario' => 'costo unitario', 'precio_venta' => 'precio de venta', 'estrategia_logistica' => 'estrategia logística', 'ubicacion' => 'ubicación', 'cantidad' => 'cantidad', 'nivel_scm' => 'nivel SCM'],
];
```

### 36. package-lock.json

**Base CRM.** Desde original: Reemplazar completo. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/package-lock.json`.

```json
{
    "name": "HFSTUDIOS",
    "lockfileVersion": 3,
    "requires": true,
    "packages": {
        "": {
            "dependencies": {
                "alpinejs": "3.14.9",
                "jspdf": "3.0.4",
                "jspdf-autotable": "5.0.2"
            },
            "devDependencies": {
                "@tailwindcss/vite": "^4.0.0",
                "autoprefixer": "^10.4.24",
                "axios": "^1.11.0",
                "concurrently": "^9.0.1",
                "laravel-vite-plugin": "^2.0.0",
                "postcss": "^8.5.6",
                "tailwindcss": "^4.1.18",
                "vite": "^7.0.7"
            }
        },
        "node_modules/@babel/runtime": {
            "version": "7.29.7",
            "resolved": "https://registry.npmjs.org/@babel/runtime/-/runtime-7.29.7.tgz",
            "integrity": "sha512-Nq8OhGWiZIZGV6hLHoyAKLLcJihP/xFeBMGJoUrxTX2psI8dCifzLhZISFb+VWS3wFMRDmCGw5R+dOySCqPLhw==",
            "license": "MIT",
            "engines": {
                "node": ">=6.9.0"
            }
        },
        "node_modules/@esbuild/aix-ppc64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/aix-ppc64/-/aix-ppc64-0.27.3.tgz",
            "integrity": "sha512-9fJMTNFTWZMh5qwrBItuziu834eOCUcEqymSH7pY+zoMVEZg3gcPuBNxH1EvfVYe9h0x/Ptw8KBzv7qxb7l8dg==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "aix"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/android-arm": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/android-arm/-/android-arm-0.27.3.tgz",
            "integrity": "sha512-i5D1hPY7GIQmXlXhs2w8AWHhenb00+GxjxRncS2ZM7YNVGNfaMxgzSGuO8o8SJzRc/oZwU2bcScvVERk03QhzA==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/android-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/android-arm64/-/android-arm64-0.27.3.tgz",
            "integrity": "sha512-YdghPYUmj/FX2SYKJ0OZxf+iaKgMsKHVPF1MAq/P8WirnSpCStzKJFjOjzsW0QQ7oIAiccHdcqjbHmJxRb/dmg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/android-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/android-x64/-/android-x64-0.27.3.tgz",
            "integrity": "sha512-IN/0BNTkHtk8lkOM8JWAYFg4ORxBkZQf9zXiEOfERX/CzxW3Vg1ewAhU7QSWQpVIzTW+b8Xy+lGzdYXV6UZObQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/darwin-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/darwin-arm64/-/darwin-arm64-0.27.3.tgz",
            "integrity": "sha512-Re491k7ByTVRy0t3EKWajdLIr0gz2kKKfzafkth4Q8A5n1xTHrkqZgLLjFEHVD+AXdUGgQMq+Godfq45mGpCKg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/darwin-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/darwin-x64/-/darwin-x64-0.27.3.tgz",
            "integrity": "sha512-vHk/hA7/1AckjGzRqi6wbo+jaShzRowYip6rt6q7VYEDX4LEy1pZfDpdxCBnGtl+A5zq8iXDcyuxwtv3hNtHFg==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/freebsd-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/freebsd-arm64/-/freebsd-arm64-0.27.3.tgz",
            "integrity": "sha512-ipTYM2fjt3kQAYOvo6vcxJx3nBYAzPjgTCk7QEgZG8AUO3ydUhvelmhrbOheMnGOlaSFUoHXB6un+A7q4ygY9w==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/freebsd-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/freebsd-x64/-/freebsd-x64-0.27.3.tgz",
            "integrity": "sha512-dDk0X87T7mI6U3K9VjWtHOXqwAMJBNN2r7bejDsc+j03SEjtD9HrOl8gVFByeM0aJksoUuUVU9TBaZa2rgj0oA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-arm": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-arm/-/linux-arm-0.27.3.tgz",
            "integrity": "sha512-s6nPv2QkSupJwLYyfS+gwdirm0ukyTFNl3KTgZEAiJDd+iHZcbTPPcWCcRYH+WlNbwChgH2QkE9NSlNrMT8Gfw==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-arm64/-/linux-arm64-0.27.3.tgz",
            "integrity": "sha512-sZOuFz/xWnZ4KH3YfFrKCf1WyPZHakVzTiqji3WDc0BCl2kBwiJLCXpzLzUBLgmp4veFZdvN5ChW4Eq/8Fc2Fg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-ia32": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-ia32/-/linux-ia32-0.27.3.tgz",
            "integrity": "sha512-yGlQYjdxtLdh0a3jHjuwOrxQjOZYD/C9PfdbgJJF3TIZWnm/tMd/RcNiLngiu4iwcBAOezdnSLAwQDPqTmtTYg==",
            "cpu": [
                "ia32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-loong64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-loong64/-/linux-loong64-0.27.3.tgz",
            "integrity": "sha512-WO60Sn8ly3gtzhyjATDgieJNet/KqsDlX5nRC5Y3oTFcS1l0KWba+SEa9Ja1GfDqSF1z6hif/SkpQJbL63cgOA==",
            "cpu": [
                "loong64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-mips64el": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-mips64el/-/linux-mips64el-0.27.3.tgz",
            "integrity": "sha512-APsymYA6sGcZ4pD6k+UxbDjOFSvPWyZhjaiPyl/f79xKxwTnrn5QUnXR5prvetuaSMsb4jgeHewIDCIWljrSxw==",
            "cpu": [
                "mips64el"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-ppc64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-ppc64/-/linux-ppc64-0.27.3.tgz",
            "integrity": "sha512-eizBnTeBefojtDb9nSh4vvVQ3V9Qf9Df01PfawPcRzJH4gFSgrObw+LveUyDoKU3kxi5+9RJTCWlj4FjYXVPEA==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-riscv64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-riscv64/-/linux-riscv64-0.27.3.tgz",
            "integrity": "sha512-3Emwh0r5wmfm3ssTWRQSyVhbOHvqegUDRd0WhmXKX2mkHJe1SFCMJhagUleMq+Uci34wLSipf8Lagt4LlpRFWQ==",
            "cpu": [
                "riscv64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-s390x": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-s390x/-/linux-s390x-0.27.3.tgz",
            "integrity": "sha512-pBHUx9LzXWBc7MFIEEL0yD/ZVtNgLytvx60gES28GcWMqil8ElCYR4kvbV2BDqsHOvVDRrOxGySBM9Fcv744hw==",
            "cpu": [
                "s390x"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-x64/-/linux-x64-0.27.3.tgz",
            "integrity": "sha512-Czi8yzXUWIQYAtL/2y6vogER8pvcsOsk5cpwL4Gk5nJqH5UZiVByIY8Eorm5R13gq+DQKYg0+JyQoytLQas4dA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/netbsd-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/netbsd-arm64/-/netbsd-arm64-0.27.3.tgz",
            "integrity": "sha512-sDpk0RgmTCR/5HguIZa9n9u+HVKf40fbEUt+iTzSnCaGvY9kFP0YKBWZtJaraonFnqef5SlJ8/TiPAxzyS+UoA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "netbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/netbsd-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/netbsd-x64/-/netbsd-x64-0.27.3.tgz",
            "integrity": "sha512-P14lFKJl/DdaE00LItAukUdZO5iqNH7+PjoBm+fLQjtxfcfFE20Xf5CrLsmZdq5LFFZzb5JMZ9grUwvtVYzjiA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "netbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/openbsd-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/openbsd-arm64/-/openbsd-arm64-0.27.3.tgz",
            "integrity": "sha512-AIcMP77AvirGbRl/UZFTq5hjXK+2wC7qFRGoHSDrZ5v5b8DK/GYpXW3CPRL53NkvDqb9D+alBiC/dV0Fb7eJcw==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/openbsd-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/openbsd-x64/-/openbsd-x64-0.27.3.tgz",
            "integrity": "sha512-DnW2sRrBzA+YnE70LKqnM3P+z8vehfJWHXECbwBmH/CU51z6FiqTQTHFenPlHmo3a8UgpLyH3PT+87OViOh1AQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/openharmony-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/openharmony-arm64/-/openharmony-arm64-0.27.3.tgz",
            "integrity": "sha512-NinAEgr/etERPTsZJ7aEZQvvg/A6IsZG/LgZy+81wON2huV7SrK3e63dU0XhyZP4RKGyTm7aOgmQk0bGp0fy2g==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openharmony"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/sunos-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/sunos-x64/-/sunos-x64-0.27.3.tgz",
            "integrity": "sha512-PanZ+nEz+eWoBJ8/f8HKxTTD172SKwdXebZ0ndd953gt1HRBbhMsaNqjTyYLGLPdoWHy4zLU7bDVJztF5f3BHA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "sunos"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/win32-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/win32-arm64/-/win32-arm64-0.27.3.tgz",
            "integrity": "sha512-B2t59lWWYrbRDw/tjiWOuzSsFh1Y/E95ofKz7rIVYSQkUYBjfSgf6oeYPNWHToFRr2zx52JKApIcAS/D5TUBnA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/win32-ia32": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/win32-ia32/-/win32-ia32-0.27.3.tgz",
            "integrity": "sha512-QLKSFeXNS8+tHW7tZpMtjlNb7HKau0QDpwm49u0vUp9y1WOF+PEzkU84y9GqYaAVW8aH8f3GcBck26jh54cX4Q==",
            "cpu": [
                "ia32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/win32-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/win32-x64/-/win32-x64-0.27.3.tgz",
            "integrity": "sha512-4uJGhsxuptu3OcpVAzli+/gWusVGwZZHTlS63hh++ehExkVT8SgiEf7/uC/PclrPPkLhZqGgCTjd0VWLo6xMqA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@jridgewell/gen-mapping": {
            "version": "0.3.13",
            "resolved": "https://registry.npmjs.org/@jridgewell/gen-mapping/-/gen-mapping-0.3.13.tgz",
            "integrity": "sha512-2kkt/7niJ6MgEPxF0bYdQ6etZaA+fQvDcLKckhy1yIQOzaoKjBBjSj63/aLVjYE3qhRt5dvM+uUyfCg6UKCBbA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/sourcemap-codec": "^1.5.0",
                "@jridgewell/trace-mapping": "^0.3.24"
            }
        },
        "node_modules/@jridgewell/remapping": {
            "version": "2.3.5",
            "resolved": "https://registry.npmjs.org/@jridgewell/remapping/-/remapping-2.3.5.tgz",
            "integrity": "sha512-LI9u/+laYG4Ds1TDKSJW2YPrIlcVYOwi2fUC6xB43lueCjgxV4lffOCZCtYFiH6TNOX+tQKXx97T4IKHbhyHEQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/gen-mapping": "^0.3.5",
                "@jridgewell/trace-mapping": "^0.3.24"
            }
        },
        "node_modules/@jridgewell/resolve-uri": {
            "version": "3.1.2",
            "resolved": "https://registry.npmjs.org/@jridgewell/resolve-uri/-/resolve-uri-3.1.2.tgz",
            "integrity": "sha512-bRISgCIjP20/tbWSPWMEi54QVPRZExkuD9lJL+UIxUKtwVJA8wW1Trb1jMs1RFXo1CBTNZ/5hpC9QvmKWdopKw==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=6.0.0"
            }
        },
        "node_modules/@jridgewell/sourcemap-codec": {
            "version": "1.5.5",
            "resolved": "https://registry.npmjs.org/@jridgewell/sourcemap-codec/-/sourcemap-codec-1.5.5.tgz",
            "integrity": "sha512-cYQ9310grqxueWbl+WuIUIaiUaDcj7WOq5fVhEljNVgRfOUhY9fy2zTvfoqWsnebh8Sl70VScFbICvJnLKB0Og==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/@jridgewell/trace-mapping": {
            "version": "0.3.31",
            "resolved": "https://registry.npmjs.org/@jridgewell/trace-mapping/-/trace-mapping-0.3.31.tgz",
            "integrity": "sha512-zzNR+SdQSDJzc8joaeP8QQoCQr8NuYx2dIIytl1QeBEZHJ9uW6hebsrYgbz8hJwUQao3TWCMtmfV8Nu1twOLAw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/resolve-uri": "^3.1.0",
                "@jridgewell/sourcemap-codec": "^1.4.14"
            }
        },
        "node_modules/@rollup/rollup-android-arm-eabi": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-android-arm-eabi/-/rollup-android-arm-eabi-4.57.1.tgz",
            "integrity": "sha512-A6ehUVSiSaaliTxai040ZpZ2zTevHYbvu/lDoeAteHI8QnaosIzm4qwtezfRg1jOYaUmnzLX1AOD6Z+UJjtifg==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ]
        },
        "node_modules/@rollup/rollup-android-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-android-arm64/-/rollup-android-arm64-4.57.1.tgz",
            "integrity": "sha512-dQaAddCY9YgkFHZcFNS/606Exo8vcLHwArFZ7vxXq4rigo2bb494/xKMMwRRQW6ug7Js6yXmBZhSBRuBvCCQ3w==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ]
        },
        "node_modules/@rollup/rollup-darwin-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-darwin-arm64/-/rollup-darwin-arm64-4.57.1.tgz",
            "integrity": "sha512-crNPrwJOrRxagUYeMn/DZwqN88SDmwaJ8Cvi/TN1HnWBU7GwknckyosC2gd0IqYRsHDEnXf328o9/HC6OkPgOg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ]
        },
        "node_modules/@rollup/rollup-darwin-x64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-darwin-x64/-/rollup-darwin-x64-4.57.1.tgz",
            "integrity": "sha512-Ji8g8ChVbKrhFtig5QBV7iMaJrGtpHelkB3lsaKzadFBe58gmjfGXAOfI5FV0lYMH8wiqsxKQ1C9B0YTRXVy4w==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ]
        },
        "node_modules/@rollup/rollup-freebsd-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-freebsd-arm64/-/rollup-freebsd-arm64-4.57.1.tgz",
            "integrity": "sha512-R+/WwhsjmwodAcz65guCGFRkMb4gKWTcIeLy60JJQbXrJ97BOXHxnkPFrP+YwFlaS0m+uWJTstrUA9o+UchFug==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ]
        },
        "node_modules/@rollup/rollup-freebsd-x64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-freebsd-x64/-/rollup-freebsd-x64-4.57.1.tgz",
            "integrity": "sha512-IEQTCHeiTOnAUC3IDQdzRAGj3jOAYNr9kBguI7MQAAZK3caezRrg0GxAb6Hchg4lxdZEI5Oq3iov/w/hnFWY9Q==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm-gnueabihf": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm-gnueabihf/-/rollup-linux-arm-gnueabihf-4.57.1.tgz",
            "integrity": "sha512-F8sWbhZ7tyuEfsmOxwc2giKDQzN3+kuBLPwwZGyVkLlKGdV1nvnNwYD0fKQ8+XS6hp9nY7B+ZeK01EBUE7aHaw==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm-musleabihf": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm-musleabihf/-/rollup-linux-arm-musleabihf-4.57.1.tgz",
            "integrity": "sha512-rGfNUfn0GIeXtBP1wL5MnzSj98+PZe/AXaGBCRmT0ts80lU5CATYGxXukeTX39XBKsxzFpEeK+Mrp9faXOlmrw==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm64-gnu/-/rollup-linux-arm64-gnu-4.57.1.tgz",
            "integrity": "sha512-MMtej3YHWeg/0klK2Qodf3yrNzz6CGjo2UntLvk2RSPlhzgLvYEB3frRvbEF2wRKh1Z2fDIg9KRPe1fawv7C+g==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm64-musl/-/rollup-linux-arm64-musl-4.57.1.tgz",
            "integrity": "sha512-1a/qhaaOXhqXGpMFMET9VqwZakkljWHLmZOX48R0I/YLbhdxr1m4gtG1Hq7++VhVUmf+L3sTAf9op4JlhQ5u1Q==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-loong64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-loong64-gnu/-/rollup-linux-loong64-gnu-4.57.1.tgz",
            "integrity": "sha512-QWO6RQTZ/cqYtJMtxhkRkidoNGXc7ERPbZN7dVW5SdURuLeVU7lwKMpo18XdcmpWYd0qsP1bwKPf7DNSUinhvA==",
            "cpu": [
                "loong64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-loong64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-loong64-musl/-/rollup-linux-loong64-musl-4.57.1.tgz",
            "integrity": "sha512-xpObYIf+8gprgWaPP32xiN5RVTi/s5FCR+XMXSKmhfoJjrpRAjCuuqQXyxUa/eJTdAE6eJ+KDKaoEqjZQxh3Gw==",
            "cpu": [
                "loong64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-ppc64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-ppc64-gnu/-/rollup-linux-ppc64-gnu-4.57.1.tgz",
            "integrity": "sha512-4BrCgrpZo4hvzMDKRqEaW1zeecScDCR+2nZ86ATLhAoJ5FQ+lbHVD3ttKe74/c7tNT9c6F2viwB3ufwp01Oh2w==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-ppc64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-ppc64-musl/-/rollup-linux-ppc64-musl-4.57.1.tgz",
            "integrity": "sha512-NOlUuzesGauESAyEYFSe3QTUguL+lvrN1HtwEEsU2rOwdUDeTMJdO5dUYl/2hKf9jWydJrO9OL/XSSf65R5+Xw==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-riscv64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-riscv64-gnu/-/rollup-linux-riscv64-gnu-4.57.1.tgz",
            "integrity": "sha512-ptA88htVp0AwUUqhVghwDIKlvJMD/fmL/wrQj99PRHFRAG6Z5nbWoWG4o81Nt9FT+IuqUQi+L31ZKAFeJ5Is+A==",
            "cpu": [
                "riscv64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-riscv64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-riscv64-musl/-/rollup-linux-riscv64-musl-4.57.1.tgz",
            "integrity": "sha512-S51t7aMMTNdmAMPpBg7OOsTdn4tySRQvklmL3RpDRyknk87+Sp3xaumlatU+ppQ+5raY7sSTcC2beGgvhENfuw==",
            "cpu": [
                "riscv64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-s390x-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-s390x-gnu/-/rollup-linux-s390x-gnu-4.57.1.tgz",
            "integrity": "sha512-Bl00OFnVFkL82FHbEqy3k5CUCKH6OEJL54KCyx2oqsmZnFTR8IoNqBF+mjQVcRCT5sB6yOvK8A37LNm/kPJiZg==",
            "cpu": [
                "s390x"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-x64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-x64-gnu/-/rollup-linux-x64-gnu-4.57.1.tgz",
            "integrity": "sha512-ABca4ceT4N+Tv/GtotnWAeXZUZuM/9AQyCyKYyKnpk4yoA7QIAuBt6Hkgpw8kActYlew2mvckXkvx0FfoInnLg==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-x64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-x64-musl/-/rollup-linux-x64-musl-4.57.1.tgz",
            "integrity": "sha512-HFps0JeGtuOR2convgRRkHCekD7j+gdAuXM+/i6kGzQtFhlCtQkpwtNzkNj6QhCDp7DRJ7+qC/1Vg2jt5iSOFw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-openbsd-x64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-openbsd-x64/-/rollup-openbsd-x64-4.57.1.tgz",
            "integrity": "sha512-H+hXEv9gdVQuDTgnqD+SQffoWoc0Of59AStSzTEj/feWTBAnSfSD3+Dql1ZruJQxmykT/JVY0dE8Ka7z0DH1hw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openbsd"
            ]
        },
        "node_modules/@rollup/rollup-openharmony-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-openharmony-arm64/-/rollup-openharmony-arm64-4.57.1.tgz",
            "integrity": "sha512-4wYoDpNg6o/oPximyc/NG+mYUejZrCU2q+2w6YZqrAs2UcNUChIZXjtafAiiZSUc7On8v5NyNj34Kzj/Ltk6dQ==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openharmony"
            ]
        },
        "node_modules/@rollup/rollup-win32-arm64-msvc": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-arm64-msvc/-/rollup-win32-arm64-msvc-4.57.1.tgz",
            "integrity": "sha512-O54mtsV/6LW3P8qdTcamQmuC990HDfR71lo44oZMZlXU4tzLrbvTii87Ni9opq60ds0YzuAlEr/GNwuNluZyMQ==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@rollup/rollup-win32-ia32-msvc": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-ia32-msvc/-/rollup-win32-ia32-msvc-4.57.1.tgz",
            "integrity": "sha512-P3dLS+IerxCT/7D2q2FYcRdWRl22dNbrbBEtxdWhXrfIMPP9lQhb5h4Du04mdl5Woq05jVCDPCMF7Ub0NAjIew==",
            "cpu": [
                "ia32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@rollup/rollup-win32-x64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-x64-gnu/-/rollup-win32-x64-gnu-4.57.1.tgz",
            "integrity": "sha512-VMBH2eOOaKGtIJYleXsi2B8CPVADrh+TyNxJ4mWPnKfLB/DBUmzW+5m1xUrcwWoMfSLagIRpjUFeW5CO5hyciQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@rollup/rollup-win32-x64-msvc": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-x64-msvc/-/rollup-win32-x64-msvc-4.57.1.tgz",
            "integrity": "sha512-mxRFDdHIWRxg3UfIIAwCm6NzvxG0jDX/wBN6KsQFTvKFqqg9vTrWUE68qEjHt19A5wwx5X5aUi2zuZT7YR0jrA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@tailwindcss/node": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/node/-/node-4.1.18.tgz",
            "integrity": "sha512-DoR7U1P7iYhw16qJ49fgXUlry1t4CpXeErJHnQ44JgTSKMaZUdf17cfn5mHchfJ4KRBZRFA/Coo+MUF5+gOaCQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/remapping": "^2.3.4",
                "enhanced-resolve": "^5.18.3",
                "jiti": "^2.6.1",
                "lightningcss": "1.30.2",
                "magic-string": "^0.30.21",
                "source-map-js": "^1.2.1",
                "tailwindcss": "4.1.18"
            }
        },
        "node_modules/@tailwindcss/oxide": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide/-/oxide-4.1.18.tgz",
            "integrity": "sha512-EgCR5tTS5bUSKQgzeMClT6iCY3ToqE1y+ZB0AKldj809QXk1Y+3jB0upOYZrn9aGIzPtUsP7sX4QQ4XtjBB95A==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 10"
            },
            "optionalDependencies": {
                "@tailwindcss/oxide-android-arm64": "4.1.18",
                "@tailwindcss/oxide-darwin-arm64": "4.1.18",
                "@tailwindcss/oxide-darwin-x64": "4.1.18",
                "@tailwindcss/oxide-freebsd-x64": "4.1.18",
                "@tailwindcss/oxide-linux-arm-gnueabihf": "4.1.18",
                "@tailwindcss/oxide-linux-arm64-gnu": "4.1.18",
                "@tailwindcss/oxide-linux-arm64-musl": "4.1.18",
                "@tailwindcss/oxide-linux-x64-gnu": "4.1.18",
                "@tailwindcss/oxide-linux-x64-musl": "4.1.18",
                "@tailwindcss/oxide-wasm32-wasi": "4.1.18",
                "@tailwindcss/oxide-win32-arm64-msvc": "4.1.18",
                "@tailwindcss/oxide-win32-x64-msvc": "4.1.18"
            }
        },
        "node_modules/@tailwindcss/oxide-android-arm64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-android-arm64/-/oxide-android-arm64-4.1.18.tgz",
            "integrity": "sha512-dJHz7+Ugr9U/diKJA0W6N/6/cjI+ZTAoxPf9Iz9BFRF2GzEX8IvXxFIi/dZBloVJX/MZGvRuFA9rqwdiIEZQ0Q==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-darwin-arm64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-darwin-arm64/-/oxide-darwin-arm64-4.1.18.tgz",
            "integrity": "sha512-Gc2q4Qhs660bhjyBSKgq6BYvwDz4G+BuyJ5H1xfhmDR3D8HnHCmT/BSkvSL0vQLy/nkMLY20PQ2OoYMO15Jd0A==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-darwin-x64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-darwin-x64/-/oxide-darwin-x64-4.1.18.tgz",
            "integrity": "sha512-FL5oxr2xQsFrc3X9o1fjHKBYBMD1QZNyc1Xzw/h5Qu4XnEBi3dZn96HcHm41c/euGV+GRiXFfh2hUCyKi/e+yw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-freebsd-x64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-freebsd-x64/-/oxide-freebsd-x64-4.1.18.tgz",
            "integrity": "sha512-Fj+RHgu5bDodmV1dM9yAxlfJwkkWvLiRjbhuO2LEtwtlYlBgiAT4x/j5wQr1tC3SANAgD+0YcmWVrj8R9trVMA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-arm-gnueabihf": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-arm-gnueabihf/-/oxide-linux-arm-gnueabihf-4.1.18.tgz",
            "integrity": "sha512-Fp+Wzk/Ws4dZn+LV2Nqx3IilnhH51YZoRaYHQsVq3RQvEl+71VGKFpkfHrLM/Li+kt5c0DJe/bHXK1eHgDmdiA==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-arm64-gnu": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-arm64-gnu/-/oxide-linux-arm64-gnu-4.1.18.tgz",
            "integrity": "sha512-S0n3jboLysNbh55Vrt7pk9wgpyTTPD0fdQeh7wQfMqLPM/Hrxi+dVsLsPrycQjGKEQk85Kgbx+6+QnYNiHalnw==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-arm64-musl": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-arm64-musl/-/oxide-linux-arm64-musl-4.1.18.tgz",
            "integrity": "sha512-1px92582HkPQlaaCkdRcio71p8bc8i/ap5807tPRDK/uw953cauQBT8c5tVGkOwrHMfc2Yh6UuxaH4vtTjGvHg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-x64-gnu": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-x64-gnu/-/oxide-linux-x64-gnu-4.1.18.tgz",
            "integrity": "sha512-v3gyT0ivkfBLoZGF9LyHmts0Isc8jHZyVcbzio6Wpzifg/+5ZJpDiRiUhDLkcr7f/r38SWNe7ucxmGW3j3Kb/g==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-x64-musl": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-x64-musl/-/oxide-linux-x64-musl-4.1.18.tgz",
            "integrity": "sha512-bhJ2y2OQNlcRwwgOAGMY0xTFStt4/wyU6pvI6LSuZpRgKQwxTec0/3Scu91O8ir7qCR3AuepQKLU/kX99FouqQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-wasm32-wasi/-/oxide-wasm32-wasi-4.1.18.tgz",
            "integrity": "sha512-LffYTvPjODiP6PT16oNeUQJzNVyJl1cjIebq/rWWBF+3eDst5JGEFSc5cWxyRCJ0Mxl+KyIkqRxk1XPEs9x8TA==",
            "bundleDependencies": [
                "@napi-rs/wasm-runtime",
                "@emnapi/core",
                "@emnapi/runtime",
                "@tybys/wasm-util",
                "@emnapi/wasi-threads",
                "tslib"
            ],
            "cpu": [
                "wasm32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "@emnapi/core": "^1.7.1",
                "@emnapi/runtime": "^1.7.1",
                "@emnapi/wasi-threads": "^1.1.0",
                "@napi-rs/wasm-runtime": "^1.1.0",
                "@tybys/wasm-util": "^0.10.1",
                "tslib": "^2.4.0"
            },
            "engines": {
                "node": ">=14.0.0"
            }
        },
        "node_modules/@tailwindcss/oxide-win32-arm64-msvc": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-win32-arm64-msvc/-/oxide-win32-arm64-msvc-4.1.18.tgz",
            "integrity": "sha512-HjSA7mr9HmC8fu6bdsZvZ+dhjyGCLdotjVOgLA2vEqxEBZaQo9YTX4kwgEvPCpRh8o4uWc4J/wEoFzhEmjvPbA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-win32-x64-msvc": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-win32-x64-msvc/-/oxide-win32-x64-msvc-4.1.18.tgz",
            "integrity": "sha512-bJWbyYpUlqamC8dpR7pfjA0I7vdF6t5VpUGMWRkXVE3AXgIZjYUYAK7II1GNaxR8J1SSrSrppRar8G++JekE3Q==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/vite": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/vite/-/vite-4.1.18.tgz",
            "integrity": "sha512-jVA+/UpKL1vRLg6Hkao5jldawNmRo7mQYrZtNHMIVpLfLhDml5nMRUo/8MwoX2vNXvnaXNNMedrMfMugAVX1nA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@tailwindcss/node": "4.1.18",
                "@tailwindcss/oxide": "4.1.18",
                "tailwindcss": "4.1.18"
            },
            "peerDependencies": {
                "vite": "^5.2.0 || ^6 || ^7"
            }
        },
        "node_modules/@types/estree": {
            "version": "1.0.8",
            "resolved": "https://registry.npmjs.org/@types/estree/-/estree-1.0.8.tgz",
            "integrity": "sha512-dWHzHa2WqEXI/O1E9OjrocMTKJl2mSrEolh1Iomrv6U+JuNwaHXsXx9bLu5gG7BUWFIN0skIQJQ/L1rIex4X6w==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/@types/pako": {
            "version": "2.0.4",
            "resolved": "https://registry.npmjs.org/@types/pako/-/pako-2.0.4.tgz",
            "integrity": "sha512-VWDCbrLeVXJM9fihYodcLiIv0ku+AlOa/TQ1SvYOaBuyrSKgEcro95LJyIsJ4vSo6BXIxOKxiJAat04CmST9Fw==",
            "license": "MIT"
        },
        "node_modules/@types/raf": {
            "version": "3.4.3",
            "resolved": "https://registry.npmjs.org/@types/raf/-/raf-3.4.3.tgz",
            "integrity": "sha512-c4YAvMedbPZ5tEyxzQdMoOhhJ4RD3rngZIdwC2/qDN3d7JpEhB6fiBRKVY1lg5B7Wk+uPBjn5f39j1/2MY1oOw==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/@types/trusted-types": {
            "version": "2.0.7",
            "resolved": "https://registry.npmjs.org/@types/trusted-types/-/trusted-types-2.0.7.tgz",
            "integrity": "sha512-ScaPdn1dQczgbl0QFTeTOmVHFULt394XJgOQNoyVhZ6r2vLnMLJfBPd53SB52T/3G36VI1/g2MZaX0cwDuXsfw==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/@vue/reactivity": {
            "version": "3.1.5",
            "resolved": "https://registry.npmjs.org/@vue/reactivity/-/reactivity-3.1.5.tgz",
            "integrity": "sha512-1tdfLmNjWG6t/CsPldh+foumYFo3cpyCHgBYQ34ylaMsJ+SNHQ1kApMIa8jN+i593zQuaw3AdWH0nJTARzCFhg==",
            "license": "MIT",
            "dependencies": {
                "@vue/shared": "3.1.5"
            }
        },
        "node_modules/@vue/shared": {
            "version": "3.1.5",
            "resolved": "https://registry.npmjs.org/@vue/shared/-/shared-3.1.5.tgz",
            "integrity": "sha512-oJ4F3TnvpXaQwZJNF3ZK+kLPHKarDmJjJ6jyzVNDKH9md1dptjC7lWR//jrGuLdek/U6iltWxqAnYOu8gCiOvA==",
            "license": "MIT"
        },
        "node_modules/alpinejs": {
            "version": "3.14.9",
            "resolved": "https://registry.npmjs.org/alpinejs/-/alpinejs-3.14.9.tgz",
            "integrity": "sha512-gqSOhTEyryU9FhviNqiHBHzgjkvtukq9tevew29fTj+ofZtfsYriw4zPirHHOAy9bw8QoL3WGhyk7QqCh5AYlw==",
            "license": "MIT",
            "dependencies": {
                "@vue/reactivity": "~3.1.1"
            }
        },
        "node_modules/ansi-regex": {
            "version": "5.0.1",
            "resolved": "https://registry.npmjs.org/ansi-regex/-/ansi-regex-5.0.1.tgz",
            "integrity": "sha512-quJQXlTSUGL2LH9SUXo8VwsY4soanhgo6LNSm84E1LBcE8s3O0wpdiRzyR9z/ZZJMlMWv37qOOb9pdJlMUEKFQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/ansi-styles": {
            "version": "4.3.0",
            "resolved": "https://registry.npmjs.org/ansi-styles/-/ansi-styles-4.3.0.tgz",
            "integrity": "sha512-zbB9rCJAT1rbjiVDb2hqKFHNYLxgtk8NURxZ3IZwD3F6NtxbXZQCnnSi1Lkx+IDohdPlFp222wVALIheZJQSEg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "color-convert": "^2.0.1"
            },
            "engines": {
                "node": ">=8"
            },
            "funding": {
                "url": "https://github.com/chalk/ansi-styles?sponsor=1"
            }
        },
        "node_modules/asynckit": {
            "version": "0.4.0",
            "resolved": "https://registry.npmjs.org/asynckit/-/asynckit-0.4.0.tgz",
            "integrity": "sha512-Oei9OH4tRh0YqU3GxhX79dM/mwVgvbZJaSNaRk+bshkj0S5cfHcgYakreBjrHwatXKbz+IoIdYLxrKim2MjW0Q==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/autoprefixer": {
            "version": "10.4.24",
            "resolved": "https://registry.npmjs.org/autoprefixer/-/autoprefixer-10.4.24.tgz",
            "integrity": "sha512-uHZg7N9ULTVbutaIsDRoUkoS8/h3bdsmVJYZ5l3wv8Cp/6UIIoRDm90hZ+BwxUj/hGBEzLxdHNSKuFpn8WOyZw==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/postcss/"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/autoprefixer"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "browserslist": "^4.28.1",
                "caniuse-lite": "^1.0.30001766",
                "fraction.js": "^5.3.4",
                "picocolors": "^1.1.1",
                "postcss-value-parser": "^4.2.0"
            },
            "bin": {
                "autoprefixer": "bin/autoprefixer"
            },
            "engines": {
                "node": "^10 || ^12 || >=14"
            },
            "peerDependencies": {
                "postcss": "^8.1.0"
            }
        },
        "node_modules/axios": {
            "version": "1.13.5",
            "resolved": "https://registry.npmjs.org/axios/-/axios-1.13.5.tgz",
            "integrity": "sha512-cz4ur7Vb0xS4/KUN0tPWe44eqxrIu31me+fbang3ijiNscE129POzipJJA6zniq2C/Z6sJCjMimjS8Lc/GAs8Q==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "follow-redirects": "^1.15.11",
                "form-data": "^4.0.5",
                "proxy-from-env": "^1.1.0"
            }
        },
        "node_modules/base64-arraybuffer": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/base64-arraybuffer/-/base64-arraybuffer-1.0.2.tgz",
            "integrity": "sha512-I3yl4r9QB5ZRY3XuJVEPfc2XhZO6YweFPI+UovAzn+8/hb3oJ6lnysaFcjVpkCPfVWFUDvoZ8kmVDP7WyRtYtQ==",
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": ">= 0.6.0"
            }
        },
        "node_modules/baseline-browser-mapping": {
            "version": "2.9.19",
            "resolved": "https://registry.npmjs.org/baseline-browser-mapping/-/baseline-browser-mapping-2.9.19.tgz",
            "integrity": "sha512-ipDqC8FrAl/76p2SSWKSI+H9tFwm7vYqXQrItCuiVPt26Km0jS+NzSsBWAaBusvSbQcfJG+JitdMm+wZAgTYqg==",
            "dev": true,
            "license": "Apache-2.0",
            "bin": {
                "baseline-browser-mapping": "dist/cli.js"
            }
        },
        "node_modules/browserslist": {
            "version": "4.28.1",
            "resolved": "https://registry.npmjs.org/browserslist/-/browserslist-4.28.1.tgz",
            "integrity": "sha512-ZC5Bd0LgJXgwGqUknZY/vkUQ04r8NXnJZ3yYi4vDmSiZmC/pdSN0NbNRPxZpbtO4uAfDUAFffO8IZoM3Gj8IkA==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/browserslist"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/browserslist"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "baseline-browser-mapping": "^2.9.0",
                "caniuse-lite": "^1.0.30001759",
                "electron-to-chromium": "^1.5.263",
                "node-releases": "^2.0.27",
                "update-browserslist-db": "^1.2.0"
            },
            "bin": {
                "browserslist": "cli.js"
            },
            "engines": {
                "node": "^6 || ^7 || ^8 || ^9 || ^10 || ^11 || ^12 || >=13.7"
            }
        },
        "node_modules/call-bind-apply-helpers": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/call-bind-apply-helpers/-/call-bind-apply-helpers-1.0.2.tgz",
            "integrity": "sha512-Sp1ablJ0ivDkSzjcaJdxEunN5/XvksFJ2sMBFfq6x0ryhQV/2b/KwFe21cMpmHtPOSij8K99/wSfoEuTObmuMQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "es-errors": "^1.3.0",
                "function-bind": "^1.1.2"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/caniuse-lite": {
            "version": "1.0.30001769",
            "resolved": "https://registry.npmjs.org/caniuse-lite/-/caniuse-lite-1.0.30001769.tgz",
            "integrity": "sha512-BCfFL1sHijQlBGWBMuJyhZUhzo7wer5sVj9hqekB/7xn0Ypy+pER/edCYQm4exbXj4WiySGp40P8UuTh6w1srg==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/browserslist"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/caniuse-lite"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "CC-BY-4.0"
        },
        "node_modules/canvg": {
            "version": "3.0.11",
            "resolved": "https://registry.npmjs.org/canvg/-/canvg-3.0.11.tgz",
            "integrity": "sha512-5ON+q7jCTgMp9cjpu4Jo6XbvfYwSB2Ow3kzHKfIyJfaCAOHLbdKPQqGKgfED/R5B+3TFFfe8pegYA+b423SRyA==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "@babel/runtime": "^7.12.5",
                "@types/raf": "^3.4.0",
                "core-js": "^3.8.3",
                "raf": "^3.4.1",
                "regenerator-runtime": "^0.13.7",
                "rgbcolor": "^1.0.1",
                "stackblur-canvas": "^2.0.0",
                "svg-pathdata": "^6.0.3"
            },
            "engines": {
                "node": ">=10.0.0"
            }
        },
        "node_modules/chalk": {
            "version": "4.1.2",
            "resolved": "https://registry.npmjs.org/chalk/-/chalk-4.1.2.tgz",
            "integrity": "sha512-oKnbhFyRIXpUuez8iBMmyEa4nbj4IOQyuhc/wy9kY7/WVPcwIO9VA668Pu8RkO7+0G76SLROeyw9CpQ061i4mA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "ansi-styles": "^4.1.0",
                "supports-color": "^7.1.0"
            },
            "engines": {
                "node": ">=10"
            },
            "funding": {
                "url": "https://github.com/chalk/chalk?sponsor=1"
            }
        },
        "node_modules/chalk/node_modules/supports-color": {
            "version": "7.2.0",
            "resolved": "https://registry.npmjs.org/supports-color/-/supports-color-7.2.0.tgz",
            "integrity": "sha512-qpCAvRl9stuOHveKsn7HncJRvv501qIacKzQlO/+Lwxc9+0q2wLyv4Dfvt80/DPn2pqOBsJdDiogXGR9+OvwRw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "has-flag": "^4.0.0"
            },
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/cliui": {
            "version": "8.0.1",
            "resolved": "https://registry.npmjs.org/cliui/-/cliui-8.0.1.tgz",
            "integrity": "sha512-BSeNnyus75C4//NQ9gQt1/csTXyo/8Sb+afLAkzAptFuMsod9HFokGNudZpi/oQV73hnVK+sR+5PVRMd+Dr7YQ==",
            "dev": true,
            "license": "ISC",
            "dependencies": {
                "string-width": "^4.2.0",
                "strip-ansi": "^6.0.1",
                "wrap-ansi": "^7.0.0"
            },
            "engines": {
                "node": ">=12"
            }
        },
        "node_modules/color-convert": {
            "version": "2.0.1",
            "resolved": "https://registry.npmjs.org/color-convert/-/color-convert-2.0.1.tgz",
            "integrity": "sha512-RRECPsj7iu/xb5oKYcsFHSppFNnsj/52OVTRKb4zP5onXwVF3zVmmToNcOfGC+CRDpfK/U584fMg38ZHCaElKQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "color-name": "~1.1.4"
            },
            "engines": {
                "node": ">=7.0.0"
            }
        },
        "node_modules/color-name": {
            "version": "1.1.4",
            "resolved": "https://registry.npmjs.org/color-name/-/color-name-1.1.4.tgz",
            "integrity": "sha512-dOy+3AuW3a2wNbZHIuMZpTcgjGuLU/uBL/ubcZF9OXbDo8ff4O8yVp5Bf0efS8uEoYo5q4Fx7dY9OgQGXgAsQA==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/combined-stream": {
            "version": "1.0.8",
            "resolved": "https://registry.npmjs.org/combined-stream/-/combined-stream-1.0.8.tgz",
            "integrity": "sha512-FQN4MRfuJeHf7cBbBMJFXhKSDq+2kAArBlmRBvcvFE5BB1HZKXtSFASDhdlz9zOYwxh8lDdnvmMOe/+5cdoEdg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "delayed-stream": "~1.0.0"
            },
            "engines": {
                "node": ">= 0.8"
            }
        },
        "node_modules/concurrently": {
            "version": "9.2.1",
            "resolved": "https://registry.npmjs.org/concurrently/-/concurrently-9.2.1.tgz",
            "integrity": "sha512-fsfrO0MxV64Znoy8/l1vVIjjHa29SZyyqPgQBwhiDcaW8wJc2W3XWVOGx4M3oJBnv/zdUZIIp1gDeS98GzP8Ng==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "chalk": "4.1.2",
                "rxjs": "7.8.2",
                "shell-quote": "1.8.3",
                "supports-color": "8.1.1",
                "tree-kill": "1.2.2",
                "yargs": "17.7.2"
            },
            "bin": {
                "conc": "dist/bin/concurrently.js",
                "concurrently": "dist/bin/concurrently.js"
            },
            "engines": {
                "node": ">=18"
            },
            "funding": {
                "url": "https://github.com/open-cli-tools/concurrently?sponsor=1"
            }
        },
        "node_modules/core-js": {
            "version": "3.50.0",
            "resolved": "https://registry.npmjs.org/core-js/-/core-js-3.50.0.tgz",
            "integrity": "sha512-BRWgOLKkFeCgRudR6zrs8p9XJZcE14grzKMMssoYrk6krtuEZ7MTKPIY5RzOnqsEKIR9kst7wNzphttraT+Yqw==",
            "hasInstallScript": true,
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": "*"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/core-js"
            }
        },
        "node_modules/css-line-break": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/css-line-break/-/css-line-break-2.1.0.tgz",
            "integrity": "sha512-FHcKFCZcAha3LwfVBhCQbW2nCNbkZXn7KVUJcsT5/P8YmfsVja0FMPJr0B903j/E69HUphKiV9iQArX8SDYA4w==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "utrie": "^1.0.2"
            }
        },
        "node_modules/delayed-stream": {
            "version": "1.0.0",
            "resolved": "https://registry.npmjs.org/delayed-stream/-/delayed-stream-1.0.0.tgz",
            "integrity": "sha512-ZySD7Nf91aLB0RxL4KGrKHBXl7Eds1DAmEdcoVawXnLD7SDhpNgtuII2aAkg7a7QS41jxPSZ17p4VdGnMHk3MQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=0.4.0"
            }
        },
        "node_modules/detect-libc": {
            "version": "2.1.2",
            "resolved": "https://registry.npmjs.org/detect-libc/-/detect-libc-2.1.2.tgz",
            "integrity": "sha512-Btj2BOOO83o3WyH59e8MgXsxEQVcarkUOpEYrubB0urwnN10yQ364rsiByU11nZlqWYZm05i/of7io4mzihBtQ==",
            "dev": true,
            "license": "Apache-2.0",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/dompurify": {
            "version": "3.4.16",
            "resolved": "https://registry.npmjs.org/dompurify/-/dompurify-3.4.16.tgz",
            "integrity": "sha512-sqo+pNp3qRhCIpbgRi1y8Tgk27Bo2Ry7w0dC1NBeNTdZChWjz9Xb/KOoZbRP/R6pQZ80Qw8YhXw13hWWBbMRnQ==",
            "license": "(MPL-2.0 OR Apache-2.0)",
            "optional": true,
            "optionalDependencies": {
                "@types/trusted-types": "^2.0.7"
            }
        },
        "node_modules/dunder-proto": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/dunder-proto/-/dunder-proto-1.0.1.tgz",
            "integrity": "sha512-KIN/nDJBQRcXw0MLVhZE9iQHmG68qAVIBg9CqmUYjmQIhgij9U5MFvrqkUL5FbtyyzZuOeOt0zdeRe4UY7ct+A==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "call-bind-apply-helpers": "^1.0.1",
                "es-errors": "^1.3.0",
                "gopd": "^1.2.0"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/electron-to-chromium": {
            "version": "1.5.286",
            "resolved": "https://registry.npmjs.org/electron-to-chromium/-/electron-to-chromium-1.5.286.tgz",
            "integrity": "sha512-9tfDXhJ4RKFNerfjdCcZfufu49vg620741MNs26a9+bhLThdB+plgMeou98CAaHu/WATj2iHOOHTp1hWtABj2A==",
            "dev": true,
            "license": "ISC"
        },
        "node_modules/emoji-regex": {
            "version": "8.0.0",
            "resolved": "https://registry.npmjs.org/emoji-regex/-/emoji-regex-8.0.0.tgz",
            "integrity": "sha512-MSjYzcWNOA0ewAHpz0MxpYFvwg6yjy1NG3xteoqz644VCo/RPgnr1/GGt+ic3iJTzQ8Eu3TdM14SawnVUmGE6A==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/enhanced-resolve": {
            "version": "5.19.0",
            "resolved": "https://registry.npmjs.org/enhanced-resolve/-/enhanced-resolve-5.19.0.tgz",
            "integrity": "sha512-phv3E1Xl4tQOShqSte26C7Fl84EwUdZsyOuSSk9qtAGyyQs2s3jJzComh+Abf4g187lUUAvH+H26omrqia2aGg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "graceful-fs": "^4.2.4",
                "tapable": "^2.3.0"
            },
            "engines": {
                "node": ">=10.13.0"
            }
        },
        "node_modules/es-define-property": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/es-define-property/-/es-define-property-1.0.1.tgz",
            "integrity": "sha512-e3nRfgfUZ4rNGL232gUgX06QNyyez04KdjFrF+LTRoOXmrOgFKDg4BCdsjW8EnT69eqdYGmRpJwiPVYNrCaW3g==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/es-errors": {
            "version": "1.3.0",
            "resolved": "https://registry.npmjs.org/es-errors/-/es-errors-1.3.0.tgz",
            "integrity": "sha512-Zf5H2Kxt2xjTvbJvP2ZWLEICxA6j+hAmMzIlypy4xcBg1vKVnx89Wy0GbS+kf5cwCVFFzdCFh2XSCFNULS6csw==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/es-object-atoms": {
            "version": "1.1.1",
            "resolved": "https://registry.npmjs.org/es-object-atoms/-/es-object-atoms-1.1.1.tgz",
            "integrity": "sha512-FGgH2h8zKNim9ljj7dankFPcICIK9Cp5bm+c2gQSYePhpaG5+esrLODihIorn+Pe6FGJzWhXQotPv73jTaldXA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "es-errors": "^1.3.0"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/es-set-tostringtag": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/es-set-tostringtag/-/es-set-tostringtag-2.1.0.tgz",
            "integrity": "sha512-j6vWzfrGVfyXxge+O0x5sh6cvxAog0a/4Rdd2K36zCMV5eJ+/+tOAngRO8cODMNWbVRdVlmGZQL2YS3yR8bIUA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "es-errors": "^1.3.0",
                "get-intrinsic": "^1.2.6",
                "has-tostringtag": "^1.0.2",
                "hasown": "^2.0.2"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/esbuild": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/esbuild/-/esbuild-0.27.3.tgz",
            "integrity": "sha512-8VwMnyGCONIs6cWue2IdpHxHnAjzxnw2Zr7MkVxB2vjmQ2ivqGFb4LEG3SMnv0Gb2F/G/2yA8zUaiL1gywDCCg==",
            "dev": true,
            "hasInstallScript": true,
            "license": "MIT",
            "bin": {
                "esbuild": "bin/esbuild"
            },
            "engines": {
                "node": ">=18"
            },
            "optionalDependencies": {
                "@esbuild/aix-ppc64": "0.27.3",
                "@esbuild/android-arm": "0.27.3",
                "@esbuild/android-arm64": "0.27.3",
                "@esbuild/android-x64": "0.27.3",
                "@esbuild/darwin-arm64": "0.27.3",
                "@esbuild/darwin-x64": "0.27.3",
                "@esbuild/freebsd-arm64": "0.27.3",
                "@esbuild/freebsd-x64": "0.27.3",
                "@esbuild/linux-arm": "0.27.3",
                "@esbuild/linux-arm64": "0.27.3",
                "@esbuild/linux-ia32": "0.27.3",
                "@esbuild/linux-loong64": "0.27.3",
                "@esbuild/linux-mips64el": "0.27.3",
                "@esbuild/linux-ppc64": "0.27.3",
                "@esbuild/linux-riscv64": "0.27.3",
                "@esbuild/linux-s390x": "0.27.3",
                "@esbuild/linux-x64": "0.27.3",
                "@esbuild/netbsd-arm64": "0.27.3",
                "@esbuild/netbsd-x64": "0.27.3",
                "@esbuild/openbsd-arm64": "0.27.3",
                "@esbuild/openbsd-x64": "0.27.3",
                "@esbuild/openharmony-arm64": "0.27.3",
                "@esbuild/sunos-x64": "0.27.3",
                "@esbuild/win32-arm64": "0.27.3",
                "@esbuild/win32-ia32": "0.27.3",
                "@esbuild/win32-x64": "0.27.3"
            }
        },
        "node_modules/escalade": {
            "version": "3.2.0",
            "resolved": "https://registry.npmjs.org/escalade/-/escalade-3.2.0.tgz",
            "integrity": "sha512-WUj2qlxaQtO4g6Pq5c29GTcWGDyd8itL8zTlipgECz3JesAiiOKotd8JU6otB3PACgG6xkJUyVhboMS+bje/jA==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=6"
            }
        },
        "node_modules/fast-png": {
            "version": "6.4.0",
            "resolved": "https://registry.npmjs.org/fast-png/-/fast-png-6.4.0.tgz",
            "integrity": "sha512-kAqZq1TlgBjZcLr5mcN6NP5Rv4V2f22z00c3g8vRrwkcqjerx7BEhPbOnWCPqaHUl2XWQBJQvOT/FQhdMT7X/Q==",
            "license": "MIT",
            "dependencies": {
                "@types/pako": "^2.0.3",
                "iobuffer": "^5.3.2",
                "pako": "^2.1.0"
            }
        },
        "node_modules/fdir": {
            "version": "6.5.0",
            "resolved": "https://registry.npmjs.org/fdir/-/fdir-6.5.0.tgz",
            "integrity": "sha512-tIbYtZbucOs0BRGqPJkshJUYdL+SDH7dVM8gjy+ERp3WAUjLEFJE+02kanyHtwjWOnwrKYBiwAmM0p4kLJAnXg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=12.0.0"
            },
            "peerDependencies": {
                "picomatch": "^3 || ^4"
            },
            "peerDependenciesMeta": {
                "picomatch": {
                    "optional": true
                }
            }
        },
        "node_modules/fflate": {
            "version": "0.8.3",
            "resolved": "https://registry.npmjs.org/fflate/-/fflate-0.8.3.tgz",
            "integrity": "sha512-tbZNuJrLwGUp3zshBtdy4W+ORxZuIh8a5ilyIEQDC5rY1f3U20JMry0Ll3WBzU58EZKsEuJFXhb5gwv8CsPvgA==",
            "license": "MIT"
        },
        "node_modules/follow-redirects": {
            "version": "1.15.11",
            "resolved": "https://registry.npmjs.org/follow-redirects/-/follow-redirects-1.15.11.tgz",
            "integrity": "sha512-deG2P0JfjrTxl50XGCDyfI97ZGVCxIpfKYmfyrQ54n5FO/0gfIES8C/Psl6kWVDolizcaaxZJnTS0QSMxvnsBQ==",
            "dev": true,
            "funding": [
                {
                    "type": "individual",
                    "url": "https://github.com/sponsors/RubenVerborgh"
                }
            ],
            "license": "MIT",
            "engines": {
                "node": ">=4.0"
            },
            "peerDependenciesMeta": {
                "debug": {
                    "optional": true
                }
            }
        },
        "node_modules/form-data": {
            "version": "4.0.5",
            "resolved": "https://registry.npmjs.org/form-data/-/form-data-4.0.5.tgz",
            "integrity": "sha512-8RipRLol37bNs2bhoV67fiTEvdTrbMUYcFTiy3+wuuOnUog2QBHCZWXDRijWQfAkhBj2Uf5UnVaiWwA5vdd82w==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "asynckit": "^0.4.0",
                "combined-stream": "^1.0.8",
                "es-set-tostringtag": "^2.1.0",
                "hasown": "^2.0.2",
                "mime-types": "^2.1.12"
            },
            "engines": {
                "node": ">= 6"
            }
        },
        "node_modules/fraction.js": {
            "version": "5.3.4",
            "resolved": "https://registry.npmjs.org/fraction.js/-/fraction.js-5.3.4.tgz",
            "integrity": "sha512-1X1NTtiJphryn/uLQz3whtY6jK3fTqoE3ohKs0tT+Ujr1W59oopxmoEh7Lu5p6vBaPbgoM0bzveAW4Qi5RyWDQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": "*"
            },
            "funding": {
                "type": "github",
                "url": "https://github.com/sponsors/rawify"
            }
        },
        "node_modules/fsevents": {
            "version": "2.3.3",
            "resolved": "https://registry.npmjs.org/fsevents/-/fsevents-2.3.3.tgz",
            "integrity": "sha512-5xoDfX+fL7faATnagmWPpbFtwh/R77WmMMqqHGS65C3vvB0YHrgF+B1YmZ3441tMj5n63k0212XNoJwzlhffQw==",
            "dev": true,
            "hasInstallScript": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": "^8.16.0 || ^10.6.0 || >=11.0.0"
            }
        },
        "node_modules/function-bind": {
            "version": "1.1.2",
            "resolved": "https://registry.npmjs.org/function-bind/-/function-bind-1.1.2.tgz",
            "integrity": "sha512-7XHNxH7qX9xG5mIwxkhumTox/MIRNcOgDrxWsMt2pAr23WHp6MrRlN7FBSFpCpr+oVO0F744iUgR82nJMfG2SA==",
            "dev": true,
            "license": "MIT",
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/get-caller-file": {
            "version": "2.0.5",
            "resolved": "https://registry.npmjs.org/get-caller-file/-/get-caller-file-2.0.5.tgz",
            "integrity": "sha512-DyFP3BM/3YHTQOCUL/w0OZHR0lpKeGrxotcHWcqNEdnltqFwXVfhEBQ94eIo34AfQpo0rGki4cyIiftY06h2Fg==",
            "dev": true,
            "license": "ISC",
            "engines": {
                "node": "6.* || 8.* || >= 10.*"
            }
        },
        "node_modules/get-intrinsic": {
            "version": "1.3.0",
            "resolved": "https://registry.npmjs.org/get-intrinsic/-/get-intrinsic-1.3.0.tgz",
            "integrity": "sha512-9fSjSaos/fRIVIp+xSJlE6lfwhES7LNtKaCBIamHsjr2na1BiABJPo0mOjjz8GJDURarmCPGqaiVg5mfjb98CQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "call-bind-apply-helpers": "^1.0.2",
                "es-define-property": "^1.0.1",
                "es-errors": "^1.3.0",
                "es-object-atoms": "^1.1.1",
                "function-bind": "^1.1.2",
                "get-proto": "^1.0.1",
                "gopd": "^1.2.0",
                "has-symbols": "^1.1.0",
                "hasown": "^2.0.2",
                "math-intrinsics": "^1.1.0"
            },
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/get-proto": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/get-proto/-/get-proto-1.0.1.tgz",
            "integrity": "sha512-sTSfBjoXBp89JvIKIefqw7U2CCebsc74kiY6awiGogKtoSGbgjYE/G/+l9sF3MWFPNc9IcoOC4ODfKHfxFmp0g==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "dunder-proto": "^1.0.1",
                "es-object-atoms": "^1.0.0"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/gopd": {
            "version": "1.2.0",
            "resolved": "https://registry.npmjs.org/gopd/-/gopd-1.2.0.tgz",
            "integrity": "sha512-ZUKRh6/kUFoAiTAtTYPZJ3hw9wNxx+BIBOijnlG9PnrJsCcSjs1wyyD6vJpaYtgnzDrKYRSqf3OO6Rfa93xsRg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/graceful-fs": {
            "version": "4.2.11",
            "resolved": "https://registry.npmjs.org/graceful-fs/-/graceful-fs-4.2.11.tgz",
            "integrity": "sha512-RbJ5/jmFcNNCcDV5o9eTnBLJ/HszWV0P73bc+Ff4nS/rJj+YaS6IGyiOL0VoBYX+l1Wrl3k63h/KrH+nhJ0XvQ==",
            "dev": true,
            "license": "ISC"
        },
        "node_modules/has-flag": {
            "version": "4.0.0",
            "resolved": "https://registry.npmjs.org/has-flag/-/has-flag-4.0.0.tgz",
            "integrity": "sha512-EykJT/Q1KjTWctppgIAgfSO0tKVuZUjhgMr17kqTumMl6Afv3EISleU7qZUzoXDFTAHTDC4NOoG/ZxU3EvlMPQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/has-symbols": {
            "version": "1.1.0",
            "resolved": "https://registry.npmjs.org/has-symbols/-/has-symbols-1.1.0.tgz",
            "integrity": "sha512-1cDNdwJ2Jaohmb3sg4OmKaMBwuC48sYni5HUw2DvsC8LjGTLK9h+eb1X6RyuOHe4hT0ULCW68iomhjUoKUqlPQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/has-tostringtag": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/has-tostringtag/-/has-tostringtag-1.0.2.tgz",
            "integrity": "sha512-NqADB8VjPFLM2V0VvHUewwwsw0ZWBaIdgo+ieHtK3hasLz4qeCRjYcqfB6AQrBggRKppKF8L52/VqdVsO47Dlw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "has-symbols": "^1.0.3"
            },
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/hasown": {
            "version": "2.0.2",
            "resolved": "https://registry.npmjs.org/hasown/-/hasown-2.0.2.tgz",
            "integrity": "sha512-0hJU9SCPvmMzIBdZFqNPXWa6dqh7WdH0cII9y+CyS8rG3nL48Bclra9HmKhVVUHyPWNH5Y7xDwAB7bfgSjkUMQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "function-bind": "^1.1.2"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/html2canvas": {
            "version": "1.4.1",
            "resolved": "https://registry.npmjs.org/html2canvas/-/html2canvas-1.4.1.tgz",
            "integrity": "sha512-fPU6BHNpsyIhr8yyMpTLLxAbkaK8ArIBcmZIRiBLiDhjeqvXolaEmDGmELFuX9I4xDcaKKcJl+TKZLqruBbmWA==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "css-line-break": "^2.1.0",
                "text-segmentation": "^1.0.3"
            },
            "engines": {
                "node": ">=8.0.0"
            }
        },
        "node_modules/iobuffer": {
            "version": "5.4.0",
            "resolved": "https://registry.npmjs.org/iobuffer/-/iobuffer-5.4.0.tgz",
            "integrity": "sha512-DRebOWuqDvxunfkNJAlc3IzWIPD5xVxwUNbHr7xKB8E6aLJxIPfNX3CoMJghcFjpv6RWQsrcJbghtEwSPoJqMA==",
            "license": "MIT"
        },
        "node_modules/is-fullwidth-code-point": {
            "version": "3.0.0",
            "resolved": "https://registry.npmjs.org/is-fullwidth-code-point/-/is-fullwidth-code-point-3.0.0.tgz",
            "integrity": "sha512-zymm5+u+sCsSWyD9qNaejV3DFvhCKclKdizYaJUuHA83RLjb7nSuGnddCHGv0hk+KY7BMAlsWeK4Ueg6EV6XQg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/jiti": {
            "version": "2.6.1",
            "resolved": "https://registry.npmjs.org/jiti/-/jiti-2.6.1.tgz",
            "integrity": "sha512-ekilCSN1jwRvIbgeg/57YFh8qQDNbwDb9xT/qu2DAHbFFZUicIl4ygVaAvzveMhMVr3LnpSKTNnwt8PoOfmKhQ==",
            "dev": true,
            "license": "MIT",
            "bin": {
                "jiti": "lib/jiti-cli.mjs"
            }
        },
        "node_modules/jspdf": {
            "version": "3.0.4",
            "resolved": "https://registry.npmjs.org/jspdf/-/jspdf-3.0.4.tgz",
            "integrity": "sha512-dc6oQ8y37rRcHn316s4ngz/nOjayLF/FFxBF4V9zamQKRqXxyiH1zagkCdktdWhtoQId5K20xt1lB90XzkB+hQ==",
            "license": "MIT",
            "dependencies": {
                "@babel/runtime": "^7.28.4",
                "fast-png": "^6.2.0",
                "fflate": "^0.8.1"
            },
            "optionalDependencies": {
                "canvg": "^3.0.11",
                "core-js": "^3.6.0",
                "dompurify": "^3.2.4",
                "html2canvas": "^1.0.0-rc.5"
            }
        },
        "node_modules/jspdf-autotable": {
            "version": "5.0.2",
            "resolved": "https://registry.npmjs.org/jspdf-autotable/-/jspdf-autotable-5.0.2.tgz",
            "integrity": "sha512-YNKeB7qmx3pxOLcNeoqAv3qTS7KuvVwkFe5AduCawpop3NOkBUtqDToxNc225MlNecxT4kP2Zy3z/y/yvGdXUQ==",
            "license": "MIT",
            "peerDependencies": {
                "jspdf": "^2 || ^3"
            }
        },
        "node_modules/laravel-vite-plugin": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/laravel-vite-plugin/-/laravel-vite-plugin-2.1.0.tgz",
            "integrity": "sha512-z+ck2BSV6KWtYcoIzk9Y5+p4NEjqM+Y4i8/H+VZRLq0OgNjW2DqyADquwYu5j8qRvaXwzNmfCWl1KrMlV1zpsg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "picocolors": "^1.0.0",
                "vite-plugin-full-reload": "^1.1.0"
            },
            "bin": {
                "clean-orphaned-assets": "bin/clean.js"
            },
            "engines": {
                "node": "^20.19.0 || >=22.12.0"
            },
            "peerDependencies": {
                "vite": "^7.0.0"
            }
        },
        "node_modules/lightningcss": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss/-/lightningcss-1.30.2.tgz",
            "integrity": "sha512-utfs7Pr5uJyyvDETitgsaqSyjCb2qNRAtuqUeWIAKztsOYdcACf2KtARYXg2pSvhkt+9NfoaNY7fxjl6nuMjIQ==",
            "dev": true,
            "license": "MPL-2.0",
            "dependencies": {
                "detect-libc": "^2.0.3"
            },
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            },
            "optionalDependencies": {
                "lightningcss-android-arm64": "1.30.2",
                "lightningcss-darwin-arm64": "1.30.2",
                "lightningcss-darwin-x64": "1.30.2",
                "lightningcss-freebsd-x64": "1.30.2",
                "lightningcss-linux-arm-gnueabihf": "1.30.2",
                "lightningcss-linux-arm64-gnu": "1.30.2",
                "lightningcss-linux-arm64-musl": "1.30.2",
                "lightningcss-linux-x64-gnu": "1.30.2",
                "lightningcss-linux-x64-musl": "1.30.2",
                "lightningcss-win32-arm64-msvc": "1.30.2",
                "lightningcss-win32-x64-msvc": "1.30.2"
            }
        },
        "node_modules/lightningcss-android-arm64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-android-arm64/-/lightningcss-android-arm64-1.30.2.tgz",
            "integrity": "sha512-BH9sEdOCahSgmkVhBLeU7Hc9DWeZ1Eb6wNS6Da8igvUwAe0sqROHddIlvU06q3WyXVEOYDZ6ykBZQnjTbmo4+A==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-darwin-arm64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-darwin-arm64/-/lightningcss-darwin-arm64-1.30.2.tgz",
            "integrity": "sha512-ylTcDJBN3Hp21TdhRT5zBOIi73P6/W0qwvlFEk22fkdXchtNTOU4Qc37SkzV+EKYxLouZ6M4LG9NfZ1qkhhBWA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-darwin-x64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-darwin-x64/-/lightningcss-darwin-x64-1.30.2.tgz",
            "integrity": "sha512-oBZgKchomuDYxr7ilwLcyms6BCyLn0z8J0+ZZmfpjwg9fRVZIR5/GMXd7r9RH94iDhld3UmSjBM6nXWM2TfZTQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-freebsd-x64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-freebsd-x64/-/lightningcss-freebsd-x64-1.30.2.tgz",
            "integrity": "sha512-c2bH6xTrf4BDpK8MoGG4Bd6zAMZDAXS569UxCAGcA7IKbHNMlhGQ89eRmvpIUGfKWNVdbhSbkQaWhEoMGmGslA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-arm-gnueabihf": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-arm-gnueabihf/-/lightningcss-linux-arm-gnueabihf-1.30.2.tgz",
            "integrity": "sha512-eVdpxh4wYcm0PofJIZVuYuLiqBIakQ9uFZmipf6LF/HRj5Bgm0eb3qL/mr1smyXIS1twwOxNWndd8z0E374hiA==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-arm64-gnu": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-arm64-gnu/-/lightningcss-linux-arm64-gnu-1.30.2.tgz",
            "integrity": "sha512-UK65WJAbwIJbiBFXpxrbTNArtfuznvxAJw4Q2ZGlU8kPeDIWEX1dg3rn2veBVUylA2Ezg89ktszWbaQnxD/e3A==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-arm64-musl": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-arm64-musl/-/lightningcss-linux-arm64-musl-1.30.2.tgz",
            "integrity": "sha512-5Vh9dGeblpTxWHpOx8iauV02popZDsCYMPIgiuw97OJ5uaDsL86cnqSFs5LZkG3ghHoX5isLgWzMs+eD1YzrnA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-x64-gnu": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-x64-gnu/-/lightningcss-linux-x64-gnu-1.30.2.tgz",
            "integrity": "sha512-Cfd46gdmj1vQ+lR6VRTTadNHu6ALuw2pKR9lYq4FnhvgBc4zWY1EtZcAc6EffShbb1MFrIPfLDXD6Xprbnni4w==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-x64-musl": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-x64-musl/-/lightningcss-linux-x64-musl-1.30.2.tgz",
            "integrity": "sha512-XJaLUUFXb6/QG2lGIW6aIk6jKdtjtcffUT0NKvIqhSBY3hh9Ch+1LCeH80dR9q9LBjG3ewbDjnumefsLsP6aiA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-win32-arm64-msvc": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-win32-arm64-msvc/-/lightningcss-win32-arm64-msvc-1.30.2.tgz",
            "integrity": "sha512-FZn+vaj7zLv//D/192WFFVA0RgHawIcHqLX9xuWiQt7P0PtdFEVaxgF9rjM/IRYHQXNnk61/H/gb2Ei+kUQ4xQ==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-win32-x64-msvc": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-win32-x64-msvc/-/lightningcss-win32-x64-msvc-1.30.2.tgz",
            "integrity": "sha512-5g1yc73p+iAkid5phb4oVFMB45417DkRevRbt/El/gKXJk4jid+vPFF/AXbxn05Aky8PapwzZrdJShv5C0avjw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/magic-string": {
            "version": "0.30.21",
            "resolved": "https://registry.npmjs.org/magic-string/-/magic-string-0.30.21.tgz",
            "integrity": "sha512-vd2F4YUyEXKGcLHoq+TEyCjxueSeHnFxyyjNp80yg0XV4vUhnDer/lvvlqM/arB5bXQN5K2/3oinyCRyx8T2CQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/sourcemap-codec": "^1.5.5"
            }
        },
        "node_modules/math-intrinsics": {
            "version": "1.1.0",
            "resolved": "https://registry.npmjs.org/math-intrinsics/-/math-intrinsics-1.1.0.tgz",
            "integrity": "sha512-/IXtbwEk5HTPyEwyKX6hGkYXxM9nbj64B+ilVJnC/R6B0pH5G4V3b0pVbL7DBj4tkhBAppbQUlf6F6Xl9LHu1g==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/mime-db": {
            "version": "1.52.0",
            "resolved": "https://registry.npmjs.org/mime-db/-/mime-db-1.52.0.tgz",
            "integrity": "sha512-sPU4uV7dYlvtWJxwwxHD0PuihVNiE7TyAbQ5SWxDCB9mUYvOgroQOwYQQOKPJ8CIbE+1ETVlOoK1UC2nU3gYvg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.6"
            }
        },
        "node_modules/mime-types": {
            "version": "2.1.35",
            "resolved": "https://registry.npmjs.org/mime-types/-/mime-types-2.1.35.tgz",
            "integrity": "sha512-ZDY+bPm5zTTF+YpCrAU9nK0UgICYPT0QtT1NZWFv4s++TNkcgVaT0g6+4R2uI4MjQjzysHB1zxuWL50hzaeXiw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "mime-db": "1.52.0"
            },
            "engines": {
                "node": ">= 0.6"
            }
        },
        "node_modules/nanoid": {
            "version": "3.3.11",
            "resolved": "https://registry.npmjs.org/nanoid/-/nanoid-3.3.11.tgz",
            "integrity": "sha512-N8SpfPUnUp1bK+PMYW8qSWdl9U+wwNWI4QKxOYDy9JAro3WMX7p2OeVRF9v+347pnakNevPmiHhNmZ2HbFA76w==",
            "dev": true,
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "bin": {
                "nanoid": "bin/nanoid.cjs"
            },
            "engines": {
                "node": "^10 || ^12 || ^13.7 || ^14 || >=15.0.1"
            }
        },
        "node_modules/node-releases": {
            "version": "2.0.27",
            "resolved": "https://registry.npmjs.org/node-releases/-/node-releases-2.0.27.tgz",
            "integrity": "sha512-nmh3lCkYZ3grZvqcCH+fjmQ7X+H0OeZgP40OierEaAptX4XofMh5kwNbWh7lBduUzCcV/8kZ+NDLCwm2iorIlA==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/pako": {
            "version": "2.2.0",
            "resolved": "https://registry.npmjs.org/pako/-/pako-2.2.0.tgz",
            "integrity": "sha512-zJq6RP/5q+TO2OpFV3FHzlPnFjmkb7Nc99a5SNjJE+uu/PkpChs+NIZSSzbBoD+6kjiISXjfYdwj1ZRQ81dz/w==",
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/puzrin"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/nodeca"
                }
            ],
            "license": "(MIT AND Zlib)"
        },
        "node_modules/performance-now": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/performance-now/-/performance-now-2.1.0.tgz",
            "integrity": "sha512-7EAHlyLHI56VEIdK57uwHdHKIaAGbnXPiw0yWbarQZOKaKpvUIgW0jWRVLiatnM+XXlSwsanIBH/hzGMJulMow==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/picocolors": {
            "version": "1.1.1",
            "resolved": "https://registry.npmjs.org/picocolors/-/picocolors-1.1.1.tgz",
            "integrity": "sha512-xceH2snhtb5M9liqDsmEw56le376mTZkEX/jEb/RxNFyegNul7eNslCXP9FDj/Lcu0X8KEyMceP2ntpaHrDEVA==",
            "dev": true,
            "license": "ISC"
        },
        "node_modules/picomatch": {
            "version": "4.0.3",
            "resolved": "https://registry.npmjs.org/picomatch/-/picomatch-4.0.3.tgz",
            "integrity": "sha512-5gTmgEY/sqK6gFXLIsQNH19lWb4ebPDLA4SdLP7dsWkIXHWlG66oPuVvXSGFPppYZz8ZDZq0dYYrbHfBCVUb1Q==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=12"
            },
            "funding": {
                "url": "https://github.com/sponsors/jonschlinkert"
            }
        },
        "node_modules/postcss": {
            "version": "8.5.6",
            "resolved": "https://registry.npmjs.org/postcss/-/postcss-8.5.6.tgz",
            "integrity": "sha512-3Ybi1tAuwAP9s0r1UQ2J4n5Y0G05bJkpUIO0/bI9MhwmD70S5aTWbXGBwxHrelT+XM1k6dM0pk+SwNkpTRN7Pg==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/postcss/"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/postcss"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "nanoid": "^3.3.11",
                "picocolors": "^1.1.1",
                "source-map-js": "^1.2.1"
            },
            "engines": {
                "node": "^10 || ^12 || >=14"
            }
        },
        "node_modules/postcss-value-parser": {
            "version": "4.2.0",
            "resolved": "https://registry.npmjs.org/postcss-value-parser/-/postcss-value-parser-4.2.0.tgz",
            "integrity": "sha512-1NNCs6uurfkVbeXG4S8JFT9t19m45ICnif8zWLd5oPSZ50QnwMfK+H3jv408d4jw/7Bttv5axS5IiHoLaVNHeQ==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/proxy-from-env": {
            "version": "1.1.0",
            "resolved": "https://registry.npmjs.org/proxy-from-env/-/proxy-from-env-1.1.0.tgz",
            "integrity": "sha512-D+zkORCbA9f1tdWRK0RaCR3GPv50cMxcrz4X8k5LTSUD1Dkw47mKJEZQNunItRTkWwgtaUSo1RVFRIG9ZXiFYg==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/raf": {
            "version": "3.4.1",
            "resolved": "https://registry.npmjs.org/raf/-/raf-3.4.1.tgz",
            "integrity": "sha512-Sq4CW4QhwOHE8ucn6J34MqtZCeWFP2aQSmrlroYgqAV1PjStIhJXxYuTgUIfkEk7zTLjmIjLmU5q+fbD1NnOJA==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "performance-now": "^2.1.0"
            }
        },
        "node_modules/regenerator-runtime": {
            "version": "0.13.11",
            "resolved": "https://registry.npmjs.org/regenerator-runtime/-/regenerator-runtime-0.13.11.tgz",
            "integrity": "sha512-kY1AZVr2Ra+t+piVaJ4gxaFaReZVH40AKNo7UCX6W+dEwBo/2oZJzqfuN1qLq1oL45o56cPaTXELwrTh8Fpggg==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/require-directory": {
            "version": "2.1.1",
            "resolved": "https://registry.npmjs.org/require-directory/-/require-directory-2.1.1.tgz",
            "integrity": "sha512-fGxEI7+wsG9xrvdjsrlmL22OMTTiHRwAMroiEeMgq8gzoLC/PQr7RsRDSTLUg/bZAZtF+TVIkHc6/4RIKrui+Q==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=0.10.0"
            }
        },
        "node_modules/rgbcolor": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/rgbcolor/-/rgbcolor-1.0.1.tgz",
            "integrity": "sha512-9aZLIrhRaD97sgVhtJOW6ckOEh6/GnvQtdVNfdZ6s67+3/XwLS9lBcQYzEEhYVeUowN7pRzMLsyGhK2i/xvWbw==",
            "license": "MIT OR SEE LICENSE IN FEEL-FREE.md",
            "optional": true,
            "engines": {
                "node": ">= 0.8.15"
            }
        },
        "node_modules/rollup": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/rollup/-/rollup-4.57.1.tgz",
            "integrity": "sha512-oQL6lgK3e2QZeQ7gcgIkS2YZPg5slw37hYufJ3edKlfQSGGm8ICoxswK15ntSzF/a8+h7ekRy7k7oWc3BQ7y8A==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@types/estree": "1.0.8"
            },
            "bin": {
                "rollup": "dist/bin/rollup"
            },
            "engines": {
                "node": ">=18.0.0",
                "npm": ">=8.0.0"
            },
            "optionalDependencies": {
                "@rollup/rollup-android-arm-eabi": "4.57.1",
                "@rollup/rollup-android-arm64": "4.57.1",
                "@rollup/rollup-darwin-arm64": "4.57.1",
                "@rollup/rollup-darwin-x64": "4.57.1",
                "@rollup/rollup-freebsd-arm64": "4.57.1",
                "@rollup/rollup-freebsd-x64": "4.57.1",
                "@rollup/rollup-linux-arm-gnueabihf": "4.57.1",
                "@rollup/rollup-linux-arm-musleabihf": "4.57.1",
                "@rollup/rollup-linux-arm64-gnu": "4.57.1",
                "@rollup/rollup-linux-arm64-musl": "4.57.1",
                "@rollup/rollup-linux-loong64-gnu": "4.57.1",
                "@rollup/rollup-linux-loong64-musl": "4.57.1",
                "@rollup/rollup-linux-ppc64-gnu": "4.57.1",
                "@rollup/rollup-linux-ppc64-musl": "4.57.1",
                "@rollup/rollup-linux-riscv64-gnu": "4.57.1",
                "@rollup/rollup-linux-riscv64-musl": "4.57.1",
                "@rollup/rollup-linux-s390x-gnu": "4.57.1",
                "@rollup/rollup-linux-x64-gnu": "4.57.1",
                "@rollup/rollup-linux-x64-musl": "4.57.1",
                "@rollup/rollup-openbsd-x64": "4.57.1",
                "@rollup/rollup-openharmony-arm64": "4.57.1",
                "@rollup/rollup-win32-arm64-msvc": "4.57.1",
                "@rollup/rollup-win32-ia32-msvc": "4.57.1",
                "@rollup/rollup-win32-x64-gnu": "4.57.1",
                "@rollup/rollup-win32-x64-msvc": "4.57.1",
                "fsevents": "~2.3.2"
            }
        },
        "node_modules/rxjs": {
            "version": "7.8.2",
            "resolved": "https://registry.npmjs.org/rxjs/-/rxjs-7.8.2.tgz",
            "integrity": "sha512-dhKf903U/PQZY6boNNtAGdWbG85WAbjT/1xYoZIC7FAY0yWapOBQVsVrDl58W86//e1VpMNBtRV4MaXfdMySFA==",
            "dev": true,
            "license": "Apache-2.0",
            "dependencies": {
                "tslib": "^2.1.0"
            }
        },
        "node_modules/shell-quote": {
            "version": "1.8.3",
            "resolved": "https://registry.npmjs.org/shell-quote/-/shell-quote-1.8.3.tgz",
            "integrity": "sha512-ObmnIF4hXNg1BqhnHmgbDETF8dLPCggZWBjkQfhZpbszZnYur5DUljTcCHii5LC3J5E0yeO/1LIMyH+UvHQgyw==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/source-map-js": {
            "version": "1.2.1",
            "resolved": "https://registry.npmjs.org/source-map-js/-/source-map-js-1.2.1.tgz",
            "integrity": "sha512-UXWMKhLOwVKb728IUtQPXxfYU+usdybtUrK/8uGE8CQMvrhOpwvzDBwj0QhSL7MQc7vIsISBG8VQ8+IDQxpfQA==",
            "dev": true,
            "license": "BSD-3-Clause",
            "engines": {
                "node": ">=0.10.0"
            }
        },
        "node_modules/stackblur-canvas": {
            "version": "2.7.0",
            "resolved": "https://registry.npmjs.org/stackblur-canvas/-/stackblur-canvas-2.7.0.tgz",
            "integrity": "sha512-yf7OENo23AGJhBriGx0QivY5JP6Y1HbrrDI6WLt6C5auYZXlQrheoY8hD4ibekFKz1HOfE48Ww8kMWMnJD/zcQ==",
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": ">=0.1.14"
            }
        },
        "node_modules/string-width": {
            "version": "4.2.3",
            "resolved": "https://registry.npmjs.org/string-width/-/string-width-4.2.3.tgz",
            "integrity": "sha512-wKyQRQpjJ0sIp62ErSZdGsjMJWsap5oRNihHhu6G7JVO/9jIB6UyevL+tXuOqrng8j/cxKTWyWUwvSTriiZz/g==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "emoji-regex": "^8.0.0",
                "is-fullwidth-code-point": "^3.0.0",
                "strip-ansi": "^6.0.1"
            },
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/strip-ansi": {
            "version": "6.0.1",
            "resolved": "https://registry.npmjs.org/strip-ansi/-/strip-ansi-6.0.1.tgz",
            "integrity": "sha512-Y38VPSHcqkFrCpFnQ9vuSXmquuv5oXOKpGeT6aGrr3o3Gc9AlVa6JBfUSOCnbxGGZF+/0ooI7KrPuUSztUdU5A==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "ansi-regex": "^5.0.1"
            },
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/supports-color": {
            "version": "8.1.1",
            "resolved": "https://registry.npmjs.org/supports-color/-/supports-color-8.1.1.tgz",
            "integrity": "sha512-MpUEN2OodtUzxvKQl72cUF7RQ5EiHsGvSsVG0ia9c5RbWGL2CI4C7EpPS8UTBIplnlzZiNuV56w+FuNxy3ty2Q==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "has-flag": "^4.0.0"
            },
            "engines": {
                "node": ">=10"
            },
            "funding": {
                "url": "https://github.com/chalk/supports-color?sponsor=1"
            }
        },
        "node_modules/svg-pathdata": {
            "version": "6.0.3",
            "resolved": "https://registry.npmjs.org/svg-pathdata/-/svg-pathdata-6.0.3.tgz",
            "integrity": "sha512-qsjeeq5YjBZ5eMdFuUa4ZosMLxgr5RZ+F+Y1OrDhuOCEInRMA3x74XdBtggJcj9kOeInz0WE+LgCPDkZFlBYJw==",
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": ">=12.0.0"
            }
        },
        "node_modules/tailwindcss": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/tailwindcss/-/tailwindcss-4.1.18.tgz",
            "integrity": "sha512-4+Z+0yiYyEtUVCScyfHCxOYP06L5Ne+JiHhY2IjR2KWMIWhJOYZKLSGZaP5HkZ8+bY0cxfzwDE5uOmzFXyIwxw==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/tapable": {
            "version": "2.3.0",
            "resolved": "https://registry.npmjs.org/tapable/-/tapable-2.3.0.tgz",
            "integrity": "sha512-g9ljZiwki/LfxmQADO3dEY1CbpmXT5Hm2fJ+QaGKwSXUylMybePR7/67YW7jOrrvjEgL1Fmz5kzyAjWVWLlucg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=6"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/webpack"
            }
        },
        "node_modules/text-segmentation": {
            "version": "1.0.3",
            "resolved": "https://registry.npmjs.org/text-segmentation/-/text-segmentation-1.0.3.tgz",
            "integrity": "sha512-iOiPUo/BGnZ6+54OsWxZidGCsdU8YbE4PSpdPinp7DeMtUJNJBoJ/ouUSTJjHkh1KntHaltHl/gDs2FC4i5+Nw==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "utrie": "^1.0.2"
            }
        },
        "node_modules/tinyglobby": {
            "version": "0.2.15",
            "resolved": "https://registry.npmjs.org/tinyglobby/-/tinyglobby-0.2.15.tgz",
            "integrity": "sha512-j2Zq4NyQYG5XMST4cbs02Ak8iJUdxRM0XI5QyxXuZOzKOINmWurp3smXu3y5wDcJrptwpSjgXHzIQxR0omXljQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "fdir": "^6.5.0",
                "picomatch": "^4.0.3"
            },
            "engines": {
                "node": ">=12.0.0"
            },
            "funding": {
                "url": "https://github.com/sponsors/SuperchupuDev"
            }
        },
        "node_modules/tree-kill": {
            "version": "1.2.2",
            "resolved": "https://registry.npmjs.org/tree-kill/-/tree-kill-1.2.2.tgz",
            "integrity": "sha512-L0Orpi8qGpRG//Nd+H90vFB+3iHnue1zSSGmNOOCh1GLJ7rUKVwV2HvijphGQS2UmhUZewS9VgvxYIdgr+fG1A==",
            "dev": true,
            "license": "MIT",
            "bin": {
                "tree-kill": "cli.js"
            }
        },
        "node_modules/tslib": {
            "version": "2.8.1",
            "resolved": "https://registry.npmjs.org/tslib/-/tslib-2.8.1.tgz",
            "integrity": "sha512-oJFu94HQb+KVduSUQL7wnpmqnfmLsOA/nAh6b6EH0wCEoK0/mPeXU6c3wKDV83MkOuHPRHtSXKKU99IBazS/2w==",
            "dev": true,
            "license": "0BSD"
        },
        "node_modules/update-browserslist-db": {
            "version": "1.2.3",
            "resolved": "https://registry.npmjs.org/update-browserslist-db/-/update-browserslist-db-1.2.3.tgz",
            "integrity": "sha512-Js0m9cx+qOgDxo0eMiFGEueWztz+d4+M3rGlmKPT+T4IS/jP4ylw3Nwpu6cpTTP8R1MAC1kF4VbdLt3ARf209w==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/browserslist"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/browserslist"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "escalade": "^3.2.0",
                "picocolors": "^1.1.1"
            },
            "bin": {
                "update-browserslist-db": "cli.js"
            },
            "peerDependencies": {
                "browserslist": ">= 4.21.0"
            }
        },
        "node_modules/utrie": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/utrie/-/utrie-1.0.2.tgz",
            "integrity": "sha512-1MLa5ouZiOmQzUbjbu9VmjLzn1QLXBhwpUa7kdLUQK+KQ5KA9I1vk5U4YHe/X2Ch7PYnJfWuWT+VbuxbGwljhw==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "base64-arraybuffer": "^1.0.2"
            }
        },
        "node_modules/vite": {
            "version": "7.3.1",
            "resolved": "https://registry.npmjs.org/vite/-/vite-7.3.1.tgz",
            "integrity": "sha512-w+N7Hifpc3gRjZ63vYBXA56dvvRlNWRczTdmCBBa+CotUzAPf5b7YMdMR/8CQoeYE5LX3W4wj6RYTgonm1b9DA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "esbuild": "^0.27.0",
                "fdir": "^6.5.0",
                "picomatch": "^4.0.3",
                "postcss": "^8.5.6",
                "rollup": "^4.43.0",
                "tinyglobby": "^0.2.15"
            },
            "bin": {
                "vite": "bin/vite.js"
            },
            "engines": {
                "node": "^20.19.0 || >=22.12.0"
            },
            "funding": {
                "url": "https://github.com/vitejs/vite?sponsor=1"
            },
            "optionalDependencies": {
                "fsevents": "~2.3.3"
            },
            "peerDependencies": {
                "@types/node": "^20.19.0 || >=22.12.0",
                "jiti": ">=1.21.0",
                "less": "^4.0.0",
                "lightningcss": "^1.21.0",
                "sass": "^1.70.0",
                "sass-embedded": "^1.70.0",
                "stylus": ">=0.54.8",
                "sugarss": "^5.0.0",
                "terser": "^5.16.0",
                "tsx": "^4.8.1",
                "yaml": "^2.4.2"
            },
            "peerDependenciesMeta": {
                "@types/node": {
                    "optional": true
                },
                "jiti": {
                    "optional": true
                },
                "less": {
                    "optional": true
                },
                "lightningcss": {
                    "optional": true
                },
                "sass": {
                    "optional": true
                },
                "sass-embedded": {
                    "optional": true
                },
                "stylus": {
                    "optional": true
                },
                "sugarss": {
                    "optional": true
                },
                "terser": {
                    "optional": true
                },
                "tsx": {
                    "optional": true
                },
                "yaml": {
                    "optional": true
                }
            }
        },
        "node_modules/vite-plugin-full-reload": {
            "version": "1.2.0",
            "resolved": "https://registry.npmjs.org/vite-plugin-full-reload/-/vite-plugin-full-reload-1.2.0.tgz",
            "integrity": "sha512-kz18NW79x0IHbxRSHm0jttP4zoO9P9gXh+n6UTwlNKnviTTEpOlum6oS9SmecrTtSr+muHEn5TUuC75UovQzcA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "picocolors": "^1.0.0",
                "picomatch": "^2.3.1"
            }
        },
        "node_modules/vite-plugin-full-reload/node_modules/picomatch": {
            "version": "2.3.1",
            "resolved": "https://registry.npmjs.org/picomatch/-/picomatch-2.3.1.tgz",
            "integrity": "sha512-JU3teHTNjmE2VCGFzuY8EXzCDVwEqB2a8fsIvwaStHhAWJEeVd1o1QD80CU6+ZdEXXSLbSsuLwJjkCBWqRQUVA==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8.6"
            },
            "funding": {
                "url": "https://github.com/sponsors/jonschlinkert"
            }
        },
        "node_modules/wrap-ansi": {
            "version": "7.0.0",
            "resolved": "https://registry.npmjs.org/wrap-ansi/-/wrap-ansi-7.0.0.tgz",
            "integrity": "sha512-YVGIj2kamLSTxw6NsZjoBxfSwsn0ycdesmc4p+Q21c5zPuZ1pl+NfxVdxPtdHvmNVOQ6XSYG4AUtyt/Fi7D16Q==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "ansi-styles": "^4.0.0",
                "string-width": "^4.1.0",
                "strip-ansi": "^6.0.0"
            },
            "engines": {
                "node": ">=10"
            },
            "funding": {
                "url": "https://github.com/chalk/wrap-ansi?sponsor=1"
            }
        },
        "node_modules/y18n": {
            "version": "5.0.8",
            "resolved": "https://registry.npmjs.org/y18n/-/y18n-5.0.8.tgz",
            "integrity": "sha512-0pfFzegeDWJHJIAmTLRP2DwHjdF5s7jo9tuztdQxAhINCdvS+3nGINqPd00AphqJR/0LhANUS6/+7SCb98YOfA==",
            "dev": true,
            "license": "ISC",
            "engines": {
                "node": ">=10"
            }
        },
        "node_modules/yargs": {
            "version": "17.7.2",
            "resolved": "https://registry.npmjs.org/yargs/-/yargs-17.7.2.tgz",
            "integrity": "sha512-7dSzzRQ++CKnNI/krKnYRV7JKKPUXMEh61soaHKg9mrWEhzFWhFnxPxGl+69cD1Ou63C13NUPCnmIcrvqCuM6w==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "cliui": "^8.0.1",
                "escalade": "^3.1.1",
                "get-caller-file": "^2.0.5",
                "require-directory": "^2.1.1",
                "string-width": "^4.2.3",
                "y18n": "^5.0.5",
                "yargs-parser": "^21.1.1"
            },
            "engines": {
                "node": ">=12"
            }
        },
        "node_modules/yargs-parser": {
            "version": "21.1.1",
            "resolved": "https://registry.npmjs.org/yargs-parser/-/yargs-parser-21.1.1.tgz",
            "integrity": "sha512-tVpsJW7DdjecAiFpbIB1e3qxIQsE6NoPc5/eTdrbbIC4h0LVsWhnoa3g+m2HclBIujHzsxZ4VJVA+GUuc2/LBw==",
            "dev": true,
            "license": "ISC",
            "engines": {
                "node": ">=12"
            }
        }
    }
}
```

### 37. package.json

**Base CRM.** Desde original: Reemplazar completo. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/package.json`.

```json
{
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "autoprefixer": "^10.4.24",
        "axios": "^1.11.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^2.0.0",
        "postcss": "^8.5.6",
        "tailwindcss": "^4.1.18",
        "vite": "^7.0.7"
    },
    "dependencies": {
        "alpinejs": "3.14.9",
        "jspdf": "3.0.4",
        "jspdf-autotable": "5.0.2"
    }
}
```

### 38. public/css/crm.css

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/public/css/crm.css`.

```css
:root{font-family:Inter,Arial,sans-serif;color:#151515;background:#f5f5f2;font-size:15px}*{box-sizing:border-box}body{margin:0}header{background:#121212;color:white;min-height:78px;padding:20px 32px;display:flex;align-items:center;justify-content:space-between;gap:16px}.brand{font-size:25px;font-weight:900;letter-spacing:-1px;color:white;text-decoration:none}.brand small{font-size:12px;letter-spacing:2px;color:#bce191;margin-left:12px}header button{margin-left:15px}.shell{display:grid;grid-template-columns:230px 1fr;min-height:calc(100vh - 78px)}aside{background:white;border-right:1px solid #e2e2dc;padding:32px 20px}aside p,.eyebrow{font-size:10px;letter-spacing:2px;font-weight:800;color:#6b715f}nav{display:grid;gap:8px;margin:25px 0 50px}nav button,nav a{text-align:left;padding:14px;border:0;border-radius:4px;color:#45483f;text-decoration:none;font-weight:700}nav .selected{background:#151515;color:white}aside small{color:#838878;line-height:1.8}main{min-width:0;padding:40px;max-width:1500px}.heading{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:26px}h1{font-size:34px;letter-spacing:-1px;margin:12px 0}h2{font-size:19px;margin:0 0 20px}p{line-height:1.6}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px}.card,article{background:white;border:1px solid #e0e2d9;border-radius:6px;padding:24px}.card strong{font-size:36px;display:block;margin-top:10px}.card span{font-size:12px;color:#74776d}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px}.two-col label{margin-bottom:0}article{margin-bottom:24px}button,input,select,textarea{font:inherit}button{border:1px solid #d6d9cd;background:white;border-radius:4px;padding:10px 14px;cursor:pointer;font-weight:700}button:hover{background:#eceee5}button:disabled{opacity:.5;cursor:wait}.primary{background:#171a14;color:white;border-color:#171a14}.primary:hover{background:#343b29}label{display:grid;gap:8px;font-size:12px;font-weight:700;margin-bottom:17px}input,select,textarea{width:100%;border:1px solid #d2d6c9;border-radius:4px;background:white;padding:11px;font-weight:400}input:focus,textarea:focus,select:focus{outline:2px solid #a3c66f;outline-offset:2px}.filters{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:14px;align-items:end;margin-bottom:24px}.filters label{margin:0}.filters button{height:42px}.muted{color:#74776c;font-size:12px}.scroll{overflow:auto}table{width:100%;border-collapse:collapse;text-align:left;font-size:12px}th{color:#777c6d;font-size:10px;text-transform:uppercase;letter-spacing:1px;white-space:nowrap}td,th{padding:15px 10px;border-bottom:1px solid #eceee6}td .row-actions{display:flex;gap:6px}td button{font-size:11px;padding:7px 9px}.badge{padding:4px 8px;border-radius:20px;background:#edf1e7;display:inline-block;font-size:11px;margin:2px}.stage-Prospecto{background:#e4ebfa;color:#375687}.stage-Activo{background:#deefd5;color:#346424}.stage-Frecuente{background:#e9def6;color:#654283}.stage-Inactivo{background:#f3dddd;color:#8d4040}.bar-row{display:grid;grid-template-columns:90px 1fr 30px;gap:12px;align-items:center;margin:20px 0;font-size:12px}.track{height:18px;background:#eef0e8;border-radius:3px;overflow:hidden}.bar{height:100%;background:#97b877;border-radius:3px}.list-item{padding:16px 0;border-bottom:1px solid #eceee7}.list-item:last-child{border:0}.list-item p{white-space:pre-wrap;overflow-wrap:anywhere}.timeline .list-item{padding-left:20px;border-left:2px solid #b8cba1;margin:12px 0}.pagination{display:flex;gap:12px;align-items:center;margin-top:18px}#message{padding:14px 18px;background:#e0efd1;border:1px solid #a3c66f;border-radius:4px;margin-bottom:24px;overflow-wrap:anywhere}#message.error,#client-error{color:#a12222}#message.error{background:#fff1ef;border-color:#d89c94}dialog{border:1px solid #d3d8c7;border-radius:8px;width:min(540px,95vw);padding:28px;max-height:90vh;overflow:auto}dialog::backdrop{background:#0008}dialog h2{margin:0}.danger{color:#9e2323}#detail-info{display:flex;flex-wrap:wrap;gap:22px;align-items:center}#evaluation-list{margin-top:24px}[hidden]{display:none!important}@media(max-width:1000px){main{padding:24px}.cards{grid-template-columns:repeat(2,1fr)}.shell{grid-template-columns:190px 1fr}.two-col{grid-template-columns:1fr}.filters{grid-template-columns:1fr 1fr}}@media(max-width:650px){header{padding:18px;align-items:flex-start;flex-direction:column}.shell{display:block}aside{padding:16px}aside p,aside small{display:none}nav{display:flex;flex-wrap:wrap;margin:0;gap:5px}nav button,nav a{font-size:12px;padding:10px}main{padding:20px 14px}h1{font-size:28px}.heading{align-items:flex-start}.cards{gap:10px}.card,article{padding:18px}.filters{grid-template-columns:1fr}.card strong{font-size:30px}}
```

### 39. public/css/scm.css

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/public/css/scm.css`.

```css
:root{--ink:#1c2827;--green:#194e46;--soft:#eef4f1;--border:#d8e2dd;--muted:#5d706b;--danger:#973329}*{box-sizing:border-box}body{margin:0;background:#f4f6f3;color:var(--ink);font:15px/1.6 system-ui,sans-serif}header{padding:20px 4vw;background:#152c29;color:white;display:flex;align-items:center;justify-content:space-between;gap:20px}header>div{display:flex;gap:18px;align-items:center;flex-wrap:wrap}a{color:var(--green)}header a{color:white}.brand{text-decoration:none;font-weight:900;letter-spacing:1px;font-size:22px}.brand small{font-size:12px;color:#b5daca;margin-left:10px}button,.button{padding:10px 15px;border:1px solid var(--border);border-radius:5px;background:white;color:var(--green);font:600 13px system-ui;cursor:pointer;text-decoration:none;display:inline-block}button:hover,.button:hover{background:var(--soft)}button:disabled{opacity:.5;cursor:wait}.primary{background:var(--green);color:white;border-color:var(--green)}.primary:hover{background:#26675c}.danger{color:var(--danger)}nav{display:flex;gap:8px;padding:15px 4vw;background:white;border-bottom:1px solid var(--border);flex-wrap:wrap}nav [aria-current]{background:var(--green);color:white}main{max-width:1440px;margin:auto;padding:32px 4vw}.page-title,.section-heading,.dialog-heading{display:flex;align-items:center;justify-content:space-between;gap:16px}.eyebrow{font-size:11px;letter-spacing:2px;font-weight:800;color:var(--green);margin:0}h1{font-size:clamp(26px,4vw,38px);line-height:1.15;letter-spacing:-1px;margin:10px 0}h2{font-size:23px;margin:14px 0}h3{font-size:18px;margin:0 0 10px}p{color:var(--muted)}.two-col,.three-col{display:grid;gap:22px;grid-template-columns:repeat(2,minmax(0,1fr))}.three-col{grid-template-columns:repeat(3,minmax(0,1fr))}article{background:white;border:1px solid var(--border);border-radius:7px;padding:24px;margin:10px 0}.cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:24px 0}.card{background:white;border:1px solid var(--border);padding:22px;border-radius:6px}.card small{color:var(--muted);display:block}.card strong{display:block;font-size:28px;margin-top:8px}.filters{display:flex;flex-wrap:wrap;align-items:end;gap:14px;margin:15px 0 22px}.filters label{flex:1;min-width:140px}label{display:block;font-size:13px;font-weight:600;margin-bottom:15px}input,select,textarea{display:block;width:100%;border:1px solid #b8c9c1;background:white;color:var(--ink);border-radius:4px;padding:11px;font:15px system-ui;margin-top:5px}textarea{min-height:90px;resize:vertical}.check{display:flex;align-items:center;gap:10px;font-weight:400}.check input{width:18px;height:18px;margin:0;accent-color:var(--green)}.table-wrap{overflow:auto;background:white;border:1px solid var(--border);border-radius:6px}table{border-collapse:collapse;width:100%;font-size:13px;min-width:600px}td,th{padding:15px;text-align:left;border-bottom:1px solid var(--border);vertical-align:top}th{background:var(--soft);white-space:nowrap;color:var(--green)}td small{display:block;color:var(--muted)}td button{margin:2px;padding:7px 10px;font-size:12px}.badge{display:inline-block;padding:3px 8px;border-radius:4px;background:#e4f0ea;color:#255d4e;font-size:11px;font-weight:700;white-space:nowrap}.critical{background:#fff0dc;color:#8a4e09}.pagination{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin:18px 0}.list-row{display:flex;justify-content:space-between;align-items:center;gap:10px;border-bottom:1px solid var(--border);padding:12px 0}.bar-row{margin:16px 0}.bar-row>div:first-child{display:flex;justify-content:space-between;gap:10px;font-size:13px}.bar-track{background:var(--soft);height:12px;border-radius:3px;margin-top:7px}.bar-fill{height:100%;background:#3f7a6b;border-radius:3px;min-width:2px}.hint{font-size:12px}.level{font-size:24px;color:var(--green);font-weight:700}dialog{width:min(900px,94vw);max-height:90vh;overflow:auto;border:1px solid var(--border);border-radius:10px;padding:28px;color:var(--ink);box-shadow:0 20px 80px #122b2933}dialog::backdrop{background:#11242188}dialog .dialog-heading{margin-bottom:15px}#notice{position:fixed;z-index:10000;bottom:24px;right:24px;max-width:min(520px,92vw);background:var(--green);color:white;padding:17px 24px;border-radius:7px;box-shadow:0 6px 25px #0003}#notice.error{background:var(--danger)}footer{text-align:center;padding:26px;color:var(--muted);font-size:12px}[hidden]{display:none!important}:focus-visible{outline:3px solid #7ba995;outline-offset:2px}@media(max-width:850px){header{align-items:flex-start;flex-direction:column}header>div{font-size:12px;gap:12px}.cards{grid-template-columns:repeat(2,minmax(0,1fr))}.two-col,.three-col{grid-template-columns:1fr}.page-title{align-items:flex-start;flex-direction:column}.filters label{min-width:120px}dialog{padding:18px}article{padding:18px}nav{gap:6px}nav button{padding:9px;font-size:12px}}@media(max-width:420px){main{padding:24px 15px}.card{padding:15px}.card strong{font-size:24px}}
```

### 40. public/js/crm.js

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/public/js/crm.js`.

```javascript
(() => {
    const $ = selector => document.querySelector(selector);
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const date = value => value ? new Date(value).toLocaleString('es-MX') : 'Sin contacto';
    const state = {user:null, editing:null, client:null, clientPage:1, activityPage:1, contactsPage:1, sequence:0};
    let alertTimer;
    function alert(message, error=false) {
        const box=$('#message'); box.textContent=message; box.hidden=false; box.className=error?'error':'';
        clearTimeout(alertTimer); alertTimer=setTimeout(()=>box.hidden=true,8000);
    }
    async function run(action, button) {
        if (button?.disabled) return;
        if(button) button.disabled=true;
        try { await action(); } catch (e) { alert(e.message,true); } finally { if(button) button.disabled=false; }
    }
    function bind(selector, event, action) {
        $(selector)?.addEventListener(event,e=>{e.preventDefault(); run(()=>action(e),e.submitter || (e.currentTarget.tagName==='BUTTON'?e.currentTarget:null));});
    }
    function data(form) { return Object.fromEntries(new FormData(form)); }
    function pager(selector, response, action) {
        const root=$(selector); root.replaceChildren();
        for(const [label,page] of [['Anterior',response.current_page-1],['Siguiente',response.current_page+1]]) {
            const button=document.createElement('button'); button.textContent=label;
            button.disabled=page<1 || page>response.last_page; button.onclick=()=>run(()=>action(page),button); root.append(button);
        }
        const info=document.createElement('span'); info.textContent=`Página ${response.current_page} de ${response.last_page} · ${response.total} registros`; root.append(info);
    }
    async function tab(id) {
        document.querySelectorAll('.tab').forEach(s=>s.hidden=s.id!==id);
        document.querySelectorAll('[data-tab]').forEach(b=>b.classList.toggle('selected',b.dataset.tab===id));
        if(id==='dashboard') await metrics();
        if(id==='clients') await clients(1);
        if(id==='activity') await activity(1);
        if(id==='contacts') await contacts(1);
        if(id==='users') await users();
    }
    async function metrics() {
        const m=await HF.request('/metricas');
        $('#counters').innerHTML=[['Total de clientes',m.total],['Activos',m.activos],['Inactivos',m.inactivos],['Interacciones',m.interacciones]].map(([label,value])=>`<div class="card"><span>${label}</span><strong>${Number(value)}</strong></div>`).join('');
        const max=Math.max(1,...m.por_etapa.map(s=>s.total));
        $('#chart').innerHTML=m.por_etapa.map(s=>`<div class="bar-row"><span>${escape(s.etapa)}</span><div class="track"><div class="bar" style="width:${s.total/max*100}%"></div></div><strong>${Number(s.total)}</strong></div>`).join('');
        $('#risks').innerHTML=m.clientes_en_riesgo.length?m.clientes_en_riesgo.map(c=>`<div class="list-item"><button data-history="${c.id}">${escape(c.nombre)}</button><p class="muted">Última interacción: ${escape(date(c.interacciones_max_fecha))}</p></div>`).join(''):'<p class="muted">Sin clientes en riesgo.</p>';
        $('#interaction-counts').innerHTML=m.interacciones_por_cliente.map(c=>`<div class="list-item">${escape(c.nombre)} <strong>· ${Number(c.total)}</strong></div>`).join('')||'<p class="muted">Registra tu primer cliente.</p>';
    }
    async function clients(page=1) {
        state.clientPage=page; const sequence=++state.sequence;
        const filters=data($('#filters')); const query=new URLSearchParams(Object.entries(filters).filter(([,v])=>v)); query.set('page',page);
        const result=await HF.request('/clientes?'+query); if(sequence!==state.sequence) return;
        $('#client-list').innerHTML=result.data.map(c=>`<tr><td><strong>${escape(c.nombre)}</strong><br><span class="muted">Alta: ${escape(date(c.fecha_registro))}</span></td>
            <td>${escape(c.correo)}<br>${escape(c.telefono || 'Sin teléfono')}</td><td>${escape(c.empresa || '—')}</td>
            <td><span class="badge">${escape(c.estado)}</span><span class="badge stage-${escape(c.etapa_crm)}">${escape(c.etapa_crm)}</span></td>
            <td>${Number(c.interacciones_count)}</td><td><div class="row-actions"><button data-history="${c.id}">Historial</button><button data-edit="${c.id}">Editar</button>${state.user.role==='admin'?`<button class="danger" data-delete="${c.id}">Eliminar</button>`:''}</div></td></tr>`).join('')||'<tr><td colspan="6">No hay clientes con estos filtros.</td></tr>';
        pager('#client-pages',result,clients);
    }
    async function edit(id=null) {
        state.editing=id; const form=$('#client-form'); form.reset(); $('#client-error').textContent='';
        $('#client-form-title').textContent=id?'Editar cliente':'Nuevo cliente';
        if(id) { const c=await HF.request('/clientes/'+id); for(const key of ['nombre','correo','telefono','empresa','estado','etapa_crm']) form.elements[key].value=c[key]??''; }
        $('#client-dialog').showModal();
    }
    async function detail(id) {
        state.client=id; await tab('detail');
        const [client,history]=await Promise.all([HF.request('/clientes/'+id),HF.request(`/clientes/${id}/interacciones`)]);
        $('#detail-title').textContent=client.nombre;
        $('#detail-info').innerHTML=`<div>${escape(client.correo)}<br><small>${escape(client.telefono||'Sin teléfono')} · ${escape(client.empresa||'Sin empresa')}</small></div><span class="badge">${escape(client.estado)}</span>
            <label>Etapa CRM<select id="detail-stage">${['Prospecto','Activo','Frecuente','Inactivo'].map(s=>`<option ${s===client.etapa_crm?'selected':''}>${s}</option>`).join('')}</select></label>`;
        $('#detail-stage').onchange=e=>run(async()=>{await HF.request(`/clientes/${id}/etapa`,'PUT',{etapa_crm:e.target.value});alert('Etapa actualizada.');});
        $('#history').innerHTML=history.map(i=>`<div class="list-item"><strong>${escape(i.tipo)} · ${escape(date(i.fecha))}</strong><p>${escape(i.descripcion)}</p><small>Responsable: ${escape(i.usuario.name)}</small></div>`).join('')||'<p class="muted">Todavía no hay interacciones.</p>';
        $('#evaluation-list').innerHTML=[...client.evaluaciones].reverse().map(e=>`<div class="list-item"><strong>${Number(e.puntuacion)}/5</strong> · ${escape(e.usuario.name)}<p>${escape(e.observaciones||'')}</p><small>${escape(date(e.created_at))}</small></div>`).join('');
        const now=new Date(); const local=new Date(now.getTime()-now.getTimezoneOffset()*60000).toISOString().slice(0,16);
        $('#interaction-form').elements.fecha.value=local; $('#interaction-form').elements.fecha.max=local;
    }
    async function activity(page=1) {
        state.activityPage=page; const result=await HF.request('/mi-actividad?page='+page);
        $('#my-activity').innerHTML=result.data.map(i=>`<div class="list-item"><button data-history="${i.cliente_id}">${escape(i.cliente.nombre)}</button> · ${escape(i.tipo)}<p>${escape(i.descripcion)}</p><small>${escape(date(i.fecha))}</small></div>`).join('')||'<p class="muted">Aún no registraste interacciones.</p>';
        pager('#activity-pages',result,activity);
    }
    async function contacts(page=1) {
        state.contactsPage=page; const result=await HF.request('/contactos?page='+page);
        $('#contact-list').innerHTML=result.data.map(c=>`<div class="list-item"><strong>${escape(c.name)}</strong> · ${escape(c.email)}<p>${escape(c.message)}</p><small>${escape(date(c.created_at))}</small> ${c.handled?'<span class="badge">Registrado en CRM</span>':`<button data-contact="${c.id}">Registrar como interacción</button>`}</div>`).join('')||'<p class="muted">Sin mensajes.</p>';
        pager('#contact-pages',result,contacts);
    }
    async function users() {
        const list=await HF.request('/usuarios'); $('#user-list').innerHTML=list.map(u=>`<div class="list-item"><strong>${escape(u.name)}</strong> <span class="badge">${escape(u.role)}</span><p>${escape(u.email)}</p></div>`).join('');
    }
    document.addEventListener('click',e=>{
        const button=e.target.closest('button'); if(!button) return;
        if(button.dataset.tab) run(()=>tab(button.dataset.tab),button);
        if(button.dataset.history) run(()=>detail(Number(button.dataset.history)),button);
        if(button.dataset.edit) run(()=>edit(Number(button.dataset.edit)),button);
        if(button.dataset.delete && confirm('¿Eliminar el cliente y su historial? Esta acción no se puede deshacer.')) run(async()=>{await HF.request('/clientes/'+button.dataset.delete,'DELETE');await clients(state.clientPage);alert('Cliente eliminado.');},button);
        if(button.dataset.contact) run(async()=>{await HF.request(`/contactos/${button.dataset.contact}/registrar`,'POST',{});await contacts(state.contactsPage);alert('Cliente e interacción registrados.');},button);
    });
    bind('#filters','submit',()=>clients(1)); bind('#new-client','click',()=>edit());
    bind('#close-client','click',()=>$('#client-dialog').close()); bind('#back-clients','click',()=>tab('clients'));
    bind('#refresh-metrics','click',metrics);
    bind('#logout','click',async()=>{await HF.request('/logout','POST',{});location.href='/';});
    bind('#client-form','submit',async e=>{
        try { await HF.request(state.editing?'/clientes/'+state.editing:'/clientes',state.editing?'PUT':'POST',data(e.target));
            $('#client-dialog').close(); await clients(state.clientPage); alert('Cliente guardado.');
        } catch(error) { $('#client-error').textContent=error.message; }
    });
    bind('#interaction-form','submit',async e=>{const body=data(e.target);body.cliente_id=state.client;body.fecha=new Date(body.fecha).toISOString();await HF.request('/interacciones','POST',body);e.target.reset();await detail(state.client);alert('Interacción guardada.');});
    bind('#evaluation-form','submit',async e=>{await HF.request(`/clientes/${state.client}/evaluaciones`,'POST',data(e.target));e.target.reset();await detail(state.client);alert('Evaluación guardada.');});
    bind('#user-form','submit',async e=>{await HF.request('/usuarios','POST',data(e.target));e.target.reset();await users();alert('Cuenta creada.');});
    run(async()=>{const session=await HF.request('/sesion');state.user=session.user;if(!state.user){location.href='/login';return;}await metrics();});
})();
```

### 41. public/js/http.js

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/public/js/http.js`.

```javascript
window.HF = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    async request(path, method = 'GET', data) {
        const response = await fetch(path, {
            method, credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf},
            ...(data === undefined ? {} : {body: JSON.stringify(data)})
        });
        const json = response.status === 204 ? null : await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = json.errors ? Object.values(json.errors).flat().join(' ') :
                ({401:'Inicia sesión para continuar.',403:'No tienes permiso.',419:'La sesión venció. Recarga la página.',429:'Demasiados intentos. Espera un minuto.'}[response.status] || json.message || 'No se pudo completar la operación.');
            const error = new Error(message); error.status = response.status; throw error;
        }
        if (json?.csrf_token) this.csrf = json.csrf_token;
        return json;
    }
};
```

### 42. public/js/scm.js

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/public/js/scm.js`.

```javascript
(() => {
    const $ = selector => document.querySelector(selector);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = n => new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format(n);
    const date = v => v ? new Date(v).toLocaleString('es-MX') : '—';
    const admin = document.body.dataset.role === 'admin';
    const state = {suppliers:[], products:[], productPage:1, orderPage:1, historyPage:1, selected:null, productRevision:0, orderRevision:0};
    const keys = new WeakMap();
    let noticeTimer;
    function notice(text, error = false) {
        const el = $('#notice'); el.textContent = text; el.classList.toggle('error', error); el.hidden = false;
        clearTimeout(noticeTimer); noticeTimer = setTimeout(() => el.hidden = true, error ? 12000 : 6000);
    }
    async function action(element, fn) {
        if (element?.dataset.busy) return;
        if (element) {element.dataset.busy = '1'; element.disabled = true;}
        try {await fn();} catch (e) {notice(e.message, true);} finally {if (element) {delete element.dataset.busy; element.disabled = false;}}
    }
    function tab(id) {
        document.querySelectorAll('.panel').forEach(el => el.hidden = el.id !== id);
        document.querySelectorAll('[data-tab]').forEach(el => {if (el.dataset.tab === id) el.setAttribute('aria-current','page'); else el.removeAttribute('aria-current');});
    }
    function open(id) {$(id).showModal();}
    function close(id) {$(id).close();}
    function field(form, name) {return form.elements.namedItem(name);}
    function values(form) {return Object.fromEntries(new FormData(form));}
    function requestKey(form) {if (!keys.has(form)) keys.set(form, crypto.randomUUID()); return keys.get(form);}
    function pagination(selector, data, kind) {
        $(selector).innerHTML = `<button data-page="${kind}" data-number="${data.current_page-1}" ${data.current_page<=1?'disabled':''}>Anterior</button><span>${data.total} registros · ${data.current_page}/${data.last_page}</span><button data-page="${kind}" data-number="${data.current_page+1}" ${data.current_page>=data.last_page?'disabled':''}>Siguiente</button>`;
    }
    async function loadProducts(page = state.productPage) {
        state.productPage = page; const revision = ++state.productRevision;
        const query = new URLSearchParams(values($('#product-filter'))); query.set('page', page);
        const data = await HF.request('/productos?'+query);
        if (revision !== state.productRevision) return;
        state.products = data.data;
        $('#product-rows').innerHTML = data.data.map(p => `<tr><td><strong>${esc(p.nombre)}</strong><small>${esc(p.categoria)} · ${esc(p.proveedor||'Sin proveedor')}</small><small>${esc(p.ubicacion)}${p.activo?'':' · Oculto en tienda'}</small></td><td><span class="badge ${p.stock_bajo?'critical':''}">${p.stock_actual} ${p.stock_bajo?'· CRÍTICO':''}</span></td><td>${p.stock_minimo} / ${p.stock_objetivo}</td><td>${money(p.costo_unitario)}<small>Venta: ${money(p.precio_venta)}</small></td><td><span class="badge">${esc(p.estrategia_logistica)}</span></td><td><button data-history="${p.id}">Historial</button>${!p.deleted_at && admin?`<button data-edit-product="${p.id}">Editar</button><button data-strategy="${p.id}" data-value="${p.estrategia_logistica==='PUSH'?'PULL':'PUSH'}">Cambiar a ${p.estrategia_logistica==='PUSH'?'Pull':'Push'}</button><button class="danger" data-delete-product="${p.id}">Archivar</button>`:''}${p.deleted_at?'<span class="badge">Archivado</span>':''}</td></tr>`).join('') || '<tr><td colspan="6">Sin productos con estos filtros.</td></tr>';
        pagination('#product-pages', data, 'product');
    }
    async function loadSuppliers() {
        state.suppliers = await HF.request('/proveedores');
        $('#supplier-rows').innerHTML = state.suppliers.map(p => `<tr><td>${esc(p.nombre)}</td><td>${esc(p.contacto)}</td><td>${esc(p.correo)}</td><td>${esc(p.telefono)}</td><td>${admin?`<button data-edit-supplier="${p.id}">Editar</button><button class="danger" data-delete-supplier="${p.id}">Eliminar</button>`:'Consulta'}</td></tr>`).join('') || '<tr><td colspan="5">Registra el primer proveedor para asociarlo al catálogo.</td></tr>';
        const select = $('#product-form select[name="proveedor_id"]');
        if (select) {const chosen = select.value; select.innerHTML = '<option value="">Sin proveedor</option>'+state.suppliers.map(p=>`<option value="${p.id}">${esc(p.nombre)}</option>`).join(''); select.value = chosen;}
    }
    async function loadOrders(page = state.orderPage) {
        state.orderPage = page; const revision = ++state.orderRevision;
        const query = new URLSearchParams(values($('#order-filter'))); query.set('page', page);
        const data = await HF.request('/pedidos?'+query);
        if (revision !== state.orderRevision) return;
        $('#order-rows').innerHTML = data.data.map(p=>`<tr><td><strong>#${p.id} · ${esc(p.producto?.name)}</strong><small>Responsable: ${esc(p.usuario?.name||'Sistema')}</small></td><td>${p.cantidad}</td><td>${p.tipo==='reposicion'?'Reposición':'Venta'}<small>${p.automatico?'Push automático':'Manual'}</small></td><td><span class="badge ${p.estado==='pendiente'?'critical':''}">${esc(p.estado)}</span></td><td>${date(p.created_at)}<small>Surtido: ${date(p.fecha_surtido)}</small></td><td>${p.estado==='pendiente'?`<button class="primary" data-fulfill="${p.id}">Marcar surtido</button>`:'Registrado'}</td></tr>`).join('') || '<tr><td colspan="6">Sin pedidos con estos filtros.</td></tr>';
        pagination('#order-pages', data, 'order');
    }
    function bars(rows, label, amount, suffix) {
        const max = Math.max(1, ...rows.map(amount));
        return rows.map(r=>`<div class="bar-row"><div><span>${esc(label(r))}</span><strong>${amount(r)} ${suffix}</strong></div><div class="bar-track"><div class="bar-fill" style="width:${Math.max(0,Math.min(100,amount(r)/max*100))}%"></div></div></div>`).join('') || '<p>Aún no hay ventas surtidas registradas.</p>';
    }
    async function loadReports() {
        const d = await HF.request('/scm/reportes');
        $('#counters').innerHTML = [['Productos',d.total_productos],['Unidades disponibles',d.unidades_stock],['Valor a costo',money(d.valor_inventario_cents/100)],['Pedidos pendientes',d.pedidos_pendientes]].map(([label,value])=>`<div class="card"><small>${label}</small><strong>${esc(value)}</strong></div>`).join('');
        $('#top-chart').innerHTML = bars(d.productos_mas_vendidos,r=>r.nombre,r=>r.unidades,'u.');
        $('#strategy-chart').innerHTML = bars(d.comparacion,r=>`${r.estrategia}: ${r.productos} productos · ${r.criticos} críticos`,r=>r.stock,'u.');
        $('#critical').innerHTML = d.inventario_critico.map(p=>`<div class="list-row"><div><strong>${esc(p.nombre)}</strong><small> · ${esc(p.estrategia_logistica)}</small></div><button data-history="${p.id}">${p.stock_actual} / mín. ${p.stock_minimo}</button></div>`).join('') || '<p>No hay productos en nivel crítico.</p>';
        $('#slow').innerHTML = d.rotacion_lenta.map(p=>`<div class="list-row"><span>${esc(p.nombre)}<small> · stock ${p.stock_actual}</small></span><strong>${p.unidades_30_dias} ventas</strong></div>`).join('') || '<p>No hay productos con rotación lenta.</p>';
    }
    const descriptions = {'Inicial':'Se registra el catálogo y se establecen controles básicos de proveedores e inventario.','En desarrollo':'Se aplican movimientos trazables, pedidos y estrategias. El equipo revisa al menos tres evidencias.','Optimizado':'Las seis evidencias están verificadas y el equipo revisa reportes para ajustar decisiones logísticas.'};
    async function loadMaturity() {
        const data = await HF.request('/scm/estado'); const form = $('#maturity-form');
        field(form,'nivel_scm').value = data.nivel_scm;
        Object.entries(data.checklist).forEach(([k,v])=>field(form,k).checked = Boolean(v));
        if (!admin) [...form.elements].forEach(el=>el.disabled = true);
        $('#level-indicator').textContent = data.nivel_scm; $('#level-description').textContent = descriptions[data.nivel_scm];
    }
    async function refresh() {await Promise.all([loadProducts(),loadSuppliers(),loadOrders(),loadReports(),loadMaturity()]);}
    async function refreshInventory() {await Promise.all([loadProducts(),loadOrders(),loadReports()]);}
    async function productEditor(id) {
        const form = $('#product-form'); form.reset();
        const p = id ? await HF.request('/productos/'+id) : {id:'',nombre:'',descripcion:'',categoria:'Hoodie',proveedor_id:'',stock_actual:0,stock_minimo:0,stock_objetivo:10,costo_unitario:0,precio_venta:1,estrategia_logistica:'PULL',ubicacion:'Almacén principal',imagen:'',color:'',activo:true};
        Object.entries(p).forEach(([k,v])=>{const el = field(form,k);if(el) {if(el.type==='checkbox')el.checked=Boolean(v);else el.value=v??'';}});
        field(form,'stock_actual').disabled = Boolean(id); $('#product-title').textContent = id?'Editar producto':'Nuevo producto'; open('#product-dialog');
    }
    function supplierEditor(id) {
        const form = $('#supplier-form'); form.reset(); field(form,'id').value = id||'';
        if (id) Object.entries(state.suppliers.find(p=>p.id===id)).forEach(([k,v])=>{if(field(form,k))field(form,k).value=v;});
        open('#supplier-dialog');
    }
    async function loadHistory(page = 1) {
        state.historyPage = page;
        const [p,data] = await Promise.all([HF.request('/productos/'+state.selected),HF.request(`/productos/${state.selected}/movimientos?page=${page}`)]);
        $('#history-title').textContent = 'Movimientos · '+p.nombre; $('#history-stock').textContent = `Stock disponible: ${p.stock_actual} · mínimo: ${p.stock_minimo} · ${p.estrategia_logistica}${p.deleted_at?' · Archivado':''}`;
        const form = $('#movement-form'); form.hidden = Boolean(p.deleted_at); $('#movement-hint').hidden = Boolean(p.deleted_at); field(form,'producto_id').value = p.id;
        $('#history-rows').innerHTML = data.data.map(m=>`<tr><td>${date(m.fecha)}<small>${esc(m.usuario?.name||'Sistema')}</small></td><td>${esc(m.tipo)}<small>${esc(m.motivo)}</small></td><td>${m.cantidad}</td><td>${m.stock_anterior} → ${m.stock_resultante}</td><td>${esc(m.referencia||'—')}${m.pedido_id?`<small>Pedido SCM #${m.pedido_id}</small>`:''}</td></tr>`).join('') || '<tr><td colspan="5">Sin movimientos registrados.</td></tr>';
        pagination('#history-pages', data, 'history');
    }
    async function findOrderProducts() {
        const data = await HF.request('/productos?q='+encodeURIComponent($('#order-search').value));
        field($('#order-form'),'producto_id').innerHTML = data.data.map(p=>`<option value="${p.id}">${esc(p.nombre)} · ${p.estrategia_logistica} · stock ${p.stock_actual}</option>`).join('') || '<option value="">Sin resultados</option>';
    }
    document.addEventListener('click', e => {
        const b = e.target.closest('button'); if (!b || (b.form && b.type === 'submit')) return;
        if (b.dataset.tab) {tab(b.dataset.tab); return;}
        if (b.dataset.close) {close('#'+b.dataset.close); return;}
        action(b, async()=>{
            if (b.id==='logout') {await HF.request('/logout','POST',{}); location.href='/';}
            else if (b.id==='refresh') {await refresh(); notice('Datos actualizados.');}
            else if (b.id==='new-product') await productEditor();
            else if (b.dataset.editProduct) await productEditor(Number(b.dataset.editProduct));
            else if (b.dataset.deleteProduct) {if(!confirm('¿Archivar este producto? Su historial se conserva. Requiere stock cero y ningún pedido pendiente.'))return; await HF.request('/productos/'+b.dataset.deleteProduct,'DELETE'); await refreshInventory(); notice('Producto archivado.');}
            else if (b.dataset.strategy) {await HF.request(`/productos/${b.dataset.strategy}/estrategia`,'PUT',{estrategia_logistica:b.dataset.value}); await refreshInventory(); notice('Estrategia actualizada. Revisa los pedidos pendientes.');}
            else if (b.id==='new-supplier') supplierEditor();
            else if (b.dataset.editSupplier) supplierEditor(Number(b.dataset.editSupplier));
            else if (b.dataset.deleteSupplier) {if(!confirm('¿Eliminar proveedor? Solo se permite si no está asociado a productos.'))return;await HF.request('/proveedores/'+b.dataset.deleteSupplier,'DELETE');await loadSuppliers();notice('Proveedor eliminado.');}
            else if (b.dataset.history) {state.selected = Number(b.dataset.history); const form=$('#movement-form');form.reset();keys.delete(form);await loadHistory();open('#history-dialog');}
            else if (b.id==='new-order') {const form=$('#order-form');form.reset();keys.delete(form);await findOrderProducts();open('#order-dialog');}
            else if (b.id==='order-search-button') await findOrderProducts();
            else if (b.dataset.fulfill) {if(!confirm('¿Confirmas que este pedido fue surtido físicamente? Esta acción registra el movimiento de inventario.'))return;await HF.request(`/pedidos/${b.dataset.fulfill}/estado`,'PUT',{estado:'surtido'});await refreshInventory();notice('Pedido surtido e inventario actualizado.');}
            else if (b.dataset.page) {const page=Number(b.dataset.number);if(b.dataset.page==='product')await loadProducts(page);if(b.dataset.page==='order')await loadOrders(page);if(b.dataset.page==='history')await loadHistory(page);}
        });
    });
    document.addEventListener('submit', e => {
        const form=e.target; e.preventDefault(); const b=e.submitter||form.querySelector('button[type="submit"],button.primary,button:not([type])');
        action(b, async()=>{
            const d=values(form);
            if(form.id==='product-filter') await loadProducts(1);
            else if(form.id==='order-filter') await loadOrders(1);
            else if(form.id==='product-form') {
                const id=d.id; delete d.id; d.activo=field(form,'activo').checked; d.proveedor_id=d.proveedor_id?Number(d.proveedor_id):null;
                ['stock_actual','stock_minimo','stock_objetivo','costo_unitario','precio_venta'].forEach(k=>{if(k in d)d[k]=Number(d[k]);});
                await HF.request(id?'/productos/'+id:'/productos',id?'PUT':'POST',d);close('#product-dialog');await refreshInventory();notice('Producto guardado.');
            } else if(form.id==='supplier-form') {const id=d.id;delete d.id;await HF.request(id?'/proveedores/'+id:'/proveedores',id?'PUT':'POST',d);close('#supplier-dialog');await Promise.all([loadSuppliers(),loadProducts()]);notice('Proveedor guardado.');}
            else if(form.id==='movement-form') {d.producto_id=Number(d.producto_id);d.cantidad=Number(d.cantidad);d.request_key=requestKey(form);await HF.request('/inventario/movimiento','POST',d);keys.delete(form);field(form,'cantidad').value='';field(form,'referencia').value='';await Promise.all([loadHistory(1),refreshInventory()]);notice('Movimiento registrado.');}
            else if(form.id==='order-form') {d.producto_id=Number(d.producto_id);d.cantidad=Number(d.cantidad);d.request_key=requestKey(form);await HF.request('/pedidos','POST',d);keys.delete(form);close('#order-dialog');await Promise.all([loadOrders(1),loadReports()]);notice('Pedido generado. Recíbelo desde la lista cuando esté surtido.');}
            else if(form.id==='maturity-form' && admin) {const checklist={};['catalogo','proveedores','inventario','estrategias','pedidos','reportes'].forEach(k=>checklist[k]=field(form,k).checked);await HF.request('/scm/nivel','PUT',{nivel_scm:d.nivel_scm,checklist});await loadMaturity();notice('Nivel SCM guardado.');}
        });
    });
    // Una edición del formulario inicia una nueva operación; un reintento sin cambios conserva la clave.
    ['movement-form','order-form'].forEach(id=>$('#'+id).addEventListener('input',e=>keys.delete(e.currentTarget)));
    action(null, refresh);
})();
```

### 43. public/js/storefront.js

**U2.** Desde original: Crear. Desde CRM anterior: Reemplazar completo. Destino: `HFSTUDIOS/public/js/storefront.js`.

```javascript
document.addEventListener('alpine:init', () => {
            Alpine.data('app', () => ({
                currentRoute: 'inicio',
                searchQuery: '',
                toast: { show: false, message: '' },
                mobileMenuOpen: false,
                landingEmail: '', newsletterEmail: '',
                contactForm: { name: '', email: '', message: '' },
                
                get isAnyModalOpen() {
                    return this.cartOpen || this.checkoutOpen || this.productDetailOpen || 
                           this.orderSuccessOpen || this.invoiceModalOpen || this.editModalOpen || 
                           this.mobileMenuOpen || this.productEditorOpen;
                },
                
                allProducts: [],
                selectedCategory: 'All', selectedProduct: {}, productDetailOpen: false,
                
                get filteredProducts() {
                    let p = this.allProducts.filter(p => p.active);
                    if (this.searchQuery.trim()) {
                        const q = this.searchQuery.toLowerCase().trim();
                        p = p.filter(x => (x.name || '').toLowerCase().includes(q) || (x.category || '').toLowerCase().includes(q) || (x.color || '').toLowerCase().includes(q));
                    } else {
                        if (this.selectedCategory === 'Promociones') p = p.filter(x => x.badge && x.badge.includes('PROMO'));
                        else if (this.selectedCategory !== 'All') p = p.filter(x => x.category === this.selectedCategory);
                    }
                    return p;
                },
                
                cart: [], cartOpen: false, discountCode: '', discountApplied: false,
                
                checkoutOpen: false, checkoutStep: 1, paymentMethod: 'tarjeta', checkoutForm: { address: '', city: '', zip: '' },
                isCheckingOut: false, orderSuccessOpen: false, orderNumber: '', lastPurchaseSummary: [],

                get cartTotalItems() { return this.cart.reduce((acc, item) => acc + (Number(item.quantity) || 1), 0); },
                get cartCount() { return this.cart.length; },
                get cartTotalSinDescuento() { return this.cart.reduce((t, item) => t + ((Number(item.price) || 0) * (Number(item.quantity) || 1)), 0); },
                get cartTotal() { return this.discountApplied ? this.cartTotalSinDescuento * 0.9 : this.cartTotalSinDescuento; },
                
                userOrders: [],
                invoiceModalOpen: false, selectedOrder: null,

                isLoggedIn: false, isAdmin: false, loginRole: 'user', isLoggingIn: false,
                user: { name: '', email: '' }, registroForm: { name: '', email: '', password: '' }, loginForm: { email: '', password: '' },

                userProducts: [],
                publishForm: { name: '', price: '', description: '', image: '' },
                editModalOpen: false, editingIndex: -1, editForm: { name: '', price: '', description: '', image: '' },
                
                newComment: '', comments: [],

                auction: {timerSeconds:0, product:{name:'',image:'',currentBid:0},offers:[],newOffer:''},
                adminTab: 'pedidos', adminStats:{sales:'$0',orders:0,users:0},
                adminOrdersList: [], adminInvoicesList: [], adminUsersList: [],
                checkoutKey: '', isSaving: false, ready:false,
                productEditorOpen:false, productEditingId:null,
                productForm:{name:'',color:'',category:'Hoodie',price:'',originalPrice:'',stock:0,image:'',badge:'',description:'',active:true},
                get isStaff() {return ['admin','usuario'].includes(this.user.role);},
                get formattedTimer() {
                    const h = Math.floor(this.auction.timerSeconds / 3600).toString().padStart(2, '0');
                    const m = Math.floor((this.auction.timerSeconds % 3600) / 60).toString().padStart(2, '0');
                    const s = (this.auction.timerSeconds % 60).toString().padStart(2, '0');
                    return `${h}:${m}:${s}`;
                },

                async init() {
                    try {
                        const savedCart=JSON.parse(localStorage.getItem('hfstudios_cart')||'[]');
                        this.cart=Array.isArray(savedCart)?savedCart.filter(i=>Number.isInteger(i.id)&&Number.isInteger(i.quantity)&&i.quantity>0&&i.quantity<=99).map(i=>({...i,cartItemId:i.id})):[];
                    } catch {this.cart=[];}
                    localStorage.removeItem('hfstudios_user');
                    this.$watch('cart',value=>{try{localStorage.setItem('hfstudios_cart',JSON.stringify(value));}catch{}});
                    try {
                        const session=await HF.request('/sesion'); this.setSession(session.user);
                        await this.refreshPublic(); if(this.isLoggedIn) await this.refreshPrivate();
                        if(new URLSearchParams(location.search).get('vista')==='login') this.currentRoute='login';
                    } catch(e) {this.showToast(e.message);} finally {this.ready=true;}
                    this.timer=setInterval(()=>{if(this.auction.timerSeconds>0)this.auction.timerSeconds--;},1000);
                },
                destroy(){clearInterval(this.timer);},
                setSession(user){this.user=user||{name:'',email:''};this.isLoggedIn=!!user;this.isAdmin=user?.role==='admin';},
                async refreshPublic(){
                    [this.allProducts,this.comments]=await Promise.all([HF.request('/tienda/productos'),HF.request('/tienda/comentarios')]);
                    const auction=await HF.request('/tienda/subasta');this.auction={...auction,newOffer:this.auction.newOffer||''};
                    this.cart=this.cart.filter(i=>this.allProducts.some(p=>p.id===i.id&&p.active)).map(i=>({...i,...this.allProducts.find(p=>p.id===i.id),quantity:i.quantity,cartItemId:i.id}));
                },
                async refreshPrivate(){
                    this.userOrders=await HF.request('/tienda/pedidos');this.userProducts=await HF.request('/tienda/publicaciones');
                    if(this.isAdmin){this.adminOrdersList=this.userOrders;this.adminStats=await HF.request('/tienda/estadisticas');this.adminInvoicesList=this.userOrders.filter(o=>o.status!=='Cancelado').map(o=>({...o,orderId:o.id}));}
                },
                navigateTo(route) {
                    if(route==='admin'&&!this.isAdmin){this.showToast('Acceso solo para administradores.');return;}
                    if(['perfil','mis-publicaciones','publicar','subasta'].includes(route)&&!this.isLoggedIn){this.currentRoute='login';this.showToast('Inicia sesión para continuar.');return;}
                    if(route==='crm'||route==='scm'){if(this.isStaff)location.href='/'+route;return;}
                    this.currentRoute=route;window.scrollTo({top:0,behavior:'smooth'});
                },
                showToast(message) { this.toast.message = message; this.toast.show = true; setTimeout(() => { this.toast.show = false; }, 3500); },
                async subscribeNewsletter(){try{await HF.request('/tienda/suscripciones','POST',{email:this.newsletterEmail});this.newsletterEmail='';this.showToast('Suscripción guardada.');}catch(e){this.showToast(e.message);}},
                async handleLandingSubmit(){try{await HF.request('/tienda/suscripciones','POST',{email:this.landingEmail});this.showToast('Suscripción guardada.');this.landingEmail='';this.navigateTo('catalogo');}catch(e){this.showToast(e.message);}},
                viewProductDetail(product) { this.selectedProduct = product; this.productDetailOpen = true; },
                
                addToCart(product) {
                    const item=this.cart.find(i=>i.id===product.id);
                    if(!product.active||product.stock<1||(item&&item.quantity>=Math.min(product.stock,99))){this.showToast('Existencias insuficientes.');return;}
                    if(item)item.quantity++;else this.cart.push({...product,cartItemId:product.id,quantity:1});
                    this.showToast('Agregado a la bolsa');this.cartOpen=true;
                },
                increaseQty(index){const i=this.cart[index];if(i.quantity<Math.min(i.stock,99))i.quantity++;else this.showToast('Límite de existencias.');},
                decreaseQty(index){if(this.cart[index].quantity>1)this.cart[index].quantity--;else this.removeFromCart(index);},
                removeFromCart(index){this.cart.splice(index,1);},
                applyDiscount(){if(this.discountCode.trim().toUpperCase()!=='HF10'){this.showToast('Código inválido.');return;}this.discountApplied=true;this.discountCode='';this.showToast('Descuento HF10 aplicado.');},
                handleCheckout(){if(!this.isLoggedIn){this.cartOpen=false;this.navigateTo('login');this.showToast('Inicia sesión para registrar tu pedido.');return;}if(!this.cart.length)return;this.checkoutKey=crypto.randomUUID();this.cartOpen=false;this.checkoutOpen=true;this.checkoutStep=1;},
                async processFinalPayment(){
                    if(this.isCheckingOut)return;this.isCheckingOut=true;
                    try{
                        const order=await HF.request('/tienda/pedidos','POST',{items:this.cart.map(i=>({id:i.id,quantity:i.quantity})),...this.checkoutForm,discount_code:this.discountApplied?'HF10':null,checkout_key:this.checkoutKey});
                        this.orderNumber=order.id;this.lastPurchaseSummary=order.items;this.cart=[];this.discountApplied=false;this.checkoutOpen=false;this.orderSuccessOpen=true;
                        await this.refreshPublic();await this.refreshPrivate();
                    }catch(e){this.showToast(e.message);}finally{this.isCheckingOut=false;}
                },
                openInvoice(order) { this.selectedOrder = order; this.invoiceModalOpen = true; },
                
                downloadInvoice(order=this.selectedOrder) {
                    if(!order)return;
                    try {
                        const {jsPDF}=window.jspdf;const doc=new jsPDF();
                        doc.setFont('courier','bold');doc.setFontSize(24);doc.text('HFSTUDIOS',105,20,{align:'center'});
                        doc.setFont('courier','normal');doc.setFontSize(10);doc.text('Comprobante de pedido · Sin valor fiscal',105,28,{align:'center'});
                        doc.setFontSize(11);doc.text('Cliente: '+(order.user||this.user.name),20,42);
                        doc.setFontSize(9);doc.text('Folio: '+order.id,20,51);doc.text('Fecha: '+order.date,20,59);doc.text('Estado: '+order.status,20,67);
                        doc.autoTable({startY:76,head:[['CANT.','PRODUCTO','PRECIO MXN','TOTAL MXN']],body:order.items.map(i=>[i.quantity,i.name,Number(i.price).toFixed(2),(i.price*i.quantity).toFixed(2)]),theme:'striped',styles:{font:'courier'},headStyles:{fillColor:[20,20,20]},margin:{left:20,right:20}});
                        let y=doc.lastAutoTable.finalY+16;if(y>260){doc.addPage();y=25;}
                        doc.setFontSize(10);if(order.discount)doc.text('Descuento: $'+Number(order.discount).toFixed(2)+' MXN',20,y-7);
                        doc.setFont('courier','bold');doc.setFontSize(14);doc.text('Total del pedido:',20,y);doc.text('$'+Number(order.total).toFixed(2)+' MXN',190,y,{align:'right'});
                        doc.setFont('courier','normal');doc.setFontSize(9);doc.text('Este comprobante no acredita un cobro ni sustituye una factura fiscal.',20,y+12);
                        doc.save('HFSTUDIOS_'+order.id+'.pdf');this.showToast('Comprobante descargado.');
                    } catch(e){this.showToast('No se pudo generar el comprobante: '+e.message);}
                },

                async handlePublishSubmit(){if(this.isSaving)return;this.isSaving=true;try{await HF.request('/tienda/publicaciones','POST',this.publishForm);this.publishForm={name:'',price:'',description:'',image:''};await this.refreshPrivate();this.showToast('Publicación guardada.');this.navigateTo('mis-publicaciones');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async deleteUserProduct(index){if(!confirm('¿Eliminar publicación?'))return;try{await HF.request('/tienda/publicaciones/'+this.userProducts[index].id,'DELETE');await this.refreshPrivate();this.showToast('Publicación eliminada.');}catch(e){this.showToast(e.message);}},
                openEditModal(product,index){this.editingIndex=index;this.editForm={...product};this.editModalOpen=true;},
                async saveEdit(){if(this.isSaving)return;this.isSaving=true;try{await HF.request('/tienda/publicaciones/'+this.editForm.id,'PUT',this.editForm);await this.refreshPrivate();this.editModalOpen=false;this.showToast('Publicación actualizada.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async handleLogin(){
                    if(this.isLoggingIn)return;this.isLoggingIn=true;
                    try{const result=await HF.request('/login','POST',this.loginForm);this.setSession(result.user);this.loginForm={email:'',password:''};await this.refreshPublic();await this.refreshPrivate();this.showToast('Sesión iniciada.');if(this.isStaff)location.href='/crm';else this.navigateTo('perfil');}
                    catch(e){this.showToast(e.message);}finally{this.isLoggingIn=false;}
                },
                async handleLogout(){try{await HF.request('/logout','POST',{});this.setSession(null);this.userOrders=[];this.userProducts=[];this.adminOrdersList=[];this.adminInvoicesList=[];this.adminStats={sales:'$0',orders:0,users:0};this.navigateTo('inicio');await this.refreshPublic();this.showToast('Sesión finalizada.');}catch(e){this.showToast(e.message);}},
                async handleRegistro(){if(this.isSaving)return;this.isSaving=true;try{const result=await HF.request('/registro','POST',this.registroForm);this.setSession(result.user);this.registroForm={name:'',email:'',password:''};await this.refreshPrivate();this.navigateTo('perfil');this.showToast('Cuenta creada.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async handleContactSubmit(){try{await HF.request('/tienda/contacto','POST',this.contactForm);this.contactForm={name:'',email:'',message:''};this.showToast('Mensaje guardado para atención del equipo.');}catch(e){this.showToast(e.message);}},
                async addComment(){try{this.comments=await HF.request('/tienda/comentarios','POST',{text:this.newComment});this.newComment='';this.showToast('Reseña guardada.');}catch(e){this.showToast(e.message);}},
                async handleOfferSubmit(){try{const result=await HF.request('/tienda/subastas/'+this.auction.id+'/ofertas','POST',{amount:this.auction.newOffer});this.auction={...result,newOffer:''};this.showToast('Oferta registrada.');}catch(e){this.showToast(e.message);await this.refreshPublic().catch(()=>{});}},
                async changeOrderStatus(order,status){try{await HF.request('/tienda/pedidos/'+order.database_id+'/estado','PUT',{status});await this.refreshPrivate();await this.refreshPublic();this.showToast('Estado actualizado.');}catch(e){this.showToast(e.message);}},
                openProductEditor(product=null){this.productEditingId=product?.id||null;this.productForm=product?{...product}:{name:'',color:'',category:'Hoodie',price:'',originalPrice:'',stock:0,image:'',badge:'',description:'',active:true};this.productEditorOpen=true;},
                async saveProduct(){if(this.isSaving)return;this.isSaving=true;try{const body={...this.productForm,stock:Number(this.productForm.stock),originalPrice:this.productForm.originalPrice||null};await HF.request('/tienda/productos'+(this.productEditingId?'/'+this.productEditingId:''),this.productEditingId?'PUT':'POST',body);await this.refreshPublic();this.productEditorOpen=false;this.showToast('Producto guardado.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async toggleProduct(product){try{await HF.request('/tienda/productos/'+product.id+'/visibilidad','PATCH',{});await this.refreshPublic();this.showToast('Visibilidad actualizada.');}catch(e){this.showToast(e.message);}}
            }));
        });
```

### 44. resources/js/app.js

**Base CRM.** Desde original: Reemplazar completo. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/resources/js/app.js`.

```javascript
import Alpine from 'alpinejs';
import { jsPDF } from 'jspdf';
import { applyPlugin } from 'jspdf-autotable';
applyPlugin(jsPDF);
window.jspdf = { jsPDF };
window.Alpine = Alpine;
Alpine.start();
```

### 45. resources/views/crm/index.blade.php

**U2.** Desde original: Crear. Desde CRM anterior: Reemplazar completo. Destino: `HFSTUDIOS/resources/views/crm/index.blade.php`.

```php
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>HFSTUDIOS | CRM</title>
    <link rel="stylesheet" href="/css/crm.css">
    <script src="/js/http.js" defer></script><script src="/js/crm.js" defer></script>
</head>
<body>
<header><a href="/" class="brand">HFSTUDIOS <small>CRM</small></a><div><a href="/scm">Inventario SCM</a> · {{ auth()->user()->name }} · {{ auth()->user()->role }} <button id="logout">Cerrar sesión</button></div></header>
<div class="shell">
    <aside><p>RELACIONES CON CLIENTES</p><nav>
        <button data-tab="dashboard" class="selected">Dashboard</button><button data-tab="clients">Clientes</button>
        <button data-tab="activity">Mi actividad</button><button data-tab="contacts">Mensajes de contacto</button>
        @if(auth()->user()->role === 'admin')<button data-tab="users">Equipo y permisos</button>@endif
        <a href="/">Volver a la tienda</a>
    </nav><small>Seguimiento centralizado.<br>Alertas tras 30 días sin contacto.</small></aside>
    <main>
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
        @if(auth()->user()->role === 'admin')
        <section id="users" class="tab" hidden><div class="heading"><div><span class="eyebrow">ADMINISTRACIÓN</span><h1>Equipo y permisos</h1></div></div><div class="two-col"><article><h2>Crear cuenta del equipo</h2><form id="user-form">
            <label>Nombre<input name="name" required minlength="2" maxlength="120"></label><label>Correo<input name="email" type="email" required></label>
            <label>Contraseña<input name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></label>
            <label>Rol<select name="role"><option value="usuario">Usuario: gestiona CRM</option><option value="admin">Admin: CRM, equipo y tienda</option></select></label><button class="primary">Crear usuario</button></form></article>
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
</div>
<dialog id="client-dialog"><form id="client-form"><div class="heading"><h2 id="client-form-title">Nuevo cliente</h2><button type="button" id="close-client">Cerrar</button></div>
    <label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label><label>Correo<input name="correo" type="email" required maxlength="255"></label>
    <label>Teléfono<input name="telefono" maxlength="25" placeholder="+52 449 123 4567"></label><label>Empresa<input name="empresa" maxlength="150"></label>
    <div class="two-col"><label>Estado<select name="estado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></label><label>Etapa CRM<select name="etapa_crm"><option>Prospecto</option><option>Activo</option><option>Frecuente</option><option>Inactivo</option></select></label></div>
    <p id="client-error" role="alert"></p><button class="primary">Guardar cliente</button>
</form></dialog>
</body></html>
```

### 46. resources/views/scm/index.blade.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/resources/views/scm/index.blade.php`.

```php
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
```

### 47. resources/views/welcome.blade.php

**U2.** Desde original: Reemplazar completo. Desde CRM anterior: Reemplazar completo. Destino: `HFSTUDIOS/resources/views/welcome.blade.php`.

```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HFSTUDIOS - Acceso Exclusivo</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">



    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', sans-serif; }
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
        @keyframes marquee { 0% { transform: translateX(0%); } 100% { transform: translateX(-50%); } }
        .marquee-content { animation: marquee 20s linear infinite; }
        .product-card:hover .hover-overlay { opacity: 1; transform: translateY(0); }
        .hover-overlay { opacity: 0; transform: translateY(20px); transition: all 0.3s ease; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { animation: spin 1s linear infinite; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
        .grayscale-map { filter: grayscale(100%); }
    </style>
</head>
<body class="bg-white antialiased" x-data="app">

    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full" class="fixed top-6 right-6 z-[90] bg-black text-white px-6 py-4 shadow-2xl max-w-sm" x-cloak>
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-semibold text-sm" x-text="toast.message"></p>
        </div>
    </div>

    <div x-show="productDetailOpen" x-cloak class="fixed inset-0 z-[85] overflow-y-auto">
        <div @click="productDetailOpen = false" x-show="productDetailOpen" x-transition.opacity class="fixed inset-0 bg-black/80"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="productDetailOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-6xl w-full grid md:grid-cols-2 gap-0 shadow-2xl">
                <button @click="productDetailOpen = false" class="absolute top-4 right-4 z-10 w-10 h-10 flex items-center justify-center bg-white/90 hover:bg-white rounded-full text-black font-bold text-2xl shadow-lg">×</button>
                <div class="bg-[#F4F4F4] flex items-center justify-center p-8 min-h-[500px]">
                    <img :src="selectedProduct.image" :alt="selectedProduct.name" class="w-full h-auto object-cover max-h-[600px]">
                </div>
                <div class="p-8 md:p-12 flex flex-col justify-center">
                    <span x-show="selectedProduct.badge" :class="selectedProduct.badge && selectedProduct.badge.includes('PROMO') ? 'bg-black text-white' : 'bg-white text-black'" class="inline-block text-[9px] font-bold uppercase tracking-wider px-2 py-1 mb-4 w-fit"><span x-text="selectedProduct.badge"></span></span>
                    <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tight mb-3" x-text="selectedProduct.name"></h2>
                    <p class="text-sm text-gray-500 uppercase tracking-wider mb-4" x-text="selectedProduct.color"></p>
                    <div class="flex items-end gap-3 mb-6">
                        <p class="text-4xl font-black text-red-600" x-text="'$' + selectedProduct.price + ' MXN'"></p>
                        <template x-if="selectedProduct.originalPrice">
                            <p class="text-lg font-bold text-gray-400 line-through mb-1" x-text="'$' + selectedProduct.originalPrice + ' MXN'"></p>
                        </template>
                    </div>
                    <p class="text-sm text-gray-700 leading-relaxed mb-8" x-text="selectedProduct.description"></p>
                    <div class="space-y-4">
                        <button @click="addToCart(selectedProduct); productDetailOpen = false" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Agregar al Carrito</button>
                        <button @click="productDetailOpen = false" class="w-full border-2 border-black text-black py-4 text-sm font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Regresar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="orderSuccessOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div @click="orderSuccessOpen = false; navigateTo('perfil')" x-show="orderSuccessOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="orderSuccessOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-md w-full p-12 text-center shadow-2xl">
            <div class="w-24 h-24 mx-auto mb-6 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-3xl font-black uppercase tracking-tight mb-3">Pedido registrado</h2>
            <p class="text-gray-600 mb-2">Tu pedido está pendiente de pago y revisión por el equipo.</p>
            <p class="text-sm font-mono bg-gray-100 inline-block px-4 py-2 mb-8" x-text="orderNumber"></p>
            <button @click="orderSuccessOpen = false; navigateTo('perfil')" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Ver en Mi Perfil</button>
        </div>
    </div>

    <div x-show="invoiceModalOpen" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4">
        <div @click="invoiceModalOpen = false" x-show="invoiceModalOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="invoiceModalOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-md w-full p-12 shadow-2xl">
            <button @click="invoiceModalOpen = false" class="absolute top-4 right-4 text-2xl font-bold hover:text-gray-500 transition">×</button>
            <div class="text-center mb-8 border-b-2 border-black pb-4">
                <h2 class="text-3xl font-black tracking-tighter">HFSTUDIOS</h2>
                <p class="text-[10px] uppercase tracking-widest text-gray-500 mt-1">Comprobante de pedido · Sin valor fiscal</p>
            </div>
            <template x-if="selectedOrder">
                <div>
                    <div class="flex justify-between mb-6 text-xs bg-gray-50 p-4 border border-gray-200">
                        <div>
                            <p class="font-bold uppercase text-gray-400 mb-1">Cliente:</p>
                            <p class="font-semibold uppercase" x-text="selectedOrder?.user || user.name || 'Invitado'"></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold uppercase text-gray-400 mb-1">Detalles:</p>
                            <p class="font-mono">Folio: <span x-text="selectedOrder.id"></span></p>
                            <p x-text="selectedOrder.date"></p>
                        </div>
                    </div>
                    <div class="mb-6 max-h-48 overflow-y-auto custom-scroll pr-2">
                        <p class="text-[10px] font-bold uppercase text-gray-400 mb-2 border-b border-gray-200 pb-1">Productos</p>
                        <template x-for="item in selectedOrder.items" :key="item.name">
                            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                                <span class="uppercase font-medium text-gray-800 text-xs" x-text="(item.quantity || 1) + 'x ' + item.name"></span>
                                <span class="font-mono font-bold text-xs" x-text="'$' + ((item.price * (item.quantity || 1)).toLocaleString())"></span>
                            </div>
                        </template>
                    </div>
                    <div class="border-t-2 border-black pt-4 mb-8 flex justify-between items-center text-xl font-black uppercase">
                        <span>Total del pedido</span>
                        <span class="text-red-600 font-mono" x-text="'$' + (selectedOrder.total || 0).toLocaleString() + ' MXN'"></span>
                    </div>
                    <button @click="downloadInvoice()" class="w-full border-2 border-black text-black py-4 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">
                        Descargar PDF
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div @click="editModalOpen = false" x-show="editModalOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="editModalOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-lg w-full p-10 shadow-2xl">
            <button @click="editModalOpen = false" class="absolute top-4 right-4 text-2xl font-bold hover:text-gray-500 transition">×</button>
            <h2 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Editar Publicación</h2>
            <form @submit.prevent="saveEdit()" novalidate class="space-y-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre del artículo</label>
                    <input type="text" x-model="editForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Precio (MXN)</label>
                    <input type="number" x-model="editForm.price" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">URL de la Foto</label>
                    <input type="text" x-model="editForm.image" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Descripción</label>
                    <textarea x-model="editForm.description" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none"></textarea>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" @click="editModalOpen = false" class="w-1/3 border-2 border-black text-black py-4 text-sm font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Cancelar</button>
                    <button type="submit" class="w-2/3 bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="bg-[#F0EBE0] overflow-hidden relative h-6">
        <div class="flex whitespace-nowrap marquee-content">
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
        </div>
    </div>

    <nav x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm">
        <div class="max-w-screen-2xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">

                <div class="hidden lg:flex items-center gap-6">
                    <button @click="navigateTo('catalogo')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Catálogo</button>
                    <button @click="navigateTo('campana')" class="text-xs font-bold uppercase tracking-wider text-blue-600 hover:opacity-60 transition">Campaña</button>
                    <button @click="navigateTo('promociones-front')" class="text-xs font-bold uppercase tracking-wider text-red-600 hover:opacity-60 transition">Promos</button>
                    <button @click="navigateTo('comunidad')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Comunidad</button>
                </div>

                <div class="absolute left-1/2 transform -translate-x-1/2">
                    <button @click="navigateTo('catalogo')" class="text-2xl md:text-3xl font-black tracking-tighter">HFSTUDIOS</button>
                </div>

                <div class="hidden lg:flex items-center gap-5">
                    <button @click="navigateTo('nosotros')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Nosotros</button>
                    <button x-show="!isLoggedIn" @click="navigateTo('login')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Ingresar</button>

                    <template x-if="isLoggedIn && !isAdmin">
                        <div class="flex items-center gap-5">
                            <button @click="navigateTo('subasta')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition"><i class="fas fa-gavel mr-1"></i> Subastas</button>
                            <button @click="navigateTo('mis-publicaciones')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Publicar</button>
                            <button @click="navigateTo('perfil')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Perfil</button>
                        </div>
                    </template>

                    <button x-show="isLoggedIn && isStaff" @click="navigateTo('crm')" class="text-xs font-bold">CRM</button>
                    <a x-show="isLoggedIn && isStaff" href="/scm" class="font-bold">SCM</a>
                    <button x-show="isLoggedIn && isAdmin" @click="navigateTo('admin')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition bg-black text-white px-2 py-1">Admin</button>

                    <button @click="cartOpen = true" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition relative">
                        <i class="fas fa-shopping-cart mr-2"></i>Cart (<span x-text="cartTotalItems"></span>)
                        <span x-show="cartTotalItems > 0" class="absolute -top-1 -right-1 w-2 h-2 bg-black rounded-full animate-pulse"></span>
                    </button>
                </div>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-sm font-bold"><span x-show="!mobileMenuOpen">MENÚ</span><span x-show="mobileMenuOpen">CERRAR</span></button>
            </div>
        </div>
    </nav>

    <div x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 bg-black z-40 lg:hidden overflow-y-auto" x-cloak>
        <div class="flex flex-col items-center justify-center min-h-full py-12 space-y-6 text-white">
            <button @click="navigateTo('catalogo'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Catálogo</button>
            <button @click="navigateTo('campana'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider text-blue-400">Campaña</button>
            <button @click="navigateTo('promociones-front'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider text-red-500">Promos</button>
            <button @click="navigateTo('comunidad'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Comunidad</button>
            <button @click="navigateTo('nosotros'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Nosotros</button>

            <button x-show="!isLoggedIn" @click="navigateTo('login'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6 mt-2">Iniciar sesión</button>

            <template x-if="isLoggedIn && !isAdmin">
                <div class="flex flex-col items-center space-y-6 border-t border-gray-800 pt-6 w-full">
                    <button @click="navigateTo('subasta'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Subastas</button>
                    <button @click="navigateTo('mis-publicaciones'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Mis Publicaciones</button>
                    <button @click="navigateTo('perfil'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Perfil</button>
                </div>
            </template>

            <button x-show="isLoggedIn && isStaff" @click="navigateTo('crm')" class="text-xl font-bold">CRM</button>
                    <a x-show="isLoggedIn && isStaff" href="/scm" class="font-bold">SCM</a>
            <button x-show="isLoggedIn && isAdmin" @click="navigateTo('admin'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6">Admin Panel</button>
            <button @click="cartOpen = true; mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6">Carrito (<span x-text="cartTotalItems"></span>)</button>
        </div>
    </div>

    <div x-show="cartOpen" class="fixed inset-0 z-[60]" x-cloak>
        <div @click="cartOpen = false" x-show="cartOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="cartOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-2xl font-black uppercase tracking-tight">Tu Carrito</h2>
                <button @click="cartOpen = false" class="text-3xl font-bold hover:opacity-60">×</button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 custom-scroll">
                <template x-if="cart.length === 0">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-24 w-24 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        <p class="text-gray-500 text-sm uppercase tracking-wider mb-4 font-semibold">Tu carrito está vacío</p>
                        <button @click="cartOpen = false" class="text-xs font-bold uppercase tracking-wider underline hover:no-underline">Continuar Comprando</button>
                    </div>
                </template>
                <div class="space-y-6">
                    <template x-for="(item, index) in cart" :key="item.cartItemId || item.id">
                        <div class="flex gap-4 pb-6 border-b border-gray-200">
                            <img :src="item.image" class="w-24 h-32 object-cover bg-gray-100 flex-shrink-0">
                            <div class="flex-1 min-w-0 flex flex-col">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-sm font-bold uppercase tracking-wide mb-1 truncate" x-text="item.name"></h3>
                                        <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-2" x-text="item.color"></p>
                                    </div>
                                    <button @click="removeFromCart(index)" class="text-xs text-gray-400 hover:text-red-600 font-semibold transition self-start"><i class="fas fa-trash"></i></button>
                                </div>
                                <div class="mt-auto flex items-center justify-between">
                                    <div class="flex items-center border border-gray-200 rounded-sm">
                                        <button @click="decreaseQty(index)" class="px-3 py-1 text-gray-500 hover:bg-gray-100 transition">-</button>
                                        <span class="px-2 py-1 text-xs font-bold font-mono text-center min-w-[2rem]" x-text="item.quantity || 1"></span>
                                        <button @click="increaseQty(index)" class="px-3 py-1 text-gray-500 hover:bg-gray-100 transition">+</button>
                                    </div>
                                    <p class="text-base font-bold text-red-600" x-text="'$' + ((item.price || 0) * (item.quantity || 1)).toLocaleString() + ' MXN'"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="border-t border-gray-200 p-6 space-y-4 bg-gray-50" x-show="cart.length > 0">
                <div class="flex gap-2 mb-4">
                    <input type="text" x-model="discountCode" placeholder="Código de descuento" class="flex-1 px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-black uppercase bg-white">
                    <button @click="applyDiscount()" class="bg-gray-800 text-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-black transition">Aplicar</button>
                </div>

                <div class="flex items-center justify-between text-lg font-bold">
                    <span class="uppercase tracking-wider">Subtotal</span>
                    <div class="text-right">
                        <template x-if="discountApplied">
                            <span class="text-xs text-gray-400 line-through block" x-text="'$' + cartTotalSinDescuento.toLocaleString() + ' MXN'"></span>
                        </template>
                        <span class="text-red-600" x-text="'$' + cartTotal.toLocaleString() + ' MXN'"></span>
                    </div>
                </div>
                <template x-if="discountApplied">
                    <p class="text-[10px] uppercase font-bold text-green-600 tracking-wider text-right">Descuento aplicado con éxito</p>
                </template>

                <button @click="handleCheckout()" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Finalizar Compra</button>
            </div>
        </div>
    </div>

    <div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-[80] overflow-y-auto">
        <div @click="checkoutOpen = false" x-show="checkoutOpen" x-transition.opacity class="absolute inset-0 bg-black/80"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="checkoutOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-2xl w-full p-8 md:p-12 shadow-2xl overflow-hidden">
                <button @click="checkoutOpen = false" class="absolute top-4 right-4 text-2xl font-bold text-gray-400 hover:text-black transition">×</button>

                <div class="flex justify-between mb-12 relative px-4 md:px-12">
                    <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 -translate-y-1/2 z-0"></div>
                    <div class="absolute top-1/2 left-0 h-0.5 bg-black -translate-y-1/2 z-0 transition-all duration-500" :style="'width: ' + ((checkoutStep - 1) * 50) + '%'"></div>

                    <template x-for="step in [1, 2, 3]">
                        <div class="relative z-10 flex flex-col items-center bg-white px-2">
                            <div :class="checkoutStep >= step ? 'bg-black text-white border-black' : 'bg-white text-gray-300 border-gray-200'" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors">
                                <span x-show="checkoutStep <= step" x-text="step"></span>
                                <i x-show="checkoutStep > step" class="fas fa-check text-[10px]"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase mt-2 tracking-widest absolute -bottom-6 w-max text-center" :class="checkoutStep >= step ? 'text-black' : 'text-gray-400'" x-text="step === 1 ? 'Dirección' : step === 2 ? 'Pago' : 'Confirma'"></span>
                        </div>
                    </template>
                </div>

                <div x-show="checkoutStep === 1">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Datos de Envío</h3>
                    <form @submit.prevent="checkoutStep = 2" class="space-y-4">
                        <input type="text" required x-model="checkoutForm.address" placeholder="Calle y número" class="w-full border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors">
                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" required x-model="checkoutForm.city" placeholder="Ciudad" class="border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors">
                            <input type="text" required pattern="[0-9]{5}" maxlength="5" x-model="checkoutForm.zip" placeholder="C.P." class="border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors font-mono">
                        </div>
                        <button type="submit" class="w-full bg-black text-white py-4 mt-8 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Siguiente</button>
                    </form>
                </div>

                <div x-show="checkoutStep === 2" style="display:none;">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Registro del pedido</h3>
                    <div class="bg-gray-50 border border-gray-200 p-6"><h4 class="font-bold mb-3">Pedido pendiente de pago</h4><p class="text-sm">Al confirmar, se guardará el pedido y se reservarán las existencias. El equipo coordinará el pago y envío. No se cobra ninguna tarjeta desde esta pantalla.</p></div>

                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <button @click="checkoutStep = 1" class="border-2 border-black text-black py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition">Atrás</button>
                        <button @click="checkoutStep = 3" class="bg-black text-white py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Revisar</button>
                    </div>
                </div>

                <div x-show="checkoutStep === 3" style="display:none;">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Resumen Final</h3>
                    <div class="space-y-4 mb-8">
                        <div class="border border-gray-200 bg-gray-50 p-4">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Enviar a:</p>
                                <button @click="checkoutStep = 1" class="text-xs text-blue-500 hover:underline font-bold">Editar</button>
                            </div>
                            <p class="text-sm font-bold uppercase" x-text="checkoutForm.address || 'PENDIENTE'"></p>
                            <p class="text-xs text-gray-500 uppercase mt-1" x-text="(checkoutForm.city || 'Ciudad') + ', CP: ' + (checkoutForm.zip || '00000')"></p>
                        </div>
                        <div class="border border-gray-200 bg-gray-50 p-4">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Pago:</p>
                                <button @click="checkoutStep = 2" class="text-xs text-blue-500 hover:underline font-bold">Editar</button>
                            </div>
                            <p class="text-sm font-bold uppercase" x-text="'Pendiente de pago'"></p>
                        </div>
                        <div class="border border-gray-200 p-4">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Artículos (<span x-text="cartTotalItems"></span>)</p>
                            <div class="max-h-32 overflow-y-auto custom-scroll pr-2 space-y-2 mb-4">
                                <template x-for="item in cart" :key="item.cartItemId || item.id">
                                    <div class="flex justify-between text-xs">
                                        <span class="truncate pr-2 font-medium uppercase" x-text="(item.quantity || 1) + 'x ' + item.name"></span>
                                        <span class="font-mono font-bold" x-text="'$' + ((item.price || 0) * (item.quantity || 1)).toLocaleString()"></span>
                                    </div>
                                </template>
                            </div>
                            <div class="flex justify-between items-center text-lg font-black uppercase border-t border-black pt-3">
                                <span>Total a pagar</span>
                                <span class="text-red-600" x-text="'$' + cartTotal.toLocaleString() + ' MXN'"></span>
                            </div>
                        </div>
                    </div>
                    <button :disabled="isCheckingOut" @click="processFinalPayment()" class="w-full bg-black text-white py-5 text-sm font-black uppercase tracking-widest hover:bg-gray-800 transition flex justify-center items-center">
                        <span x-show="!isCheckingOut">Registrar pedido</span>
                        <div x-show="isCheckingOut" class="flex gap-2 items-center">
                            <svg class="w-5 h-5 spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Procesando...</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="currentRoute === 'inicio'" x-cloak>
        <section class="relative h-screen overflow-hidden">
            <div class="absolute inset-0">
                <img src="https://images.unsplash.com/photo-1550639525-c97d455acf70?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80"></div>
            </div>
            <div class="relative z-10 flex items-center justify-between px-6 md:px-12 py-6">
                <h1 class="text-2xl md:text-3xl font-black tracking-tighter text-white">HFSTUDIOS</h1>
                <button @click="navigateTo('login')" class="bg-white hover:bg-gray-200 text-black px-6 py-2 text-sm font-bold uppercase tracking-wider transition">Iniciar sesión</button>
            </div>
            <div class="relative z-10 flex flex-col items-center justify-center h-full px-6 pb-32">
                <div class="max-w-3xl text-center">
                    <h2 class="text-4xl md:text-6xl lg:text-7xl font-black uppercase tracking-tight text-white mb-6 leading-tight">ACCESO EXCLUSIVO<br>AL DROP SS26.</h2>
                    <p class="text-lg md:text-xl text-gray-300 mb-12 font-medium">Todo el catálogo con 20% de descuento por tiempo limitado.</p>
                    <form @submit.prevent="handleLandingSubmit()" class="flex flex-col sm:flex-row gap-4 max-w-2xl mx-auto">
                        <input type="email" required x-model="landingEmail" placeholder="Tu Email" class="flex-1 px-6 py-4 text-lg bg-white/90 border-2 border-white/20 focus:border-white focus:outline-none text-black">
                        <button type="submit" class="bg-black hover:bg-gray-800 text-white border-2 border-white px-8 py-4 text-lg font-black uppercase tracking-widest transition whitespace-nowrap">Entrar a la tienda <i class="fas fa-chevron-right ml-2"></i></button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'promociones-front'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-4xl mx-auto text-center mb-16">
                <h1 class="text-5xl md:text-6xl font-black uppercase tracking-tight mb-4">Promociones</h1>
                <p class="text-lg text-gray-600 uppercase tracking-wider">Aprovecha nuestras ofertas activas de temporada.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <div class="bg-black text-white p-10 flex flex-col items-center text-center justify-center shadow-xl">
                    <h2 class="text-6xl font-black uppercase mb-2">10% OFF</h2>
                    <p class="text-sm tracking-widest uppercase mb-8 text-gray-400">En toda tu compra</p>
                    <div class="bg-white/10 px-6 py-3 border border-white/30 mb-4">
                        <p class="font-mono text-2xl font-bold tracking-widest">HF10</p>
                    </div>
                    <p class="text-xs text-gray-400">Ingresa este código visual en el carrito.</p>
                </div>
                <div class="bg-[#F0EBE0] text-black p-10 flex flex-col items-center text-center justify-center shadow-xl">
                    <h2 class="text-6xl font-black uppercase mb-2">CAMISETAS</h2>
                    <p class="text-sm tracking-widest uppercase mb-8 text-gray-600">Explora los modelos disponibles</p>
                    <button @click="navigateTo('catalogo'); selectedCategory = 'T-Shirt'" class="bg-black text-white px-8 py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">
                        Ver Colección
                    </button>
                </div>
            </div>
            <div class="max-w-md mx-auto mt-20 bg-gray-50 p-8 border border-gray-200">
                <h3 class="text-center font-bold uppercase tracking-wider mb-4">Prueba tu código aquí</h3>
                <form @submit.prevent="applyDiscount()" class="flex gap-2">
                    <input type="text" x-model="discountCode" placeholder="Código de descuento" class="flex-1 px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none uppercase">
                    <button type="submit" class="bg-black text-white px-6 py-3 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Aplicar</button>
                </form>
            </div>
        </section>
    </div>

  <div x-show="currentRoute === 'campana'" x-cloak>
        <section class="relative h-[80vh] bg-[#111] overflow-hidden flex items-center">
            <div class="absolute inset-0 w-full h-full opacity-50">
                <img src="https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?q=80&w=2000&auto=format&fit=crop" class="w-full h-full object-cover">
            </div>
            <div class="relative z-10 max-w-screen-2xl mx-auto px-6 w-full flex flex-col md:flex-row items-center gap-12">
                <div class="text-white flex-1">
                    <p class="text-red-500 font-bold uppercase tracking-widest mb-4">Nueva Colección - Producto Destacado</p>
                    <h1 class="text-6xl md:text-8xl font-black uppercase tracking-tighter leading-none mb-6">HOODIE<br>GRÁFICO<br>HF</h1>
                    <p class="text-lg text-gray-300 mb-8 max-w-md">Descubre el hoodie gráfico de la colección HF: serigrafía de alta densidad y acabado lavado.</p>
                    <button @click="navigateTo('catalogo')" class="bg-white text-black px-10 py-4 text-sm font-black uppercase tracking-widest hover:bg-gray-200 transition">
                        Comprar Ahora
                    </button>
                </div>
                <div class="hidden md:block flex-1">
                    <img src="https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800&auto=format&fit=crop" class="w-2/3 ml-auto shadow-2xl rotate-3 hover:rotate-0 transition duration-500">
                </div>
            </div>
        </section>
        <section class="py-20 bg-gray-50">
            <div class="max-w-screen-2xl mx-auto px-6">
                <h2 class="text-3xl font-black uppercase tracking-tight text-center mb-12">Lo que dice nuestra comunidad</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"La calidad de los materiales es brutal. Superó por completo mis expectativas. El envío fue rápido."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Roberto M.</p>
                    </div>
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"Increíble el fit de las camisetas oversized. Ya me pedí tres colores diferentes."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Ana S.</p>
                    </div>
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"Me encanta el diseño minimalista de la marca. Una estética de lujo a precio accesible."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Diego L.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'comunidad'" x-cloak>
        <section class="max-w-screen-xl mx-auto px-6 py-20">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-4">Comunidad HF</h1>
                <p class="text-gray-600 uppercase tracking-wider text-sm">Únete a la conversación. Deja tu comentario sobre nuestros drops.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-12">
                <div class="md:col-span-1">
                    <div class="bg-gray-50 p-8 border border-gray-200 sticky top-32">
                        <h3 class="font-black uppercase tracking-tight text-xl mb-6">Deja un comentario</h3>
                        <form @submit.prevent="addComment()" class="space-y-4">
                            <textarea x-model="newComment" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none" placeholder="¿Qué opinas de la nueva colección?"></textarea>
                            <button type="submit" class="w-full bg-black text-white py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Publicar</button>
                        </form>
                    </div>
                </div>
                <div class="md:col-span-2 space-y-6">
                    <template x-for="comment in comments" :key="comment.id || comment.name">
                        <div class="bg-white p-6 border-b border-gray-200 flex gap-4">
                            <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center shrink-0">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm mb-1" x-text="'@' + (comment.name || 'Usuario')"></p>
                                <p class="text-gray-700 text-sm leading-relaxed" x-text="comment.text || 'Sin texto'"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'catalogo'" x-cloak>
        <div class="bg-black text-white p-4 text-center"><p class="text-xs md:text-sm font-black uppercase tracking-[0.2em] animate-pulse">🔥 TODA LA TIENDA TIENE 20% DE DESCUENTO APLICADO 🔥</p></div>
        <section class="max-w-screen-2xl mx-auto px-6 py-12">
            <div class="mb-12">
                <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tight mb-2">Catálogo</h2>
                <div class="mb-8 relative">
                    <input type="text" x-model="searchQuery" @input="selectedCategory = 'All'" placeholder="Buscar productos (ej. Hoodie, Negro)..." class="w-full px-6 py-4 text-sm border-2 border-gray-200 focus:border-black focus:outline-none bg-white">
                    <i class="fas fa-search absolute right-6 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button @click="selectedCategory = 'All'; searchQuery = ''" :class="selectedCategory === 'All' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Todos</button>
                    <button @click="selectedCategory = 'Promociones'; searchQuery = ''" :class="selectedCategory === 'Promociones' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-red-600 border border-red-600'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition"><i class="fas fa-tags"></i> Promociones</button>
                    <button @click="selectedCategory = 'Hoodie'; searchQuery = ''" :class="selectedCategory === 'Hoodie' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Hoodies</button>
                    <button @click="selectedCategory = 'T-Shirt'; searchQuery = ''" :class="selectedCategory === 'T-Shirt' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">T-Shirts</button>
                    <button @click="selectedCategory = 'Pants'; searchQuery = ''" :class="selectedCategory === 'Pants' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Pants</button>
                    <button @click="selectedCategory = 'Accessory'; searchQuery = ''" :class="selectedCategory === 'Accessory' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Accesorios</button>
                </div>
            </div>

            <template x-if="filteredProducts.length === 0">
                <div class="text-center py-20">
                    <svg class="mx-auto h-24 w-24 text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="text-2xl font-black uppercase tracking-tight mb-2">No se encontraron resultados</h3>
                    <button @click="searchQuery = ''; selectedCategory = 'All'" class="inline-block bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition mt-4">Ver todos los productos</button>
                </div>
            </template>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 gap-y-10" x-show="filteredProducts.length > 0">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div class="product-card group cursor-pointer" @click="viewProductDetail(product)">
                        <div class="relative bg-[#F4F4F4] overflow-hidden mb-4">
                            <span x-show="product.badge" :class="product.badge && product.badge.includes('PROMO') ? 'bg-red-600 text-white' : 'bg-black text-white'" class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider px-2 py-1 z-10 shadow-md" x-text="product.badge"></span>
                            <img :src="product.image" class="w-full aspect-[3/4] object-cover group-hover:scale-105 transition duration-500">
                        </div>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide mb-1 truncate" x-text="product.name"></h3>
                            <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-2" x-text="product.color"></p>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-black text-red-600" x-text="'$' + (product.price || 0).toLocaleString() + ' MXN'"></p>
                                <template x-if="product.originalPrice"><p class="text-[10px] font-bold text-gray-400 line-through" x-text="'$' + product.originalPrice.toLocaleString()"></p></template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'mis-publicaciones'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight">Mis Publicaciones</h1>
                    <button @click="navigateTo('publicar')" class="bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">+ Vender Producto</button>
                </div>
                <div class="bg-white border border-gray-200 overflow-hidden shadow-sm">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                                <tr><th class="px-6 py-4">Producto</th><th class="px-6 py-4">Precio</th><th class="px-6 py-4 text-right">Acciones</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-if="userProducts.length === 0">
                                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500 font-medium">No has publicado ningún producto aún.</td></tr>
                                </template>
                                <template x-for="(prod, index) in userProducts" :key="prod.id || index">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-16 h-16 bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0"><img :src="prod.image" class="w-full h-full object-cover"></div>
                                                <div><p class="text-sm font-bold uppercase" x-text="prod.name || 'Sin Título'"></p><p class="text-xs text-gray-500 truncate max-w-[200px]" x-text="prod.description || '...'"></p></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><p class="text-sm font-bold" x-text="'$' + (prod.price || 0).toLocaleString()"></p></td>
                                        <td class="px-6 py-4 text-right space-x-3">
                                            <button @click="openEditModal(prod, index)" class="text-xs font-bold text-blue-600 hover:underline uppercase tracking-wider">Editar</button>
                                            <button @click="deleteUserProduct(index)" class="text-xs font-bold text-red-600 hover:underline uppercase tracking-wider">Eliminar</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="md:hidden divide-y divide-gray-100">
                        <template x-if="userProducts.length === 0">
                            <div class="py-10 text-center text-gray-500 text-sm">No hay publicaciones</div>
                        </template>
                        <template x-for="(prod, index) in userProducts" :key="'mob-'+(prod.id || index)">
                            <div class="p-4 flex gap-4">
                                <div class="w-20 h-24 bg-gray-100 border border-gray-200 flex-shrink-0"><img :src="prod.image" class="w-full h-full object-cover"></div>
                                <div class="flex-1 flex flex-col">
                                    <p class="text-sm font-bold uppercase tracking-tight" x-text="prod.name || 'Prod'"></p>
                                    <p class="font-mono font-bold text-xs mt-1" x-text="'$' + (prod.price || 0).toLocaleString()"></p>
                                    <div class="mt-auto flex justify-between border-t border-gray-100 pt-2">
                                        <button @click="openEditModal(prod, index)" class="text-[10px] font-bold text-blue-600 uppercase">Editar</button>
                                        <button @click="deleteUserProduct(index)" class="text-[10px] font-bold text-red-500 uppercase">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'publicar'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-2xl mx-auto bg-white p-10 shadow-2xl border border-gray-100 relative">
                <button @click="navigateTo('mis-publicaciones')" class="absolute top-6 left-6 text-gray-400 hover:text-black transition"><i class="fas fa-arrow-left text-xl"></i></button>
                <div class="text-center mb-8 border-b border-gray-100 pb-4 mt-2">
                    <h1 class="text-3xl font-black uppercase tracking-tight">Publica tu producto</h1>
                </div>
                <form @submit.prevent="handlePublishSubmit()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre del artículo</label>
                        <input type="text" required x-model="publishForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="Ej. Jordan 1 Retro High">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Precio (MXN)</label>
                        <input type="number" required x-model="publishForm.price" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="2500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">URL de la Foto</label>
                        <input type="text" required x-model="publishForm.image" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="https://ejemplo.com/foto.jpg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Descripción</label>
                        <textarea x-model="publishForm.description" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none" placeholder="Condición, talla, detalles..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Publicar</button>
                </form>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'subasta'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-2">Subasta en Vivo</h1>
                <p class="text-sm text-gray-600 uppercase tracking-wider mb-8">Comunidad HF</p>
                <div class="grid md:grid-cols-2 gap-10">
                    <div class="bg-[#F4F4F4] p-8 border border-gray-200 relative">
                        <span class="absolute top-4 left-4 bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 animate-pulse">En vivo</span>
                        <img :src="auction.product.image" class="w-full h-auto object-cover max-h-[400px]">
                    </div>
                    <div class="flex flex-col justify-between">
                        <div>
                            <h2 class="text-2xl font-black uppercase tracking-tight mb-2" x-text="auction.product.name"></h2>
                            <div class="flex items-center gap-3 mb-6 bg-black text-white w-fit px-4 py-2"><i class="far fa-clock"></i><span class="font-mono text-lg font-bold tracking-widest" x-text="formattedTimer"></span></div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Oferta Actual</p>
                            <p class="text-4xl font-black text-green-600 mb-8" x-text="'$' + (auction.product.currentBid || 0).toLocaleString()"></p>
                            <form @submit.prevent="handleOfferSubmit()" class="mb-8 bg-gray-50 p-6 border border-gray-200">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-3">Hacer Oferta</label>
                                <div class="flex gap-2">
                                    <span class="bg-gray-200 flex items-center px-4 font-bold text-gray-600">$</span>
                                    <input type="number" required min="0.01" step="0.01" x-model="auction.newOffer" class="flex-1 px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none font-bold" placeholder="Monto">
                                    <button type="submit" class="bg-black text-white px-6 font-bold uppercase tracking-widest hover:bg-gray-800 transition">Ofertar</button>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-2">* La oferta debe superar el importe actual y la subasta debe estar abierta.</p>
                            </form>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider mb-3 border-b pb-2">Historial de Ofertas</h3>
                            <ul class="space-y-2 max-h-40 overflow-y-auto custom-scroll">
                                <template x-for="offer in auction.offers" :key="offer.amount || offer.user">
                                    <li class="flex justify-between items-center text-sm p-2 bg-gray-50"><span class="font-semibold text-gray-700" x-text="offer.user || 'Anónimo'"></span><span class="font-black" x-text="'$' + (offer.amount || 0).toLocaleString()"></span></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'admin' && isAdmin" x-cloak class="min-h-screen bg-gray-50 flex flex-col md:flex-row">
        <aside class="w-full md:w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-black uppercase tracking-tighter">HF. ADMIN</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-1" x-text="'HOLA, ' + (user.name || 'ADMIN')"></p>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <button @click="adminTab = 'pedidos'" :class="adminTab === 'pedidos' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-box w-5"></i> Pedidos</button>
                <button @click="adminTab = 'productos'" :class="adminTab === 'productos' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-tags w-5"></i> Productos</button>
                <a href="/scm" class="block px-4 py-3 text-xs font-bold uppercase tracking-widest">Inventario SCM</a>
                <button @click="adminTab = 'clientes'" :class="adminTab === 'clientes' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-users w-5"></i> Clientes</button>
                <button @click="adminTab = 'facturas'" :class="adminTab === 'facturas' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-file-invoice w-5"></i> Comprobantes</button>
            </nav>
            <div class="p-4 border-t border-gray-200 space-y-2">
                <button @click="navigateTo('catalogo')" class="w-full bg-white border border-gray-300 text-black px-4 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-100 transition text-center">Ver Tienda</button>
                <button @click="handleLogout()" class="w-full bg-red-600 text-white px-4 py-3 text-xs font-bold uppercase tracking-widest hover:bg-red-700 transition text-center">Cerrar Sesión</button>
            </div>
        </aside>

        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <div class="mb-8 flex justify-between items-end border-b border-gray-200 pb-4">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight" x-text="adminTab === 'facturas' ? 'Comprobantes' : adminTab"></h1>
                <button x-show="adminTab === 'productos'" @click="openProductEditor()" class="bg-black text-white px-6 py-3 text-xs font-bold">+ Nuevo producto</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" x-show="adminTab === 'pedidos' || adminTab === 'clientes' || adminTab === 'facturas'">
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Valor de pedidos</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.sales"></p>
                </div>
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Pedidos Activos</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.orders"></p>
                </div>
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Clientes CRM</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.users"></p>
                </div>
            </div>

            <div x-show="adminTab === 'pedidos'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">ID Pedido</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="pedido in adminOrdersList" :key="pedido.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm font-bold" x-text="pedido.id"></td>
                                    <td class="px-6 py-4 text-sm uppercase font-semibold" x-text="pedido.user"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="pedido.date"></td>
                                    <td class="px-6 py-4">
                                        <select x-show="pedido.status === 'Pendiente' || pedido.status === 'Enviado'" @change="changeOrderStatus(pedido, $event.target.value); $event.target.value = ''" class="text-xs border p-2"><option value="">Cambiar estado</option><option x-show="pedido.status === 'Pendiente'" value="Enviado">Enviado</option><option x-show="pedido.status === 'Enviado'" value="Entregado">Entregado</option><option x-show="pedido.status === 'Pendiente'" value="Cancelado">Cancelado</option></select>
                                        <span :class="pedido.status === 'Entregado' ? 'bg-green-100 text-green-700' : (pedido.status === 'Enviado' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')" class="px-2 py-1 text-[9px] font-bold uppercase tracking-widest rounded-sm" x-text="pedido.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-sm" x-text="'$' + pedido.total.toLocaleString()"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="adminTab === 'productos'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Producto</th>
                                <th class="px-6 py-4">Categoría</th>
                                <th class="px-6 py-4">Precio</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="prod in allProducts" :key="prod.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img :src="prod.image" class="w-12 h-16 object-cover bg-gray-100 border border-gray-200">
                                            <span class="text-sm font-bold uppercase" x-text="prod.name"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 uppercase tracking-widest font-bold" x-text="prod.category"></td>
                                    <td class="px-6 py-4 font-mono font-bold text-sm text-red-600" x-text="'$' + prod.price.toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="openProductEditor(prod)" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:underline">Editar</button>
                                        <button @click="toggleProduct(prod)" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider" x-text="prod.active ? 'Ocultar' : 'Mostrar'"></button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="adminTab === 'clientes'" class="bg-white p-8 border border-gray-200"><h2 class="text-2xl font-black mb-4">Gestión CRM</h2><p class="mb-6">Clientes, interacciones, etapas, evaluaciones y métricas centralizadas.</p><a href="/crm" class="bg-black text-white px-6 py-3 inline-block">Abrir CRM</a></div>

            <div x-show="adminTab === 'facturas'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Folio del pedido</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Pedido Relacionado</th>
                                <th class="px-6 py-4">Fecha de registro</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Monto</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="factura in adminInvoicesList" :key="factura.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm font-bold" x-text="factura.id"></td>
                                    <td class="px-6 py-4 text-sm uppercase font-semibold" x-text="factura.user"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="factura.orderId"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="factura.date"></td>
                                    <td class="px-6 py-4">
                                        <span :class="factura.status === 'Pagada' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="px-2 py-1 text-[9px] font-bold uppercase tracking-widest rounded-sm" x-text="factura.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-sm" x-text="'$' + factura.total.toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="downloadInvoice(factura)" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:underline"><i class="fas fa-file-pdf mr-1"></i> Descargar PDF</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <div x-show="currentRoute === 'perfil'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-8 border-b border-gray-100 pb-4">Mi Perfil</h1>
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="bg-gray-50 p-8 border border-gray-100 lg:col-span-1 h-fit">
                        <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center text-2xl font-black mb-6">
                            <span x-text="(user.name || 'U').charAt(0).toUpperCase()"></span>
                        </div>
                        <h2 class="text-xl font-black uppercase tracking-tight mb-6">Mis Datos</h2>
                        <div class="space-y-4">
                            <div><p class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Nombre</p><p class="text-base font-semibold uppercase" x-text="user.name || 'Invitado'"></p></div>
                            <div><p class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Email</p><p class="text-base font-semibold" x-text="user.email || 'N/A'"></p></div>
                        </div>
                        <button @click="handleLogout()" class="mt-8 w-full border-2 border-black text-black py-3 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Cerrar Sesión</button>
                    </div>

                    <div class="bg-white p-8 border border-gray-100 lg:col-span-2">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                            <h2 class="text-xl font-black uppercase tracking-tight">Mis Compras</h2>
                            <span class="text-[10px] font-bold bg-gray-100 text-gray-600 px-2 py-1 uppercase tracking-widest" x-text="userOrders.length + ' Órdenes'"></span>
                        </div>
                        <div class="space-y-4 max-h-[500px] overflow-y-auto custom-scroll pr-2">
                            <template x-for="order in userOrders" :key="order.id">
                                <div class="bg-gray-50 p-5 border border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center hover:border-black transition gap-4">
                                    <div>
                                        <p class="font-bold font-mono text-sm mb-1" x-text="order.id"></p>
                                        <div class="flex items-center gap-2 text-[10px] text-gray-500 uppercase tracking-widest font-bold">
                                            <span x-text="order.date"></span><span>•</span>
                                            <span :class="order.status === 'Entregado' ? 'text-green-600' : 'text-blue-600'" x-text="order.status || 'Procesando'"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between md:justify-end gap-6 w-full md:w-auto mt-2 md:mt-0 border-t md:border-t-0 border-gray-200 pt-3 md:pt-0">
                                        <p class="font-black font-mono text-lg" x-text="'$' + (order.total || 0).toLocaleString()"></p>
                                        <button @click="openInvoice(order)" class="text-[10px] font-bold uppercase tracking-widest text-black border border-black px-4 py-2 hover:bg-black hover:text-white transition whitespace-nowrap">
                                            Ver Ticket
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="userOrders.length === 0">
                                <div class="text-center py-16 bg-gray-50 border border-gray-100">
                                    <i class="fas fa-shopping-bag text-4xl text-gray-200 mb-4"></i>
                                    <p class="text-gray-500 text-xs uppercase tracking-widest font-bold">Aún no tienes pedidos.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'registro'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-md mx-auto bg-white p-10 shadow-2xl border border-gray-100">
                <h1 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Crear Cuenta</h1>
                <form @submit.prevent="handleRegistro()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre</label>
                        <input type="text" required x-model="registroForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label>
                        <input type="email" required x-model="registroForm.email" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Contraseña</label>
                        <input type="password" required minlength="12" maxlength="128" x-model="registroForm.password" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <button type="submit" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Registrarme</button>
                </form>
                <div class="mt-6 text-center border-t border-gray-100 pt-6">
                    <p class="text-[10px] uppercase font-bold text-gray-500 tracking-widest">¿Ya tienes cuenta? <button @click="navigateTo('login')" class="text-black font-black hover:underline ml-1">Inicia sesión</button></p>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'login'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-md mx-auto bg-white p-10 shadow-2xl border border-gray-100">
                <h1 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Iniciar Sesión</h1>
                <p class="text-xs text-gray-500 mb-6">Accede con tu cuenta. Los permisos se asignan por el equipo.</p>
                <form @submit.prevent="handleLogin()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label>
                        <input type="email" required x-model="loginForm.email" :disabled="isLoggingIn" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm disabled:opacity-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Contraseña</label>
                        <input type="password" required x-model="loginForm.password" :disabled="isLoggingIn" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm disabled:opacity-50">
                    </div>
                    <button type="submit" :disabled="isLoggingIn" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition disabled:opacity-70 flex items-center justify-center gap-2">
                        <span x-show="!isLoggingIn">Entrar</span>
                        <div x-show="isLoggingIn" class="flex gap-2 items-center"><svg class="w-4 h-4 spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Cargando...</span></div>
                    </button>
                </form>
                <div class="mt-6 text-center border-t border-gray-100 pt-6">
                    <p class="text-[10px] uppercase font-bold text-gray-500 tracking-widest">¿No tienes cuenta? <button @click="navigateTo('registro')" class="text-black font-black hover:underline ml-1">Regístrate</button></p>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'contacto'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-5xl md:text-6xl font-black uppercase tracking-tight mb-8">Contacto</h1>
                <form @submit.prevent="handleContactSubmit()" class="space-y-6 mb-12">
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre</label><input type="text" required x-model="contactForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label><input type="email" required x-model="contactForm.email" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Mensaje</label><textarea x-model="contactForm.message" rows="5" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none"></textarea></div>
                    <button type="submit" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Enviar Mensaje</button>
                </form>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'nosotros'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-24">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-5xl md:text-7xl font-black uppercase tracking-tighter mb-10">Nosotros</h1>
                <div class="space-y-6 text-gray-700 leading-relaxed mb-12 font-medium">
                    <p class="text-xl font-bold border-l-4 border-black pl-4 py-2 text-black">Fundada en 2026, HFSTUDIOS representa la intersección del lujo y la cultura urbana.</p>
                    <h2 class="text-2xl font-black uppercase tracking-tighter mt-12 mb-4 border-b border-gray-100 pb-2 text-black">Misión</h2>
                    <p>Ofrecemos streetwear de alta calidad diseñado para individuos que se niegan a mezclarse con la multitud. Cada pieza es una declaración construida con atención obsesiva a los detalles.</p>
                    <h2 class="text-2xl font-black uppercase tracking-tighter mt-12 mb-4 border-b border-gray-100 pb-2 text-black">Horarios</h2>
                    <div class="bg-gray-50 border border-gray-200 p-6 shadow-sm w-fit">
                        <p class="text-xs font-bold uppercase tracking-widest text-black">Lunes a Sábado <span class="text-gray-500 font-medium ml-4">10:00 - 20:00</span></p>
                    </div>
                </div>
                <div class="mt-16">
                    <h2 class="text-2xl font-black uppercase tracking-tighter mb-6 text-black">Ubicación</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-4">Aguascalientes, México</p>
                    <div class="aspect-video bg-gray-100 border border-gray-200 flex items-center justify-center shadow-sm opacity-60">
                        <div class="text-center"><i class="fas fa-map-marker-alt text-4xl mb-3 text-black"></i><p class="text-[10px] font-bold uppercase tracking-widest">Mapa Interactivo</p></div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="bg-black text-white py-16 mt-auto">
        <div class="max-w-screen-2xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div>
                    <h3 class="text-3xl font-black mb-4 tracking-tighter">HFSTUDIOS</h3>
                    <p class="text-xs text-gray-400 leading-relaxed font-medium">Streetwear premium para quienes se atreven a ser diferentes. Aguascalientes, MX.</p>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Tienda</h4>
                    <ul class="space-y-3 text-xs font-bold uppercase tracking-wider text-gray-300">
                        <li><button @click="navigateTo('catalogo')" class="hover:text-white transition">Catálogo</button></li>
                        <li><button @click="navigateTo('campana')" class="hover:text-white transition">Campaña</button></li>
                        <li><button @click="navigateTo('promociones-front')" class="hover:text-white transition">Promociones</button></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Comunidad</h4>
                    <ul class="space-y-3 text-xs font-bold uppercase tracking-wider text-gray-300">
                        <li><button @click="navigateTo('comunidad')" class="hover:text-white transition">Reseñas</button></li>
                        <li><button @click="navigateTo('nosotros')" class="hover:text-white transition">Nosotros</button></li>
                        <li><button @click="navigateTo('subasta')" class="hover:text-white transition text-red-500">Subastas (C2C)</button></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Newsletter</h4>
                    <form @submit.prevent="subscribeNewsletter()" class="flex shadow-md border border-white/20">
                        <input type="email" required x-model="newsletterEmail" placeholder="TU EMAIL" class="flex-1 bg-white/5 px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-white focus:outline-none focus:bg-white/10 transition">
                        <button type="submit" class="bg-white text-black px-6 py-3 text-sm font-black hover:bg-gray-200 transition">→</button>
                    </form>
                </div>
            </div>
            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">© 2026 HF Studios. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>


    <div x-show="productEditorOpen" x-cloak class="fixed inset-0 z-[130] overflow-y-auto bg-black/70 p-4">
        <form @submit.prevent="saveProduct()" class="bg-white max-w-xl mx-auto my-8 p-8 space-y-4">
            <div class="flex justify-between"><h2 class="font-black text-xl">Producto de catálogo</h2><button type="button" @click="productEditorOpen=false">Cerrar</button></div>
            <label class="block">Nombre<input required x-model="productForm.name" maxlength="120" class="w-full border p-2"></label>
            <label class="block">Color<input x-model="productForm.color" class="w-full border p-2"></label>
            <label class="block">Categoría<select x-model="productForm.category" class="w-full border p-2"><option>Hoodie</option><option>T-Shirt</option><option>Pants</option><option>Accessory</option></select></label>
            <div class="grid grid-cols-2 gap-4"><label>Precio MXN<input required type="number" min="0.01" step="0.01" x-model="productForm.price" class="w-full border p-2"></label><label>Precio original<input type="number" min="0.01" step="0.01" x-model="productForm.originalPrice" class="w-full border p-2"></label></div>
            <label class="block">Existencias<input required type="number" min="0" step="1" x-model="productForm.stock" class="w-full border p-2"></label>
            <label class="block">URL de imagen<input required type="url" x-model="productForm.image" class="w-full border p-2"></label>
            <label class="block">Etiqueta<input x-model="productForm.badge" class="w-full border p-2"></label>
            <label class="block">Descripción<textarea required x-model="productForm.description" class="w-full border p-2"></textarea></label>
            <label><input type="checkbox" x-model="productForm.active"> Visible en catálogo</label>
            <button :disabled="isSaving" class="w-full bg-black text-white p-3">Guardar producto</button>
        </form>
    </div>

    <script src="/js/http.js"></script>
    <script src="/js/storefront.js"></script>
</body>
</html>
```

### 48. routes/web.php

**U2.** Desde original: Reemplazar completo. Desde CRM anterior: Reemplazar completo. Destino: `HFSTUDIOS/routes/web.php`.

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\ScmController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/login', fn () => redirect('/?vista=login'))->name('login');
Route::get('/sesion', [AuthController::class, 'session']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/tienda/productos', [StoreController::class, 'products']);
Route::get('/tienda/comentarios', [StoreController::class, 'comments']);
Route::get('/tienda/subasta', [StoreController::class, 'auction']);
Route::post('/tienda/contacto', [StoreController::class, 'contact'])->middleware('throttle:5,1');
Route::post('/tienda/suscripciones', [StoreController::class, 'subscribe'])->middleware('throttle:5,1');
Route::middleware('auth')->group(function () {
    Route::get('/tienda/pedidos', [StoreController::class, 'orders']);
    Route::post('/tienda/pedidos', [StoreController::class, 'checkout'])->middleware('throttle:20,1');
    Route::get('/tienda/publicaciones', [StoreController::class, 'publications']);
    Route::post('/tienda/publicaciones', [StoreController::class, 'savePublication']);
    Route::put('/tienda/publicaciones/{publication}', [StoreController::class, 'savePublication']);
    Route::delete('/tienda/publicaciones/{publication}', [StoreController::class, 'deletePublication']);
    Route::post('/tienda/comentarios', [StoreController::class, 'comment'])->middleware('throttle:10,1');
    Route::post('/tienda/subastas/{auction}/ofertas', [StoreController::class, 'bid'])->middleware('throttle:20,1');
});
Route::middleware(['auth', 'role:admin,usuario'])->group(function () {
    Route::view('/crm', 'crm.index');
    Route::apiResource('clientes', CrmController::class)->parameters(['clientes' => 'cliente'])->except('destroy');
    Route::put('/clientes/{cliente}/etapa', [CrmController::class, 'etapa']);
    Route::post('/interacciones', [CrmController::class, 'interaction']);
    Route::get('/clientes/{cliente}/interacciones', [CrmController::class, 'history']);
    Route::post('/clientes/{cliente}/evaluaciones', [CrmController::class, 'evaluate']);
    Route::get('/metricas', [CrmController::class, 'metrics']);
    Route::get('/mi-actividad', [CrmController::class, 'activity']);
    Route::get('/contactos', [CrmController::class, 'contacts']);
    Route::post('/contactos/{id}/registrar', [CrmController::class, 'handleContact']);
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/clientes/{cliente}', [CrmController::class, 'destroy']);
    Route::get('/usuarios', [CrmController::class, 'users']);
    Route::post('/usuarios', [CrmController::class, 'createUser']);
    Route::get('/tienda/estadisticas', [StoreController::class, 'stats']);
    Route::post('/tienda/productos', [StoreController::class, 'saveProduct']);
    Route::put('/tienda/productos/{product}', [StoreController::class, 'saveProduct']);
    Route::patch('/tienda/productos/{product}/visibilidad', [StoreController::class, 'toggleProduct']);
    Route::put('/tienda/pedidos/{order}/estado', [StoreController::class, 'orderStatus']);
});

Route::middleware(['auth', 'role:admin,usuario'])->group(function () {
    Route::view('/scm', 'scm.index');
    Route::get('/productos', [ScmController::class, 'products']);
    Route::get('/productos/{producto}', [ScmController::class, 'product'])->withTrashed();
    Route::get('/productos/{producto}/movimientos', [ScmController::class, 'history'])->withTrashed();
    Route::get('/proveedores', [ScmController::class, 'suppliers']);
    Route::post('/inventario/movimiento', [ScmController::class, 'movement']);
    Route::get('/pedidos', [ScmController::class, 'orders']);
    Route::post('/pedidos', [ScmController::class, 'createOrder']);
    Route::put('/pedidos/{pedido}/estado', [ScmController::class, 'orderStatus']);
    Route::get('/scm/estado', [ScmController::class, 'state']);
    Route::get('/scm/reportes', [ScmController::class, 'reports']);
    Route::get('/scm/reportes/csv', [ScmController::class, 'export']);
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/productos', [ScmController::class, 'saveProduct']);
    Route::put('/productos/{producto}', [ScmController::class, 'saveProduct']);
    Route::delete('/productos/{producto}', [ScmController::class, 'deleteProduct']);
    Route::put('/productos/{producto}/estrategia', [ScmController::class, 'strategy']);
    Route::post('/proveedores', [ScmController::class, 'saveSupplier']);
    Route::put('/proveedores/{proveedor}', [ScmController::class, 'saveSupplier']);
    Route::delete('/proveedores/{proveedor}', [ScmController::class, 'deleteSupplier']);
    Route::put('/scm/nivel', [ScmController::class, 'maturity']);
});
```

### 49. tests/Feature/CrmTest.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/tests/Feature/CrmTest.php`.

```php
<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Interaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function client(array $data = []): Cliente
    {
        return Cliente::create([...['nombre' => 'Henry Test', 'correo' => 'henry@example.com', 'estado' => 'activo', 'etapa_crm' => 'Prospecto'], ...$data]);
    }

    private function payload(): array
    {
        return ['nombre' => 'Cliente nuevo', 'correo' => 'nuevo@example.com', 'telefono' => '+52 449 123 4567', 'empresa' => 'HF', 'estado' => 'activo', 'etapa_crm' => 'Prospecto'];
    }

    public function test_guest_and_customer_cannot_access_crm(): void
    {
        $this->getJson('/clientes')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/clientes')->assertForbidden();
        $this->getJson('/crm')->assertForbidden();
    }

    public function test_client_crud_filters_stage_and_delete_permissions(): void
    {
        $user = $this->staff('usuario');
        $this->actingAs($user);
        $id = $this->postJson('/clientes', $this->payload())->assertCreated()->json('id');
        $this->getJson('/clientes/'.$id)->assertOk()->assertJsonPath('nombre', 'Cliente nuevo');
        $this->putJson('/clientes/'.$id, [...$this->payload(), 'nombre' => 'Nombre actualizado'])->assertOk();
        $this->putJson('/clientes/'.$id.'/etapa', ['etapa_crm' => 'Frecuente'])->assertOk();
        $this->getJson('/clientes?q=actualizado&estado=activo&etapa=Frecuente')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/clientes?estado=inactivo')->assertJsonPath('total', 0);
        $this->deleteJson('/clientes/'.$id)->assertForbidden();
        $this->actingAs($this->staff())->deleteJson('/clientes/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('clientes', ['id' => $id]);
    }

    public function test_validation_duplicate_email_and_missing_client(): void
    {
        $this->actingAs($this->staff());
        $this->client();
        $this->postJson('/clientes', [...$this->payload(), 'correo' => 'henry@example.com'])->assertUnprocessable()->assertJsonValidationErrors('correo');
        $this->postJson('/clientes', [...$this->payload(), 'estado' => 'otro', 'etapa_crm' => 'VIP'])->assertUnprocessable()->assertJsonValidationErrors(['estado', 'etapa_crm']);
        $this->getJson('/clientes/9999')->assertNotFound();
        $this->postJson('/interacciones', ['cliente_id' => 9999, 'tipo' => 'llamada', 'descripcion' => 'Llamada inicial', 'fecha' => now()->toISOString()])->assertUnprocessable();
    }

    public function test_interaction_responsible_is_session_user_and_activity_is_private(): void
    {
        $user = $this->staff('usuario');
        $other = $this->staff();
        $client = $this->client();
        $this->actingAs($user);
        $id = $this->postJson('/interacciones', ['cliente_id' => $client->id, 'tipo' => 'reunión', 'descripcion' => 'Reunión inicial', 'fecha' => now()->subMinute()->toISOString(), 'usuario_id' => $other->id])->assertCreated()->assertJsonPath('usuario_id', $user->id)->json('id');
        $this->getJson('/clientes/'.$client->id.'/interacciones')->assertJsonPath('0.id', $id)->assertJsonPath('0.usuario.name', $user->name);
        $this->getJson('/mi-actividad')->assertJsonPath('total', 1);
        $this->actingAs($other)->getJson('/mi-actividad')->assertJsonPath('total', 0);
        $this->postJson('/interacciones', ['cliente_id' => $client->id, 'tipo' => 'correo', 'descripcion' => 'Correo futuro', 'fecha' => now()->addDay()->toISOString()])->assertUnprocessable();
    }

    public function test_metrics_risk_and_evaluations_use_real_data(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = $this->staff();
        $this->actingAs($user);
        $risk = $this->client();
        $recent = $this->client(['correo' => 'recent@example.com']);
        $inactive = $this->client(['correo' => 'inactive@example.com', 'estado' => 'inactivo']);
        Interaccion::create(['cliente_id' => $recent->id, 'usuario_id' => $user->id, 'tipo' => 'correo', 'descripcion' => 'Contacto reciente', 'fecha' => now()->subDays(2)]);
        $this->getJson('/metricas')->assertOk()->assertJsonPath('total', 3)->assertJsonPath('activos', 2)->assertJsonPath('inactivos', 1)->assertJsonPath('interacciones', 1)->assertJsonCount(1, 'clientes_en_riesgo')->assertJsonPath('clientes_en_riesgo.0.id', $risk->id);
        $this->postJson('/clientes/'.$risk->id.'/evaluaciones', ['puntuacion' => 5, 'observaciones' => 'Buena relación'])->assertCreated();
        $this->getJson('/clientes/'.$risk->id)->assertJsonPath('evaluaciones.0.puntuacion', 5);
        $this->postJson('/clientes/'.$risk->id.'/evaluaciones', ['puntuacion' => 6])->assertUnprocessable();
        Interaccion::create(['cliente_id' => $risk->id, 'usuario_id' => $user->id, 'tipo' => 'llamada', 'descripcion' => 'Contacto antiguo', 'fecha' => now()->subDays(31)]);
        $this->getJson('/metricas')->assertJsonCount(1, 'clientes_en_riesgo');
        $this->deleteJson('/clientes/'.$risk->id)->assertNoContent();
        $this->assertDatabaseMissing('interacciones', ['cliente_id' => $risk->id]);
        $this->assertDatabaseMissing('evaluaciones', ['cliente_id' => $risk->id]);
    }

    public function test_public_registration_cannot_escalate_role_and_real_login_works(): void
    {
        $this->postJson('/registro', ['name' => 'Cliente registrado', 'email' => 'TEST@example.com', 'password' => 'ValidPassword123!', 'role' => 'admin'])->assertCreated()->assertJsonPath('user.role', 'cliente');
        $this->assertDatabaseHas('clientes', ['correo' => 'test@example.com']);
        $this->postJson('/logout')->assertOk();
        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'incorrecta', 'role' => 'admin'])->assertUnprocessable();
        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'ValidPassword123!', 'role' => 'admin'])->assertOk()->assertJsonPath('user.role', 'cliente');
        $this->getJson('/usuarios')->assertForbidden();
    }

    public function test_only_admin_can_create_staff(): void
    {
        $this->actingAs($this->staff('usuario'))->postJson('/usuarios', ['name' => 'Operador', 'email' => 'op@example.com', 'password' => 'ValidPassword123!', 'role' => 'usuario'])->assertForbidden();
        $this->actingAs($this->staff())->postJson('/usuarios', ['name' => 'Operador', 'email' => 'op@example.com', 'password' => 'ValidPassword123!', 'role' => 'usuario'])->assertCreated()->assertJsonPath('role', 'usuario');
    }

    public function test_contact_is_persisted_and_convertible_once(): void
    {
        $this->postJson('/tienda/contacto', ['name' => 'Consulta HF', 'email' => 'contact@example.com', 'message' => 'Información sobre envíos'])->assertOk();
        $this->actingAs($this->staff('usuario'));
        $id = $this->getJson('/contactos')->assertOk()->json('data.0.id');
        $this->postJson('/contactos/'.$id.'/registrar')->assertOk();
        $this->assertDatabaseHas('clientes', ['correo' => 'contact@example.com']);
        $this->assertDatabaseCount('interacciones',1);
        $this->postJson('/contactos/'.$id.'/registrar')->assertConflict();
        $this->assertDatabaseCount('interacciones',1);
    }
}
```

### 50. tests/Feature/ScmTest.php

**U2.** Desde original: Crear. Desde CRM anterior: Crear. Destino: `HFSTUDIOS/tests/Feature/ScmTest.php`.

```php
<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScmTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function supplier(): Proveedor
    {
        return Proveedor::create(['nombre' => 'Textiles del Centro', 'contacto' => 'Ana Pérez', 'correo' => (string) Str::uuid().'@example.com', 'telefono' => '+52 449 123 4567']);
    }

    private function product(array $extra = []): Product
    {
        return Product::create(['name' => 'Prenda SCM', 'category' => 'Hoodie', 'description' => 'Prenda para probar logística', 'image' => 'https://example.com/p.jpg', 'price_cents' => 10000, 'stock' => 5, 'active' => true, 'stock_minimo' => 2, 'stock_objetivo' => 10, 'costo_unitario_cents' => 4000, 'estrategia_logistica' => 'PULL', ...$extra]);
    }

    private function payload(array $extra = []): array
    {
        return ['nombre' => 'Prenda nueva', 'descripcion' => 'Prenda del catálogo SCM', 'categoria' => 'Hoodie', 'stock_actual' => 5, 'stock_minimo' => 2, 'stock_objetivo' => 10, 'proveedor_id' => null, 'costo_unitario' => 40.50, 'precio_venta' => 100.90, 'estrategia_logistica' => 'PULL', 'imagen' => 'https://example.com/p.jpg', 'color' => 'Negro', 'activo' => true, 'ubicacion' => 'Estante A', ...$extra];
    }

    private function move(Product $p, array $extra = []): array
    {
        return ['producto_id' => $p->id, 'tipo' => 'salida', 'cantidad' => 2, 'motivo' => 'venta', 'request_key' => (string) Str::uuid(), ...$extra];
    }

    public function test_routes_protect_staff_actions_and_admin_configuration(): void
    {
        $p = $this->product();
        $this->getJson('/productos')->assertUnauthorized();
        $this->actingAs($this->staff('cliente'))->getJson('/scm/reportes')->assertForbidden();
        $this->actingAs($this->staff('usuario'));
        $this->get('/scm')->assertOk()->assertSee('Pedidos SCM');
        $this->getJson('/productos')->assertOk();
        $this->postJson('/productos', $this->payload())->assertForbidden();
        $this->putJson('/productos/'.$p->id.'/estrategia', ['estrategia_logistica' => 'PUSH'])->assertForbidden();
        $this->postJson('/proveedores', [])->assertForbidden();
        $this->putJson('/scm/nivel', [])->assertForbidden();
        $this->postJson('/inventario/movimiento', $this->move($p))->assertCreated();
    }

    public function test_supplier_and_product_crud_preserve_stock_and_catalog(): void
    {
        $this->actingAs($this->staff());
        $s = ['nombre' => 'Proveedor Uno', 'contacto' => 'María López', 'correo' => 'Textiles@Example.com', 'telefono' => '+52 449 123 4567'];
        $id = $this->postJson('/proveedores', $s)->assertCreated()->assertJsonPath('correo', 'textiles@example.com')->json('id');
        $this->postJson('/proveedores', $s)->assertUnprocessable();
        $this->getJson('/proveedores')->assertJsonCount(1);
        $this->putJson('/proveedores/'.$id, [...$s, 'nombre' => 'Proveedor editado'])->assertOk();
        $body = $this->payload(['proveedor_id' => $id]);
        $p = $this->postJson('/productos', $body)->assertCreated()->assertJsonPath('costo_unitario', 40.5)->assertJsonPath('ubicacion', 'Estante A')->json('id');
        $this->getJson('/tienda/productos')->assertJsonPath('0.stock', 5)->assertJsonPath('0.price', 100.9);
        unset($body['stock_actual']);
        $body['nombre'] = 'Prenda editada';
        $this->putJson('/productos/'.$p, $body)->assertOk()->assertJsonPath('nombre', 'Prenda editada')->assertJsonPath('stock_actual', 5);
        $this->assertDatabaseCount('inventarios', 1);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->deleteJson('/proveedores/'.$id)->assertConflict();
        $this->deleteJson('/productos/'.$p)->assertConflict();
    }

    public function test_invalid_product_settings_are_rejected(): void
    {
        $this->actingAs($this->staff());
        $this->postJson('/productos', $this->payload(['stock_objetivo' => 2]))->assertUnprocessable();
        $this->postJson('/productos', $this->payload(['estrategia_logistica' => 'PUSH']))->assertUnprocessable();
        $this->postJson('/productos', $this->payload(['costo_unitario' => 1.234]))->assertUnprocessable();
        $p = $this->product();
        $this->putJson('/productos/'.$p->id, $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_movement_is_atomic_audited_and_idempotent(): void
    {
        $p = $this->product();
        $u = $this->staff('usuario');
        $this->actingAs($u);
        $d = $this->move($p);
        $id = $this->postJson('/inventario/movimiento', $d)->assertCreated()->assertJsonPath('stock_anterior', 5)->assertJsonPath('stock_resultante', 3)->assertJsonPath('usuario_id', $u->id)->json('id');
        $this->postJson('/inventario/movimiento', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->postJson('/inventario/movimiento', [...$d, 'cantidad' => 3])->assertConflict();
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 4]))->assertUnprocessable();
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada']))->assertUnprocessable();
        $this->assertSame(3, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->getJson('/productos/'.$p->id.'/movimientos')->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.usuario.name', $u->name);
    }

    public function test_push_orders_cover_target_without_duplicates_and_stock_changes_on_receipt(): void
    {
        $p = $this->product(['proveedor_id' => $this->supplier()->id, 'estrategia_logistica' => 'PUSH']);
        $this->actingAs($this->staff());
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 3]))->assertCreated();
        $order = Pedido::sole();
        $this->assertTrue($order->automatico);
        $this->assertSame(8, $order->cantidad);
        $this->assertSame(2, $p->fresh()->stock);
        app(InventoryService::class)->checkPush($p->id);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 1]))->assertCreated();
        $this->assertSame(9, $order->fresh()->cantidad);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->putJson('/pedidos/'.$order->id.'/estado', ['estado' => 'surtido'])->assertOk()->assertJsonPath('estado', 'surtido');
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['pedido_id' => $order->id, 'motivo' => 'reposición', 'cantidad' => 9]);
        $count = MovimientoInventario::count();
        $this->putJson('/pedidos/'.$order->id.'/estado', ['estado' => 'surtido'])->assertOk();
        $this->assertSame($count, MovimientoInventario::count());
        $this->assertSame(10, $p->fresh()->stock);
        $this->putJson('/pedidos/'.$order->id.'/estado', ['estado' => 'pendiente'])->assertUnprocessable();
    }

    public function test_pull_uses_manual_orders_and_push_respects_existing_pending_supply(): void
    {
        $p = $this->product(['stock' => 2, 'proveedor_id' => $this->supplier()->id]);
        $this->actingAs($this->staff());
        app(InventoryService::class)->checkPush($p->id);
        $this->assertDatabaseCount('pedidos_scm', 0);
        $d = ['producto_id' => $p->id, 'tipo' => 'reposicion', 'cantidad' => 4, 'request_key' => (string) Str::uuid()];
        $id = $this->postJson('/pedidos', $d)->assertCreated()->assertJsonPath('automatico', false)->json('id');
        $this->postJson('/pedidos', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->assertSame(2, $p->fresh()->stock);
        $this->putJson('/productos/'.$p->id.'/estrategia', ['estrategia_logistica' => 'PUSH'])->assertOk();
        $this->assertSame(4, Pedido::where('automatico', true)->sole()->cantidad);
        $this->getJson('/productos?estrategia=PUSH')->assertJsonPath('total', 1);
        $this->putJson('/productos/'.$p->id.'/estrategia', ['estrategia_logistica' => 'PULL'])->assertOk();
        $this->assertDatabaseCount('pedidos_scm', 2); // Las solicitudes existentes conservan trazabilidad.
    }

    public function test_sale_order_fulfillment_rolls_back_when_stock_is_insufficient(): void
    {
        $p = $this->product(['stock' => 1]);
        $this->actingAs($this->staff('usuario'));
        $id = $this->postJson('/pedidos', ['producto_id' => $p->id, 'tipo' => 'venta', 'cantidad' => 2])->assertCreated()->json('id');
        $this->putJson('/pedidos/'.$id.'/estado', ['estado' => 'surtido'])->assertUnprocessable();
        $this->assertDatabaseHas('pedidos_scm', ['id' => $id, 'estado' => 'pendiente', 'fecha_surtido' => null]);
        $this->assertSame(1, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada', 'motivo' => 'ajuste', 'cantidad' => 1]))->assertCreated();
        $this->putJson('/pedidos/'.$id.'/estado', ['estado' => 'surtido'])->assertOk();
        $this->assertSame(0, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['pedido_id' => $id, 'tipo' => 'salida', 'motivo' => 'venta']);
    }

    public function test_direct_replenishment_creates_manual_completed_order_once_and_blocks_duplicate_pending_supply(): void
    {
        $p = $this->product(['proveedor_id' => $this->supplier()->id]);
        $this->actingAs($this->staff());
        $d = $this->move($p, ['tipo' => 'entrada', 'motivo' => 'reposición', 'cantidad' => 3]);
        $id = $this->postJson('/inventario/movimiento', $d)->assertCreated()->json('id');
        $this->postJson('/inventario/movimiento', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->assertSame('surtido', Pedido::sole()->estado);
        $this->postJson('/pedidos', ['producto_id' => $p->id, 'tipo' => 'reposicion', 'cantidad' => 2])->assertCreated();
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada', 'motivo' => 'reposición']))->assertUnprocessable();
        $this->assertSame(8, $p->fresh()->stock);
    }

    public function test_store_checkout_cancel_and_legacy_stock_edit_have_ledger_entries(): void
    {
        $p = $this->product(['proveedor_id' => $this->supplier()->id, 'estrategia_logistica' => 'PUSH']);
        $this->actingAs($this->staff('cliente'));
        $d = ['items' => [['id' => $p->id, 'quantity' => 3]], 'address' => 'Calle Uno 123', 'city' => 'Aguascalientes', 'zip' => '20000', 'checkout_key' => (string) Str::uuid()];
        $id = $this->postJson('/tienda/pedidos', $d)->assertCreated()->json('database_id');
        $this->assertSame(2, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['order_id' => $id, 'tipo' => 'salida', 'cantidad' => 3]);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->actingAs($this->staff());
        $this->putJson('/tienda/pedidos/'.$id.'/estado', ['status' => 'Cancelado'])->assertOk();
        $this->assertSame(5, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['order_id' => $id, 'tipo' => 'entrada', 'motivo' => 'ajuste']);
        $this->putJson('/tienda/productos/'.$p->id, ['name' => 'Prenda editada', 'category' => 'Hoodie', 'price' => 100, 'stock' => 6, 'image' => 'https://example.com/i.jpg', 'description' => 'Nueva descripción', 'active' => true])->assertOk();
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $p->id, 'referencia' => 'Ajuste desde administración de catálogo', 'stock_resultante' => 6]);
    }

    public function test_reports_count_only_completed_sales_and_export_csv(): void
    {
        $p = $this->product(['stock' => 10]);
        $slow = $this->product(['name' => 'Rotación lenta']);
        $critical = $this->product(['name' => 'Crítico', 'stock' => 1]);
        $this->actingAs($this->staff('cliente'));
        $ids = [];
        foreach ([2, 2, 2] as $n) {
            $ids[] = $this->postJson('/tienda/pedidos', ['items' => [['id' => $p->id, 'quantity' => $n]], 'address' => 'Calle Uno 123', 'city' => 'Aguascalientes', 'zip' => '20000', 'checkout_key' => (string) Str::uuid()])->assertCreated()->json('database_id');
        }
        $this->actingAs($this->staff());
        $this->putJson('/tienda/pedidos/'.$ids[1].'/estado', ['status' => 'Enviado'])->assertOk();
        $this->putJson('/tienda/pedidos/'.$ids[2].'/estado', ['status' => 'Cancelado'])->assertOk();
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 1]))->assertCreated();
        $this->getJson('/scm/reportes')->assertOk()->assertJsonPath('productos_mas_vendidos.0.unidades', 3)->assertJsonPath('total_productos', 3)->assertJsonPath('inventario_critico.0.id', $critical->id)->assertJsonCount(2, 'rotacion_lenta')->assertJsonPath('comparacion.1.productos', 3);
        $csv = $this->get('/scm/reportes/csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Más vendidos', $csv);
        $this->assertStringContainsString('Rotación lenta', $csv);
    }

    public function test_archiving_retains_ledger_and_does_not_sell_archived_products(): void
    {
        $p = $this->product();
        $this->actingAs($this->staff());
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 5, 'motivo' => 'ajuste']))->assertCreated();
        $this->deleteJson('/productos/'.$p->id)->assertNoContent();
        $this->assertSoftDeleted('products', ['id' => $p->id]);
        $this->getJson('/productos')->assertJsonPath('total', 0);
        $this->getJson('/productos?archivados=1')->assertJsonPath('data.0.id', $p->id);
        $this->getJson('/productos/'.$p->id.'/movimientos')->assertJsonPath('total', 2);
        $this->getJson('/tienda/productos')->assertExactJson([]);
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada', 'motivo' => 'ajuste']))->assertNotFound();
    }

    public function test_maturity_requires_evidence_and_persists(): void
    {
        $this->actingAs($this->staff());
        $check = ['catalogo' => true, 'proveedores' => true, 'inventario' => true, 'estrategias' => false, 'pedidos' => false, 'reportes' => false];
        $this->getJson('/scm/estado')->assertJsonPath('nivel_scm', 'Inicial');
        $this->putJson('/scm/nivel', ['nivel_scm' => 'Optimizado', 'checklist' => $check])->assertUnprocessable();
        $this->putJson('/scm/nivel', ['nivel_scm' => 'En desarrollo', 'checklist' => $check])->assertOk()->assertJsonPath('nivel_scm', 'En desarrollo');
        $this->putJson('/scm/nivel', ['nivel_scm' => 'Optimizado', 'checklist' => array_fill_keys(array_keys($check), true)])->assertOk();
        $this->getJson('/scm/estado')->assertJsonPath('nivel_scm', 'Optimizado')->assertJsonPath('checklist.reportes', true);
    }
}
```

### 51. tests/Feature/StoreTest.php

**Base CRM.** Desde original: Crear. Desde CRM anterior: Conservar anterior. Destino: `HFSTUDIOS/tests/Feature/StoreTest.php`.

```php
<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $extra = []): Product
    {
        return Product::create([...['name' => 'Hoodie', 'category' => 'Hoodie', 'price_cents' => 10000, 'stock' => 5, 'image' => 'https://example.com/image.jpg', 'description' => 'Producto de prueba', 'active' => true], ...$extra]);
    }

    private function checkout(Product $p, array $extra = []): array
    {
        return [...['items' => [['id' => $p->id, 'quantity' => 2]], 'address' => 'Calle Uno 123', 'city' => 'Aguascalientes', 'zip' => '20000', 'checkout_key' => (string) Str::uuid(), 'discount_code' => 'HF10'], ...$extra];
    }

    public function test_checkout_recalculates_price_persists_and_is_idempotent(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create());
        $body = $this->checkout($p, ['price' => 1, 'total' => 1]);
        $folio = $this->postJson('/tienda/pedidos', $body)->assertCreated()->assertJsonPath('total', 180)->json('id');
        $this->postJson('/tienda/pedidos', $body)->assertOk()->assertJsonPath('id', $folio);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(3, $p->fresh()->stock);
        $this->getJson('/tienda/pedidos')->assertJsonPath('0.total', 180)->assertJsonPath('0.status', 'Pendiente');
    }

    public function test_checkout_rolls_back_all_stock_if_one_item_fails(): void
    {
        $a = $this->product();
        $b = $this->product(['name' => 'Sin existencia', 'stock' => 0]);
        $this->actingAs(User::factory()->create());
        $body = $this->checkout($a, ['items' => [['id' => $a->id, 'quantity' => 2], ['id' => $b->id, 'quantity' => 1]]]);
        $this->postJson('/tienda/pedidos', $body)->assertUnprocessable();
        $this->assertSame(5, $a->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_invalid_discount_inactive_product_and_duplicate_items_are_rejected(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create());
        $this->postJson('/tienda/pedidos', $this->checkout($p, ['discount_code' => 'FAKE']))->assertUnprocessable();
        $this->postJson('/tienda/pedidos', $this->checkout($p, ['items' => [['id' => $p->id, 'quantity' => 1], ['id' => $p->id, 'quantity' => 1]]]))->assertUnprocessable();
        $p->update(['active' => false]);
        $this->getJson('/tienda/productos')->assertExactJson([]);
        $this->postJson('/tienda/pedidos', $this->checkout($p))->assertUnprocessable();
    }

    public function test_orders_are_private_and_cancel_restores_stock_once(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create());
        $this->postJson('/tienda/pedidos', $this->checkout($p))->assertCreated();
        $this->actingAs(User::factory()->create())->getJson('/tienda/pedidos')->assertExactJson([]);
        $order = Order::first();
        $this->putJson('/tienda/pedidos/'.$order->id.'/estado', ['status' => 'Cancelado'])->assertForbidden();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin);
        $this->putJson('/tienda/pedidos/'.$order->id.'/estado', ['status' => 'Cancelado'])->assertOk();
        $this->assertSame(5, $p->fresh()->stock);
        $this->putJson('/tienda/pedidos/'.$order->id.'/estado', ['status' => 'Cancelado'])->assertUnprocessable();
        $this->assertSame(5, $p->fresh()->stock);
    }

    public function test_publications_cannot_be_edited_by_another_user(): void
    {
        $this->actingAs(User::factory()->create());
        $body = ['name' => 'Chaqueta', 'price' => 200, 'description' => 'Chaqueta usada', 'image' => 'https://example.com/image.jpg'];
        $id = $this->postJson('/tienda/publicaciones', $body)->assertOk()->json('id');
        $this->actingAs(User::factory()->create())->putJson('/tienda/publicaciones/'.$id, $body)->assertForbidden();
        $this->deleteJson('/tienda/publicaciones/'.$id)->assertForbidden();
    }

    public function test_auction_rejects_lower_bids_and_closed_auctions(): void
    {
        $a = Auction::create(['name' => 'Subasta test', 'image' => 'https://example.com/image.jpg', 'current_bid_cents' => 10000, 'ends_at' => now()->addHour()]);
        $this->actingAs(User::factory()->create());
        $this->postJson('/tienda/subastas/'.$a->id.'/ofertas', ['amount' => 90])->assertUnprocessable();
        $this->postJson('/tienda/subastas/'.$a->id.'/ofertas', ['amount' => 120])->assertOk()->assertJsonPath('product.currentBid', 120);
        $a->update(['ends_at' => now()->subMinute()]);
        $this->postJson('/tienda/subastas/'.$a->id.'/ofertas', ['amount' => 140])->assertUnprocessable();
    }

    public function test_seeding_does_not_reset_existing_stock(): void
    {
        $this->seed();
        $p = Product::first();
        $p->update(['stock' => 3]);
        $this->seed();
        $this->assertSame(3, $p->fresh()->stock);
        $this->assertDatabaseCount('products', 6);
    }

    public function test_catalog_administration_requires_admin(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create())->patchJson('/tienda/productos/'.$p->id.'/visibilidad')->assertForbidden();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin);
        $this->patchJson('/tienda/productos/'.$p->id.'/visibilidad')->assertOk()->assertJsonPath('active', false);
        $this->putJson('/tienda/productos/'.$p->id, ['name' => 'Producto', 'category' => 'Pants', 'price' => 50, 'stock' => 10, 'image' => 'https://example.com/image.jpg', 'description' => 'Nueva descripción', 'active' => true])->assertOk()->assertJsonPath('price',50);
    }
}
```
