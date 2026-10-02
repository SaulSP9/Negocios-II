# HFSTUDIOS: instalación y guía de funcionamiento

Versión preparada desde `henryknot6/HFSTUDIOS`, rama `main`, commit `f380aefba6cccca39a594290b92144d5e7852421`. Revisión: 1 de octubre de 2026 (UTC).

## 1. Qué encontré y qué está implementado

El proyecto original contenía la estructura inicial de Laravel y una página de tienda en `resources/views/welcome.blade.php`. El controlador base estaba vacío, solo existía el modelo User y la ruta `/` devolvía la vista. No había tablas ni endpoints para clientes, interacciones o métricas. El login aceptaba cualquier correo y la pestaña elegida otorgaba acceso admin; productos, pedidos, clientes, comentarios y ofertas se mantenían como arreglos JavaScript. Solo el carrito y una identidad simulada se guardaban en localStorage. Varias acciones mostraban mensajes de éxito sin guardar nada.

Esta versión conserva la tienda, implementa el CRM y conecta registro, login, catálogo, stock, pedidos, publicaciones propias, comentarios, ofertas, contacto y suscripciones a SQLite mediante Laravel. La página CRM está separada en `/crm` y funciona con JavaScript y CSS propios. Las rutas JSON tienen validación y permisos de servidor. La tienda usa Alpine y los recursos compilados por Vite.

**Alcance actualizado U2:** se conservan los requisitos CRM y se añaden los requisitos detallados de SCM del nuevo PDF. Lee `docs/SCM_U2.md` para actualizar, operar inventario, proveedores, pedidos, estrategias Push/Pull, madurez y reportes. Las Etapas 3–5 siguen sin entregables técnicos especificados. Las exposiciones y defensa son actividades del equipo.

**Pagos:** el checkout guarda un pedido `Pendiente`, calcula precios y reserva stock. No cobra tarjetas, no hace transferencias SPEI, no tiene saldo wallet y no emite CFDI. El comprobante descargable es de pedido y dice que no acredita un cobro. Para cobrar hace falta elegir y configurar un proveedor con sus credenciales y webhooks. El formulario de contacto almacena mensajes en la bandeja CRM; no envía correos. Las suscripciones almacenan destinatarios; no ejecutan campañas de email.

## 2. Requisitos de tu computadora

- PHP 8.2 o superior. Se verificó con PHP 8.3.6.
- Extensiones PHP: PDO, pdo_sqlite, sqlite3, mbstring, openssl, tokenizer, ctype, fileinfo, DOM/XML, curl y zip. Composer señala las que falten.
- Composer 2.
- Node.js 22.12 o superior compatible con Vite 7. Se verificó con Node 24.
- Un navegador moderno. Internet para descargar dependencias e imágenes externas.

Comprueba en la terminal:

```powershell
php -v
php -m
composer --version
node --version
npm --version
```

Si Windows dice que no reconoce un comando, agrega la carpeta del ejecutable a PATH y vuelve a abrir la terminal. Si usas XAMPP, `php --ini` debe señalar el php.ini de la instalación de PHP que quieres usar. Activa las extensiones faltantes quitando `;` a sus líneas en ese archivo y reinicia la terminal.

## 3. Instalar la entrega completa (camino recomendado)

1. Descomprime `HFSTUDIOS-U2-CRM-SCM.zip` en una carpeta nueva. La carpeta `HFSTUDIOS-U2-CRM-SCM` debe contener directamente `artisan`, `composer.json`, `package.json`, `app`, `public`, `routes` y `database`.
2. Abre esa carpeta en VS Code. Abre Terminal → Nueva terminal. El prompt debe estar en la carpeta donde está `artisan`.
3. Ejecuta el siguiente bloque una sola vez, línea por línea. Si una línea falla, corrige el error antes de seguir.

