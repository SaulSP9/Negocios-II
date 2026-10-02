# HFSTUDIOS

Tienda Laravel 12 con CRM y SCM: clientes, seguimiento, etapas, evaluaciones, métricas, roles y Mi actividad. Catálogo y acciones de tienda conectados a base de datos.

Para el PDF U2 consulta [docs/SCM_U2.md](docs/SCM_U2.md): proveedores, inventario, pedidos, Push/Pull, madurez y reportes.

Consulta [docs/INSTALACION.md](docs/INSTALACION.md) para requisitos, instalación completa, configuración, permisos, rutas, matriz del PDF, pruebas y recorrido de aceptación.

Inicio rápido para una copia nueva:

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

El administrador se crea de forma interactiva, sin contraseña distribuida. El registro público crea clientes de tienda. El checkout registra pedidos pendientes de pago; no incluye cobros bancarios ni facturas fiscales. Los mensajes y suscripciones se guardan; no se envían emails.

Se implementan los requisitos técnicos explícitos de Etapas 1 (CRM) y 2 (SCM) de Distribución por etapas_U2.pdf. Las Etapas 3–5 no detallan entregables. Abre `/scm` con una cuenta admin o usuario del equipo.
