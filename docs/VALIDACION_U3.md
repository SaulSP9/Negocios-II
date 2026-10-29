# Validación de la entrega U3

## Entorno y base

Se partió del commit `fb8d73ff0da41cd8e2ba0500f732381cc10485a1` de `SaulSP9/Negocios-II`. Las verificaciones se ejecutaron con PHP 8.3.6, Laravel del `composer.lock`, Node 24 y SQLite.

## Resultados

| Verificación | Resultado |
|---|---|
| Suite PHP completa, CRM + SCM + tienda + ERP | 50 pruebas, 413 aserciones; todas aprobadas |
| Instalación de dependencias Node desde el lock | `npm ci` correcto |
| Compilación de producción | `npm run build` correcto |
| Estilo PHP de archivos cambiados | Laravel Pint correcto |
| Sintaxis de JavaScript | Correcta en ERP, SCM, HTTP, tienda y pruebas |
| Compilación de vistas Blade | Correcta |
| Eventos de interfaz y HTTP real | 10 grupos aprobados, sin excepciones JS no controladas |
| Migración de una copia U2 a U3 | 25 tablas anteriores conservaron todos sus valores |
| Datos de demostración | Comando aditivo e idempotente verificado |
| Integridad del cambio Git | `git diff --check` correcto |

## Recorridos de interfaz

El comando `npm run test:ui` usa JSDOM, Alpine real, una base SQLite temporal, un servidor PHP real y cookies/CSRF reales. No sustituye la API con respuestas inventadas.

- ERP: KPIs, actualizar, alta y selección de cliente, duplicado visible, búsquedas, selección y cantidad de productos, quitar, pasos anterior/siguiente, confirmación, doble clic, detalle y llamada a impresión.
- ERP: guardar pendiente, procesar, completar entrega, cancelar, devolver stock y limpiar filtros.
- ERP: cortes de reportes, CSV persistente, nivel y evidencias, recursos, usuarios y cambio de roles.
- CRM: alta y edición, historial, interacción, evaluación, etapa, Mi actividad, eliminación, contactos, usuarios y dashboard.
- SCM: alta/edición/eliminación de proveedor, rechazo duplicado visible, alta/edición/archivo/restauración de producto, Push/Pull, movimientos, pedidos, recepción, madurez y CSV.
- Tienda: registro/login/logout, carrito, cantidades, cupón, checkout persistente, generación real de comprobante PDF, publicaciones, reseñas, subasta, contacto y suscripción.
- Roles: vistas y bloqueos reales para ventas y logística.
- HTTP: login, sesión, cookies y rechazo 419 de una escritura sin token CSRF.

Las pruebas PHP comprueban además reversión total al faltar stock, precios del servidor, snapshots de partidas, reintentos, transiciones inválidas, restricciones de eliminación, cortes históricos y seguridad de CSV.

## Límites y comprobación local

El navegador Chromium de este entorno no pudo iniciarse por una restricción de sockets. La prueba DOM no comprueba medidas visuales, ventanas nativas, impresión del navegador ni diferencias de renderizado entre navegadores. El botón Imprimir se verifica como llamada; el comprobante PDF de la tienda se genera con la biblioteca real.

MySQL de Laragon y el navegador de tu computadora requieren la comprobación de instalación local. La migración usa el esquema de Laravel y se aplica a la conexión configurada en `.env`; la prueba de base de datos aquí se realizó con SQLite.

Antes de presentar, ejecuta `php artisan hf:diagnostico` y completa el recorrido de `PRESENTACION_U3.md` en tu navegador. Los archivos de actualización conservan la configuración y el contenido de tu base; la migración añade el esquema ERP.

El PDF detalla las funcionalidades de Etapas 1–3 y menciona el carrito de Etapa 4. La página final deja vacíos otros entregables de Etapa 5 y evalúa exposición individual y defensa, para las que se incluye un recorrido con explicaciones.