```powershell
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

El comando `hf:crear-usuario` pregunta nombre, correo y contraseña. Usa una contraseña de al menos 12 caracteres. La contraseña no aparece mientras la escribes. Esta será TU cuenta de administrador; no se distribuyen credenciales predeterminadas. Las seis prendas originales se insertan con 30 existencias cada una y se abre una subasta de muestra por siete días. Ese stock es un dato inicial para la demostración; ajusta el inventario antes de atender pedidos reales. Volver a ejecutar el seeder no restablece tus existencias ni tus precios.

Abre `http://127.0.0.1:8000/login`. Inicia sesión con la cuenta recién creada: se abrirá `http://127.0.0.1:8000/crm`. Desde “Volver a la tienda” puedes navegar a Catálogo y al panel Admin. En ese panel gestionas productos, pedidos y comprobantes. Un cliente que se registra públicamente recibe el rol `cliente` y entra a su perfil.

Conserva la terminal del servidor abierta. Para detenerlo pulsa Ctrl+C. En las siguientes sesiones solo necesitas volver a ejecutar `php artisan serve --host=127.0.0.1 --port=8000`. Al modificar vistas, clases Tailwind o `resources/js/app.js`, vuelve a ejecutar `npm run build`. Los archivos de `public/js` y `public/css` se sirven directamente; recarga la página tras editarlos. No necesitas un servidor Vite separado para ejecutar esta entrega compilada.

## 4. Si prefieres copiar y pegar en tu proyecto actual

Haz primero una copia de tu carpeta y de tu base de datos. La guía de código identifica exactamente qué archivos **crear** y qué archivos **reemplazar completos**. Todas las rutas son relativas a la raíz HFSTUDIOS donde está `artisan`. Por ejemplo, `app/Models/Cliente.php` significa `HFSTUDIOS/app/Models/Cliente.php`. Crea primero la carpeta si no existe. Copia únicamente el contenido del bloque correspondiente: no copies el encabezado de ruta ni las marcas del bloque.

Las migraciones originales `0001_01_01_*` se conservan. Las dos migraciones `2026_10_01_*` y la migración SCM `2026_10_02_*` se agregan como archivos nuevos. No pegues SQL a mano ni cambies una migración que ya ejecutaste. No crees otro proyecto Laravel ni instales Breeze/Sanctum: la autenticación de esta entrega usa sesiones del mismo sitio, con la protección CSRF de las rutas web. No hace falta `routes/api.php`: los endpoints REST viven en `routes/web.php` para compartir la sesión de la tienda y conservar las rutas exactas del PDF.

Una vez copiados TODOS los archivos, ejecuta `composer install`, `npm ci`, `php artisan optimize:clear`, `php artisan migrate --seed`, `npm run build` y `php artisan test` desde la raíz. Crea el administrador con el comando de la sección anterior y arranca el servidor. Si `.env` ya existe, conserva su `APP_KEY`. Si falta, copia `.env.example` y genera la clave antes de migrar. No ejecutes `migrate:fresh` sobre una base de datos que quieras conservar.

`package-lock.json` se reemplaza junto a `package.json`: así `npm ci` instala las mismas versiones. Si quieres generar el lock en vez de copiar su bloque largo, después de reemplazar `package.json` ejecuta `npm install` en lugar de `npm ci` la primera vez. Esto puede resolver versiones diferentes de las dependencias declaradas con rangos. El ZIP ya contiene el lock verificado.

## 5. Configuración de base de datos y entorno

La instalación recomendada usa SQLite para evitar requerir otro servidor. La configuración se encuentra en **HFSTUDIOS/.env**, no en los modelos ni en los controladores. Deja `DB_CONNECTION=sqlite` y, en instalación nueva, no agregues `DB_DATABASE`: Laravel usa `database/database.sqlite`.

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

Estas líneas son el subconjunto relevante del archivo completo; no borres `APP_KEY`. Si tu `.env` existente apunta a otro archivo SQLite, conserva esa ruta y haz copia de ese archivo. Si ya usas MySQL, conserva su configuración y ejecuta las migraciones allí con la extensión pdo_mysql habilitada. Las pruebas de esta entrega se ejecutaron con SQLite; MySQL requiere comprobar migraciones y permisos en tu instalación.

Para publicar posteriormente: utiliza un host para PHP/Laravel, apunta la raíz web a `public`, conserva una APP_KEY única, configura la base de datos del servidor, usa HTTPS, `APP_ENV=production`, `APP_DEBUG=false` y `SESSION_SECURE_COOKIE=true`. `storage` y `bootstrap/cache` necesitan permisos de escritura. No subas `.env` a GitHub. Publicar no está incluido en esta entrega local.

