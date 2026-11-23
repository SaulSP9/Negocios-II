# HF Studios. · CRM + SCM + ERP + Tienda

Laravel 12. Los módulos comparten clientes, catálogo e inventario. Esta edición de la Unidad 4 se validó con PHP 8.4.27.

- [Inicio rápido para la entrega U4](docs/TIENDA_U4.md).

- [Instalación y actualización en Laragon](docs/TIENDA_U4.md#laragon).
- [Matriz del PDF y evidencias](docs/MATRIZ_U3.md).
- [Recorrido de presentación](docs/PRESENTACION_U3.md).
- [Validación y alcance de las pruebas](docs/VALIDACION_U3.md).
- [Código completo por ruta](docs/CODIGO_POR_RUTA_U3.md).
- [Guía con botones para copiar](docs/GUIA_COPIAR_PEGAR_U3.html).

## Instalación nueva

Usa PHP 8.2 o superior con Composer. La entrega incluye los recursos compilados. Para recompilarlos, usa Node.js compatible con Vite 7 (20.19+ o 22.12+).

```bat
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan hf:diagnostico
php artisan serve --host=127.0.0.1 --port=8000
```

Abre `http://127.0.0.1:8000/?vista=cuenta`. Consulta los accesos de demostración en la [guía de la Unidad 4](docs/TIENDA_U4.md#accesos-de-demostración). El personal entra a `/gestion`. El registro público crea cuentas de cliente.

## Actualizar una instalación existente

Copia los archivos de actualización sobre la carpeta que contiene `artisan`, conserva `.env` y los registros de tu instalación, y ejecuta:

```bat
composer install
php artisan optimize:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Después recarga con Ctrl+F5. Si editas los recursos de Vite, ejecuta `npm ci` y `npm run build`.

## Demostración y pruebas

```bat
php artisan hf:demo-u3
php artisan test
npm run test:ui
```

El comando demo solicita una contraseña y agrega cuentas de administrador, ventas y logística, clientes, proveedor, productos Push/Pull, recurso, orden procesada y corte empresarial. Una segunda ejecución conserva el escenario.

Las pruebas de interfaz usan eventos DOM/Alpine y HTTP real contra Laravel en SQLite temporal. Consulta el informe para los resultados y sus límites.

La tienda registra pedidos y comprobantes internos. Cobros bancarios, facturas fiscales y envío de correos requieren servicios externos.

## Entrega Unidad 4

La tienda editorial abre en `/` y el menú para el personal en `/gestion`. Consulta la [guía de instalación, demostración y rutas](docs/TIENDA_U4.md), la [matriz de validación](docs/VALIDACION_U4.md) y los [resultados de pruebas](docs/RESULTADOS_PRUEBAS_U4.txt).
