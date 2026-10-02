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