## 6. Estructura y responsabilidades

| Ruta | Responsabilidad |
|---|---|
| `database/migrations` | Tablas, claves foráneas, índices y rol del usuario |
| `database/seeders` | Datos iniciales del catálogo y la subasta |
| `app/Models` | Clientes, interacciones, evaluaciones y entidades de tienda |
| `app/Http/Requests` | Validaciones de clientes e interacciones |
| `app/Http/Middleware/RequireRole.php` | Comprobación del rol del usuario autenticado |
| `app/Http/Controllers/AuthController.php` | Registro, login, sesión y logout |
| `app/Http/Controllers/CrmController.php` | Clientes, seguimiento, evaluaciones, actividad, equipo y bandeja |
| `app/Http/Controllers/StoreController.php` | Catálogo, pedidos, stock, publicaciones, comunidad y contacto |
| `app/Services/CrmMetrics.php` | Cálculo de métricas a partir de registros reales |
| `app/Console/Commands/CreateStaff.php` | Alta inicial de cuentas de equipo desde terminal |
| `routes/web.php` | Rutas REST y grupos de permisos |
| `bootstrap/app.php` | Registro del middleware role |
| `resources/views/welcome.blade.php` | Diseño e interfaz de la tienda original integrada |
| `resources/views/crm/index.blade.php` | Pantallas del CRM y restricciones de vistas por rol |
| `public/js/http.js` | Cliente fetch común con cookie de sesión, token CSRF y mensajes de error |
| `public/js/storefront.js` | Estado Alpine y conexión de la tienda con endpoints |
| `public/js/crm.js` | Formularios, búsqueda, filtros, historial y dashboard CRM |
| `public/css/crm.css` | Estilo y adaptación a pantallas pequeñas del CRM |
| `resources/js/app.js` | Inicializa Alpine y las bibliotecas de comprobantes PDF |
| `lang/es/validation.php` | Mensajes de validación en español |
| `tests/Feature` | Pruebas de integración, seguridad, persistencia y lógica de negocio |

## 7. Permisos efectivos

| Operación | Admin | Usuario del equipo | Cliente de tienda |
|---|---|---|---|
| Ver CRM, dashboard, historial y contactos | Sí | Sí | No |
| Alta/edición de cliente, etapa, interacción y evaluación | Sí | Sí | No |
| Eliminar cliente y su historial | Sí | No | No |
| Crear cuentas de equipo y ver usuarios | Sí | No | No |
| Administrar catálogo y estado de pedidos | Sí | No | No |
| Ver pedidos | Todos | Propios | Propios |
| Publicar, comentar y ofertar | Sí | Sí | Sí |
| Editar/eliminar publicaciones | Propias | Propias | Propias |
| Elegir su rol desde el registro público | No | No | No |

El invitado consulta catálogo/comentarios/subasta y puede enviar contacto o suscribirse. Necesita login para comprar, publicar, comentar u ofertar. Ocultar botones no concede seguridad: los grupos middleware rechazan también las peticiones manuales. Los campos de usuario responsable de interacciones y evaluaciones se obtienen de la sesión, aunque el navegador intente enviar otro ID.

Para crear un operador: entra como admin → Equipo y permisos → Crear cuenta del equipo → rol “Usuario”. También puedes ejecutar `php artisan hf:crear-usuario --role=usuario` en la terminal. Un operador no obtiene los permisos de la cuenta admin.

## 8. Correspondencia con el PDF

