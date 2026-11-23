<?php

namespace App\Support;

final class Permissions
{
    public const ROLES = ['admin', 'ventas', 'logistica', 'usuario', 'cliente'];

    public static function forRole(string $role): array
    {
        return match ($role) {
            'admin' => ['crm', 'scm.read', 'scm.operate', 'scm.manage', 'erp.read', 'erp.write', 'erp.complete', 'erp.cancel', 'erp.resources', 'erp.admin', 'users', 'commerce.configure', 'commerce.orders', 'commerce.support'],
            'ventas' => ['crm', 'scm.read', 'erp.read', 'erp.write', 'erp.cancel', 'commerce.orders', 'commerce.support'],
            'logistica' => ['scm.read', 'scm.operate', 'scm.manage', 'erp.read', 'erp.complete', 'erp.resources', 'commerce.orders'],
            // Conserva los permisos operativos de las cuentas de la Etapa 2.
            'usuario' => ['crm', 'scm.read', 'scm.operate', 'erp.read', 'erp.write', 'erp.complete', 'erp.cancel'],
            default => [],
        };
    }

    public static function label(string $role): string
    {
        return ['admin' => 'Administrador', 'ventas' => 'Ventas', 'logistica' => 'Logística', 'usuario' => 'Operador', 'cliente' => 'Cliente'][$role] ?? $role;
    }
}
