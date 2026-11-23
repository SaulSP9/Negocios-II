# HF Studios. · Tienda de la Unidad 4

## Inicio rápido

El proyecto usa Laravel 12. Esta edición se verificó con PHP 8.4.27. Para SQLite habilita `pdo_sqlite`; para MySQL habilita `pdo_mysql`. Composer instala las dependencias a partir de `composer.lock`. El ZIP incluye los recursos compilados y las fotografías locales.

### Laragon

Descomprime la carpeta en `C:\laragon\www`. Abre la terminal de Laragon en CMD y ejecuta:

```bat
cd /d C:\laragon\www\HFSTUDIOS_U4
composer install
if not exist .env copy .env.example .env
if not exist database\database.sqlite type nul > database\database.sqlite
php artisan key:generate
php artisan optimize:clear
php artisan migrate --seed
php artisan serve
```

Abre [http://127.0.0.1:8000](http://127.0.0.1:8000) y entra en **Mi cuenta**. En Linux o macOS, usa `cp .env.example .env` y `touch database/database.sqlite`. Para recompilar los recursos con Node.js, ejecuta `npm ci` y `npm run build`.

### Actualizar una instalación previa

Conserva tu `.env`, la clave `APP_KEY` y tu base de datos. Si trabajas con SQLite, conserva `database/database.sqlite`. Detén el servidor, copia los archivos de la entrega y ejecuta `composer install`, `php artisan optimize:clear` y `php artisan migrate`. Inicia el servidor con `php artisan serve`. Si ya tenías la edición Ficha y Checkout, Laravel indicará que las migraciones están al día. Para crear los datos de demostración en una instalación nueva, usa `php artisan migrate --seed`.

Para usar MySQL, crea una base con `utf8mb4` y configura `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env`. Después ejecuta las mismas migraciones.

## Accesos de demostración

Las cuentas se generan con `APP_ENV=local` y `HF_DEMO=true`. Los pagos de tarjeta, PayPal y transferencia, así como las recargas del cliente, son simulados y se habilitan con `HF_SIMULATE_PAYMENTS=true`. El monedero actualiza el saldo de la base de datos en la misma transacción que el pedido.

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Cliente Demo | `cliente@hfstudio.test` | `HFStudioDemo2026!` |
| Administración | `admin@hfstudio.test` | `HFStudioStaff2026!` |
| Ventas | `ventas@hfstudio.test` | `HFStudioStaff2026!` |
| Logística | `logistica@hfstudio.test` | `HFStudioStaff2026!` |

El botón **Acceso rápido con Cliente Demo** está en **Mi cuenta**. El personal entra a **Gestión empresarial** desde su cuenta o desde `/gestion`. El encabezado es común y cada módulo muestra un apartado a la vez, con las opciones autorizadas para el rol.

| Sección | Funciones |
| --- | --- |
| Clientes · CRM | Métricas, directorio de clientes, etapas, interacciones, evaluaciones, contactos y equipo. |
| Inventario · SCM | Resumen, catálogo e inventario, proveedores, pedidos SCM, reportes y madurez. El historial de movimientos está en cada producto. |
| Órdenes · ERP | Indicadores, órdenes, recursos, reportes, nivel de implementación y roles. |
| Tienda y atención | Promociones por producto o familia, impuestos, monederos con ajustes e historial, pedidos y tickets. |

El correo de recuperación y el aviso de compra usan la configuración de correo de Laravel. Con `MAIL_MAILER=log`, quedan en `storage/logs/laravel.log`. Configura un transporte SMTP y `APP_URL` para enviar los correos a las direcciones registradas.

## Mi cuenta

El acceso presenta los formularios de inicio de sesión y registro en vistas independientes. La recuperación de contraseña se abre desde el inicio de sesión. Si llegas desde el pago, puedes registrarte o iniciar sesión y regresar al checkout.

| Apartado | Contenido |
| --- | --- |
| Mis datos | Perfil de cliente y dirección de entrega. |
| Mi monedero | Saldo, recargas de demostración e historial de movimientos. |
| Mis pedidos | Historial y acceso al detalle o recibo individual. |
| Mis tickets | Seguimiento de consultas y enlace al formulario de Ayuda. |

## Flujo de compra

1. En **Colección**, filtra por familia, busca por nombre u ordena por precio, novedades y ventas. Los favoritos se guardan en el navegador.
2. Abre la ficha del producto para ver la foto y sus encuadres, el color, las tallas y la disponibilidad. Elige talla y cantidad y agrega la prenda. Las miniaturas presentan distintos encuadres de la fotografía disponible.
3. En **Carrito**, ajusta cantidades y revisa los importes. Al pulsar **Continuar compra**, se abre `/checkout`. Si todavía no tienes sesión, inicia sesión o usa el acceso demo para regresar automáticamente al pago.
4. En **Finalizar compra**, confirma la entrega y selecciona tarjeta, PayPal, transferencia o monedero. El cliente demo inicia con MXN 5,000. El resumen muestra los artículos, descuentos, impuestos y total.
5. Pulsa **Confirmar y pagar**. La página del pedido tiene un enlace permanente con acceso privado. Desde ahí puedes imprimir el recibo, entrar a **Mis pedidos** o seguir comprando.
6. En **Mi cuenta → Mi monedero**, consulta el saldo y su historial. En la demostración puedes recargar y ver el abono junto con las compras y reembolsos.
7. En **Ayuda**, crea un ticket por categoría y consulta su folio y actualizaciones. Ventas o Administración pueden responder desde **Gestión empresarial → Tienda y atención → Atención**. Cada actualización se registra en CRM.

El Hoodie Esencial comienza con 8 unidades y umbral PUSH de 5. Comprar 3 unidades en una base recién sembrada permite demostrar la reposición automática en SCM.

## Datos y reglas

| Área | Tablas | Regla principal |
| --- | --- | --- |
| Identidad | `users`, `clientes` | La cuenta tiene un vínculo único con su perfil CRM. |
| Catálogo | `products`, `categorias` | La tienda y SCM comparten producto, stock y proveedor. |
| Carrito | `carrito`, `carrito_detalle`, `importaciones_carrito` | Un carrito por cliente, con una línea por prenda y talla. Las tallas comparten la existencia de la prenda. |
| Compra | `orders`, `order_items`, `pagos` | Una compra pagada tiene un pago único y líneas con el precio y la talla de la compra. |
| Comercialización | `promociones`, `impuestos` | Se elige el mayor descuento vigente por unidad. Los impuestos activos se calculan sobre el importe descontado. |
| Monedero | `monederos`, `movimientos_monedero` | Recargas, compras, reembolsos y ajustes guardan importe, saldo y referencia. |
| Integración | `ordenes_empresariales`, `orden_productos`, `movimientos_inventario`, `pedidos_scm` | El ERP ejecuta la salida de inventario y SCM calcula la reposición. |
| Atención | `tickets_atencion`, `eventos_ticket_atencion`, `interacciones` | La solicitud y la respuesta quedan ligadas al cliente y al responsable. |

Se conservan los nombres físicos previos `products`, `users`, `orders` y `order_items`, que corresponden a productos, usuarios, pedidos de tienda y detalle de pedido en el enunciado. `pedidos_scm` gestiona venta y reposición logística.

`POST /pagos/procesar` bloquea cuenta, carrito y existencias en orden estable. Valida cantidades agrupadas por producto, recalcula importes en centavos, registra el pago y procesa la orden ERP. La solicitud usa un UUID para devolver el mismo folio ante un reintento equivalente. Un cambio de stock, precio final cotizado o saldo impide completar la compra. La cancelación de una compra sin enviar restituye inventario y saldo de monedero cuando corresponde.

## Rutas principales

| Función | Rutas |
| --- | --- |
| Catálogo | `GET /catalogo?categoria=hoodies-outerwear&buscar=hoodie`, `GET /catalogo/{id}` |
| Gestión | `/gestion/clientes`, `/gestion/inventario`, `/gestion/operaciones`, `/gestion/tienda` |
| Carrito | `POST /carrito/importar`, `POST /carrito/agregar`, `GET /carrito/{cliente_id}`, `PUT /carrito/item/{id}`, `DELETE /carrito/item/{id}`, `DELETE /carrito/vaciar/{cliente_id}` |
| Checkout y recibo | `GET /checkout`, `GET /compras/{folio}` |
| Pagos | `POST /pagos/procesar`, `GET /pagos/{id}`, `GET /tienda/pedidos/{id}` |
| Monedero | `GET /mi-monedero`, `POST /mi-monedero/recargas`, `GET /operacion/monederos/{cliente}`, `POST /operacion/monederos/{cliente}/ajustes`, `GET /operacion/monederos/reporte?csv=1` |
| Promociones e impuestos | `GET /promociones`, `POST /promociones`, `PUT /promociones/{id}`, `GET /impuestos`, `POST /impuestos`, `PUT /impuestos/{id}` |
| Atención | `POST /atencion/ticket`, `GET /atencion/tickets`, `GET /atencion/ticket/{id}`, `PUT /atencion/ticket/{id}/respuesta` |
| Cuenta | `POST /auth/registro`, `POST /auth/login`, `POST /auth/logout`, `GET /auth/perfil`, `PUT /auth/perfil`, `POST /auth/recuperar`, `POST /auth/restablecer` |

Para el catálogo o promociones, una visita del navegador abre la vista correspondiente y una petición con `Accept: application/json` devuelve los datos. Las mutaciones usan la sesión Laravel y su token CSRF.

La tienda incluye fotografías reales de referencia de modelos y un respaldo local. Las fotografías de archivo representan la familia de cada prenda de muestra. Los créditos están en [FOTOGRAFIAS_HF.md](FOTOGRAFIAS_HF.md).