| Requisito explícito de Etapa 1 | Implementación | Cómo comprobarlo |
|---|---|---|
| API REST, lógica de negocio, persistencia, validación y seguridad | Rutas JSON, controladores, modelos, migraciones, Requests, sesión y roles | Ejecutar pruebas y usar formularios |
| Cliente: id, nombre, correo, teléfono, empresa, fecha_registro, estado | Tabla/modelo Cliente | Crear cliente y abrir su historial/listado |
| POST/GET/GET por ID/PUT/DELETE de clientes | `/clientes` y `/clientes/{id}` | Alta, lista, consulta, edición y eliminación como admin |
| Formulario de alta/edición | Diálogo “Nuevo cliente” / “Editar” | Guardar y recargar |
| Búsqueda y filtro por estado | Formulario sobre listado paginado | Buscar nombre/correo/empresa; filtrar activo/inactivo |
| Interacción: id, cliente_id, tipo, descripción, fecha, usuario_id | Tabla/modelo Interaccion | Guardar interacción y revisar responsable |
| POST interacciones y GET historial por cliente | `/interacciones` y `/clientes/{id}/interacciones` | Historial → Registrar interacción |
| Historial y línea de tiempo | Pantalla detalle del cliente | Orden descendente por fecha y responsable |
| Etapa CRM: Prospecto, Activo, Frecuente, Inactivo | Campo etapa_crm y selector | Cambiar etapa; recargar y filtrar |
| PUT etapa y etiquetas por colores | `/clientes/{id}/etapa`, CSS de etapas | Historial y directorio |
| Total, activos/inactivos, interacciones por cliente | `/metricas`, servicio CrmMetrics | Dashboard y contadores |
| Clientes sin interacción reciente | Clientes activos sin contacto o con última interacción anterior a 30 días | Registrar contacto reciente y actualizar dashboard |
| Gráfica simple y lista de riesgo | Barras por etapa + lista con acceso a historial | Dashboard |
| Login y roles admin/usuario | Cuentas del equipo y middleware de servidor | Probar cuentas con distintos permisos |
| Restricción de vistas por rol y Mi actividad | Vista CRM / endpoint filtrado por usuario_id | Registrar seguimiento con dos cuentas |
| Evaluaciones / métricas en BD mínima | Tabla Evaluacion, puntuación 1–5 y observaciones | Historial → Evaluar relación |
| Mini exposición individual | Recorrido de demostración al final | Cada integrante ejecuta y explica su parte |

**Estado y etapa son campos distintos:** `estado` controla activo/inactivo operativo; `etapa_crm` clasifica la relación comercial. No se sincronizan automáticamente para evitar sobrescribir decisiones del operador. La alerta de riesgo considera únicamente clientes con `estado=activo`.

## 9. Rutas y contratos

Todas las respuestas de estos endpoints se solicitan con `Accept: application/json`. Los POST/PUT/PATCH/DELETE requieren cookie de sesión y `X-CSRF-TOKEN`. `/sesion` devuelve `{user, csrf_token}`. El helper HF de la entrega ya maneja esos encabezados y actualiza el token al iniciar/cerrar sesión.

| Método | Ruta | Uso |
|---|---|---|
| POST | `/registro` | Cuenta pública: name, email, password; rol cliente fijo |
| POST | `/login` | email, password |
| POST | `/logout` | Cierra sesión e invalida cookie/token |
| GET | `/clientes?q=&estado=&etapa=&page=1` | Lista paginada de 15, búsqueda y filtros |
| POST | `/clientes` | Crear cliente |
| GET | `/clientes/{id}` | Cliente y evaluaciones |
| PUT | `/clientes/{id}` | Actualizar todos los campos del formulario |
| DELETE | `/clientes/{id}` | Eliminación, solo admin |
| PUT | `/clientes/{id}/etapa` | etapa_crm |
| POST | `/interacciones` | cliente_id, tipo, descripcion, fecha |
| GET | `/clientes/{id}/interacciones` | Línea de tiempo con responsables |
| POST | `/clientes/{id}/evaluaciones` | puntuacion 1–5, observaciones |
| GET | `/metricas` | Contadores, etapas, riesgo, interacciones por cliente |
| GET | `/mi-actividad?page=1` | Solo interacciones del usuario autenticado |
| GET/POST | `/usuarios` | Listar/crear equipo; solo admin |
| GET | `/contactos?page=1` | Bandeja de mensajes |
| POST | `/contactos/{id}/registrar` | Crear/encontrar cliente y convertir mensaje en interacción una sola vez |
| GET/POST | `/tienda/pedidos` | Consultar / registrar pedido |
| PUT | `/tienda/pedidos/{id}/estado` | Pendiente→Enviado/Cancelado; Enviado→Entregado, solo admin |
| GET/POST/PUT | `/tienda/productos` y `/tienda/productos/{id}` | Consultar catálogo / alta y edición admin |
| PATCH | `/tienda/productos/{id}/visibilidad` | Ocultar/mostrar, admin |
| GET/POST/PUT/DELETE | `/tienda/publicaciones` y `/tienda/publicaciones/{id}` | Publicaciones del usuario |
| GET/POST | `/tienda/comentarios` | Listar / comentar |
| GET | `/tienda/subasta` | Oferta actual, fecha límite e historial |
| POST | `/tienda/subastas/{id}/ofertas` | Oferta superior a la actual, antes del cierre |
| POST | `/tienda/contacto` | name, email, message |
| POST | `/tienda/suscripciones` | email |

