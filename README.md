# HFSTUDIOS · CRM + SCM + ERP

Laravel 12 / PHP 8.2+. Tienda, CRM, SCM y ERP con clientes y catálogo compartidos.

- [Instalación y actualización en Laragon](docs/ERP_U3.md).
- [Matriz del PDF y evidencias](docs/MATRIZ_U3.md).
- [Recorrido de presentación](docs/PRESENTACION_U3.md).
- [Validación y alcance de las pruebas](docs/VALIDACION_U3.md).
- [Código completo por ruta](docs/CODIGO_POR_RUTA_U3.md).
- [Guía con botones para copiar](docs/GUIA_COPIAR_PEGAR_U3.html).

## Instalación nueva

Con PHP 8.2+ y Node compatible con Vite 7 (20.19+ o 22.12+):

```bat
composer install
npm ci
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm run build
php artisan hf:crear-usuario --role=admin
php artisan hf:diagnostico
php artisan serve --host=127.0.0.1 --port=8000
```

Abre `http://127.0.0.1:8000/?vista=login`. La cuenta usa la contraseña que escribas. El equipo entra a `/erp`; el registro público crea clientes de tienda.

## Actualizar una instalación existente

Copia los archivos de actualización sobre la carpeta que contiene `artisan`, conserva `.env` y los registros de tu instalación, y ejecuta:

```bat
composer install
npm ci
php artisan config:clear
php artisan migrate
php artisan optimize:clear
npm run build
php artisan hf:diagnostico
```

Después recarga con Ctrl+F5.

## Demostración y pruebas

```bat
php artisan hf:demo-u3
php artisan test
npm run test:ui
```

El comando demo solicita una contraseña y agrega cuentas de administrador, ventas y logística, clientes, proveedor, productos Push/Pull, recurso, orden procesada y corte empresarial. Una segunda ejecución conserva el escenario.

Las pruebas de interfaz usan eventos DOM/Alpine y HTTP real contra Laravel en SQLite temporal. Consulta el informe para los resultados y sus límites.

La tienda registra pedidos y comprobantes internos. Cobros bancarios, facturas fiscales y envío de correos requieren servicios externos.
