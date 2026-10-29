# Actualización U3 en Laragon

## Base y alcance

Repositorio revisado: `SaulSP9/Negocios-II`, `main`, commit `fb8d73ff0da41cd8e2ba0500f732381cc10485a1` (`Final Unidad 2`). El ERP solicitado en las páginas 10–14 del PDF estaba pendiente.

Se agregan órdenes de clientes con varias partidas, procesamiento transaccional, trazabilidad, dashboard empresarial, recursos, reportes, nivel ERP y roles. CRM, catálogo e inventario conservan sus tablas. Los pedidos a proveedores siguen en SCM.

## Dónde va cada archivo

La raíz es la carpeta que contiene `artisan`, `composer.json`, `app`, `public` y `resources`. En Laragon está normalmente dentro de `C:\laragon\www`.

El ZIP de actualización contiene rutas desde esa raíz. Extrae su contenido sobre tu proyecto y acepta reemplazar los archivos incluidos. Ejemplo: `public/js/erp.js` debe quedar en `C:\laragon\www\TU_CARPETA\public\js\erp.js`.

El ZIP completo sirve para una instalación nueva. La guía HTML y `CODIGO_POR_RUTA_U3.md` contienen el texto completo de cada archivo nuevo o modificado. El manifiesto identifica las rutas y sus hashes.

Antes de actualizar, guarda una copia del proyecto y de la base mediante la herramienta que utilices. Conserva `.env` y tus registros.

## Comandos para tu instalación existente

En Terminal de Laragon, entra en la carpeta que contiene `artisan`. Si se llama `Negocios-II`:

```bat
cd /d C:\laragon\www\Negocios-II
```

Usa el nombre real de tu carpeta y ejecuta:

```bat
composer install
npm ci
php artisan config:clear
php artisan migrate
php artisan optimize:clear
npm run build
php artisan hf:diagnostico
```

Todos los puntos del diagnóstico deben mostrar `[OK]`. Si falta una cuenta administradora:

```bat
php artisan hf:crear-usuario --role=admin
```

Solicita nombre, correo y contraseña de al menos 12 caracteres. La contraseña se guarda mediante hash. Para abrir el proyecto:

```bat
php artisan serve --host=127.0.0.1 --port=8000
```

Mantén la terminal abierta y entra a `http://127.0.0.1:8000/?vista=login`. Después abre `/erp`, `/crm` o `/scm` según tu rol. Recarga con Ctrl+F5. Un dominio de Laragon debe apuntar a `TU_CARPETA/public`.

## Base de datos

`.env.example` propone SQLite. MySQL de Laragon y la conexión elegida en `.env` se configuran por separado. Conserva la conexión que ya utiliza tu instalación.

```bat
php artisan config:clear
php artisan migrate:status
php artisan hf:diagnostico
```

El diagnóstico indica la conexión y la base realmente utilizadas. Esta migración debe aparecer como `Ran`:

```text
2026_10_29_000001_create_erp_tables
```

Si tu instalación usa MySQL, conserva `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`; Laravel aplica las migraciones a esa conexión.

Para una copia nueva con SQLite:

```bat
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

Para consultar un error de servidor en Windows:

```bat
powershell -NoProfile -Command "Get-Content storage/logs/laravel.log -Tail 60"
```

## Escenario de presentación

```bat
php artisan hf:demo-u3
```

Elige una contraseña para las cuentas nuevas:

| Rol | Correo ficticio |
|---|---|
| Administrador | `admin.demo@hfstudios.test` |
| Ventas | `ventas.demo@hfstudios.test` |
| Logística | `logistica.demo@hfstudios.test` |

También se crean clientes DEMO, proveedor DEMO, Hoodie Push, T-Shirt Pull, estación de empaque, orden procesada y corte. Una ejecución posterior conserva registros y contraseñas existentes, y evita repetir la venta demo.

## Flujo y reglas de la orden

1. Selecciona un cliente activo del CRM o créalo en **Nuevo cliente CRM**.
2. Busca productos SCM, agrega cantidades y revisa el total.
3. **Guardar pendiente** conserva la cotización sin reservar ni descontar stock.
4. **Confirmar y procesar** comprueba existencias, descuenta inventario, registra movimientos e historial y evalúa Push/Pull.
5. **Completar entrega** confirma la entrega física y conserva el descuento ya registrado.
6. Cancelar una pendiente conserva stock. Cancelar una venta procesada requiere administrador y devuelve existencias una sola vez.

Los precios vienen del catálogo del servidor y se conservan por partida. Una pendiente conserva el precio acordado y vuelve a validar stock al procesarse. Los cálculos usan centavos.

Una clave repetida con los mismos datos conserva la orden. Reutilizarla con datos diferentes produce un rechazo. Si una partida falla, se revierte la transacción completa.

La venta de un producto Push bajo su mínimo genera o amplía una reposición SCM. Pull requiere un pedido manual. La reposición se recibe en SCM.

Una orden cancelada conserva su historial y se excluye de ventas. Un cliente con órdenes puede inactivarse en CRM. Un producto archivado se recupera desde el filtro **Archivados** y el botón **Restaurar**; restáuralo antes de cancelar una venta que deba devolver sus existencias.

## Roles

| Operación | Administrador | Ventas | Logística | Operador anterior |
|---|---|---|---|---|
| CRM | Sí | Sí | — | Sí |
| Consultar SCM | Sí | Sí | Sí | Sí |
| Productos y proveedores | Sí | — | Sí | — |
| Movimientos y pedidos SCM | Sí | — | Sí | Sí |
| Consultar ERP y reportes | Sí | Sí | Sí | Sí |
| Crear/procesar órdenes | Sí | Sí | — | Sí |
| Completar entregas | Sí | — | Sí | Sí |
| Cancelar pendientes | Sí | Sí | — | Sí |
| Cancelar ventas procesadas | Sí | — | — | — |
| Editar recursos internos | Sí | — | Sí | — |
| Usuarios y configuración global | Sí | — | — | — |

`cliente` accede a su cuenta de tienda. Los permisos se verifican en el servidor y definen el menú.

## Reportes y nivel

Las ventas incluyen ERP procesadas/completadas y tienda enviada/entregada. Pendientes, cancelaciones y reposiciones se distinguen en indicadores separados.

**Guardar corte empresarial** conserva las cifras de ese momento. CSV actual refleja el estado actual; CSV de un corte conserva su información histórica.

Las evidencias del nivel se calculan contra los registros. Integrado exige 3; Automatizado, 4 e inventario automatizado; Optimizado, las 6. El administrador confirma las disponibles.

## Pruebas

```bat
php artisan test
npm run test:ui
```

La segunda orden usa SQLite temporal y HTTP real. Puede elegir PHP con `HF_PHP` y puerto libre con `HF_UI_PORT`. Consulta `VALIDACION_U3.md`.

## Subir a GitHub

Después de probar:

```bat
git switch -c entrega-u3
git add app bootstrap database public resources routes tests docs README.md package.json package-lock.json
git commit -m "Completar ERP U3 e integracion CRM SCM"
git push -u origin entrega-u3
```

Abre una solicitud desde `entrega-u3` hacia `main`, revisa y une los cambios para entregar la versión actualizada.