Payload de cliente:

```json
{
  "nombre": "Henry Mendoza",
  "correo": "henry@example.com",
  "telefono": "+52 449 123 4567",
  "empresa": "HFSTUDIOS",
  "estado": "activo",
  "etapa_crm": "Prospecto"
}
```

Payload de interacción; `usuario_id` no se envía:

```json
{
  "cliente_id": 1,
  "tipo": "llamada",
  "descripcion": "Se consultaron preferencias de la nueva colección.",
  "fecha": "2026-09-30T20:00:00Z"
}
```

Los tipos permitidos son `llamada`, `correo`, `reunión`, con tilde. La fecha de interacción no puede ser futura. El correo del cliente es único y se normaliza a minúsculas. El nombre exige 2–120 caracteres y la descripción 3–3000. Teléfono y empresa son opcionales.

## 10. Recorrido de aceptación y demostración

1. Crea admin y usuario de equipo. Abre una ventana privada para entrar como el operador.
2. Admin → Clientes → Nuevo cliente. Guarda nombre/correo/teléfono/empresa. Recarga. Debe continuar en la tabla.
3. Intenta crear otro con el mismo correo. Debe mostrar error y no duplicarse.
4. Edita empresa. Filtra por texto, estado y etapa. Cambia a Frecuente en el detalle y revisa la etiqueta del listado.
5. Historial → registra llamada, correo y reunión con fechas válidas. Deben ordenarse del contacto más reciente al más antiguo y mostrar tu nombre como responsable.
6. Evalúa relación con puntuación 1–5 y observaciones. Recarga: debe permanecer.
7. Registra una interacción con el operador. En “Mi actividad”, cada cuenta debe ver solo las interacciones que registró.
8. Crea un cliente activo sin contactos y otro con último contacto de hace 31 días. Deben aparecer en riesgo. Agrega una interacción reciente a uno y actualiza dashboard: debe salir de riesgo.
9. Como operador, verifica que no exista “Equipo y permisos” ni botón eliminar cliente. Una petición directa a esas operaciones devuelve 403.
10. Desde tienda, registra un cliente público. No puede abrir `/crm` y no puede elegir rol admin. Añade una prenda al carrito, registra dirección y pedido. Recarga el perfil: el pedido continúa allí.
11. Admin → Productos: revisa que stock disminuya por ese pedido. Admin → Pedidos: cancélalo; el stock se restituye una vez. Un pedido entregado no puede volver a Pendiente ni cancelar desde este flujo.
12. Admin → Comprobantes: descarga el PDF. El perfil del cliente también permite descargarlo. Debe mostrar artículos, precio, descuento si usaste HF10, total y estado.
13. Envía un mensaje desde Contacto. En CRM → Mensajes de contacto, regístralo como interacción. Debe crear/encontrar un cliente por correo y marcar el mensaje atendido. No se puede convertir dos veces.
14. Crea, edita y elimina una publicación propia. Otra cuenta no puede modificarla. Agrega un comentario y recarga. Oferta en la subasta: debe superar el importe actual y quedar en el historial.
15. Prueba el menú móvil, formularios y tablas en tu navegador a 390 px de ancho. La validación visual completa queda para tu instalación: el entorno de preparación no permitió iniciar Chrome para inspeccionar píxeles.

