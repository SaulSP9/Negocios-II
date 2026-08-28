# Negocios II - Módulo CRM

Repositorio del proyecto del sistema CRM desarrollado sobre Laravel.

## Descripción del Módulo

Este módulo implementa la lógica de negocio, arquitectura de base de datos y endpoints REST para la gestión integral de clientes e interacciones.

### Características Principales

- **Gestión de Clientes:** CRUD completo, filtrado por estado y etapas CRM (Prospecto, Activo, Frecuente, Inactivo).
- **Registro de Interacciones:** Historial centralizado por cliente (llamadas, correos, reuniones).
- **Métricas e Indicadores:** Resumen de clientes activos vs. inactivos y lista de clientes en riesgo.
- **API REST:** Endpoints listos para integración con componentes de front-end.

---

## Tecnologías Utilizadas

- PHP 8.x
- Laravel 10/11
- MySQL

---

## Configuración del Proyecto

```bash
# Instalar dependencias PHP
composer install

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Iniciar el servidor
php artisan serve