Para la mini exposición de cada alumno, toma una parte (datos/modelos, API/validación, autenticación/roles, interfaz/historial, métricas/pruebas), ejecuta un ejemplo y explica: entrada → validación → operación de BD → respuesta → actualización de la pantalla. La defensa grupal puede seguir los pasos 2, 5, 7, 8, 9 y 10. Guardar capturas y repartir qué explica cada integrante es una actividad del equipo, no algo que el software haga por ellos.

## 11. Verificación realizada y límites

- `php artisan test`: **30 pruebas, 232 assertions, todas aprobadas**, incluidos 12 casos SCM nuevos.
- `npm run build`: recursos compilados correctamente con Vite 7.3.1.
- Se revisó el estilo PHP de los archivos de implementación SCM e integración.
- `git diff --check`: sin errores de espacios en el cambio final.
- HTTP real con servidor PHP y cookies: sesión, 401 para invitado, 419 sin token CSRF, login, métricas y logout comprobados.
- DOM con Alpine: expresiones, catálogo, login, carrito, registro de pedido y logout comprobados con endpoints simulados para esa prueba de interfaz. La lógica de los endpoints se cubre con pruebas Laravel separadas.
- DOM de CRM: dashboard, formulario de cliente, historial, interacción y evaluación comprobados.
- Comprobante PDF: generado, abierto y renderizado para comprobar contenido y disposición.
- **No se completó revisión visual de páginas en Chrome**: el navegador no pudo iniciarse por restricciones del entorno. No se afirma haber probado cobros reales, publicación, servidor MySQL, concurrencia bajo carga ni todos los tamaños de pantalla.

La tecnología exacta del backend bloqueado en composer.lock es Laravel **12.50.0**. El frontend bloquea Alpine **3.14.9**, jsPDF **3.0.4** y jsPDF-AutoTable **5.0.2**, además de las versiones resueltas en package-lock.json. Los recursos JavaScript principales ya no dependen del CDN de Tailwind/Alpine/jsPDF; se compilan localmente. Las fotografías, fuentes web y Font Awesome de la tienda conservan sus URLs externas.

## 12. Errores frecuentes y solución concreta

| Error | Solución |
|---|---|
| `could not find driver` | Activa pdo_sqlite y sqlite3 en el php.ini que muestra `php --ini` |
| `No application encryption key` | En instalación nueva ejecuta `php artisan key:generate`; conserva la clave existente si hay datos |
| `no such table` / tablas faltantes | Ejecuta `php artisan migrate --seed` y revisa DB_CONNECTION en .env |
| `Vite manifest not found` | Ejecuta `npm ci` y `npm run build`; comprueba public/build/manifest.json |
| Página sin cambios tras editar | Ejecuta `php artisan optimize:clear`, recompila con `npm run build` y recarga sin caché |
| 419 | Recarga y vuelve a iniciar sesión; usa siempre el mismo host/puerto, no mezcles localhost y 127.0.0.1 |
| 401 | Inicia sesión; confirma que el navegador admite cookies |
| 403 | La cuenta no tiene el rol necesario; créala desde admin/terminal, no desde registro público |
| 422 | Corrige el campo que indica el mensaje; no omitas validación ni cambies estados permitidos |
| 429 | Espera un minuto antes de reintentar; se limita abuso de login, registro y formularios públicos |
| Usuario admin ya existe | Usa otra dirección o entra con la cuenta ya creada; el comando no sobreescribe contraseñas |
| No puedo escribir contraseña en terminal | Se oculta de forma intencional; escríbela y presiona Enter |
| Pedido no se puede registrar | Revisa sesión, dirección, ciudad, CP de 5 dígitos y existencias; el precio proviene de BD |
| Imagen o iconos no cargan | Las URLs externas requieren internet; cambia URL del producto en Admin o sirve imágenes propias |
| Base SQLite bloqueada en despliegue con muchos usuarios | Esta entrega se verificó como proyecto académico local; para alta carga usa un servidor de BD y realiza pruebas de concurrencia |

Fuentes técnicas de diseño: documentación oficial de Laravel 12 para [autenticación](https://laravel.com/framework/docs/12.x/authentication), [middleware](https://laravel.com/framework/docs/12.x/middleware) y [reglas de validación](https://api.laravel.com/docs/12.x/Illuminate/Validation/Rule.html). El alcance funcional se tomó del PDF adjunto.
