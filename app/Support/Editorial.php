<?php

namespace App\Support;

final class Editorial
{
    public const ROOT = 'images/hf-studios/';
    public const FALLBACK = self::ROOT.'productos/hoodie-esencial.webp';

    public const SECTIONS = [
        'coleccion' => ['01 / COLECCIÓN', 'LA COLECCIÓN.', 'Siluetas relajadas, capas y básicos para todos los días.'],
        'tienda' => ['02 / INVENTARIO COMPLETO', 'LA TIENDA COMPLETA.', 'Todas las piezas, ordenadas por familia. Sin complicaciones.'],
        'campana' => ['03 / CAMPAÑA', 'LA CAMPAÑA.', 'Lanzamientos recientes y promociones vigentes.'],
        'comunidad' => ['04 / COMUNIDAD', 'LA COMUNIDAD.', 'Conversa con otros miembros de HF Studios.'],
        'subasta' => ['05 / SUBASTA', 'PIEZAS ÚNICAS.', 'Participa en la subasta del archivo de HF Studios.'],
        'mis-publicaciones' => ['06 / MIS PUBLICACIONES', 'MI ARCHIVO.', 'Publica y administra las piezas que quieres compartir.'],
        'ayuda' => ['07 / AYUDA', 'ESTAMOS PARA AYUDAR.', 'Envía una consulta y sigue su respuesta desde tu cuenta.'],
        'cuenta' => ['08 / MI CUENTA', 'MI CUENTA.', 'Gestiona tu perfil, tus pedidos y tus consultas.'],
    ];

    public const SLIDES = [
        ['stock', 'Back in Stock', 'Ver colección', '/catalogo'],
        ['archivo', 'DROP 01 / STUDIO ARCHIVE', 'Ver campaña', '/?vista=campana'],
        ['essentials', 'ESSENTIALS 2026', 'Ver básicos', '/catalogo?categoria=camisetas-tees'],
    ];

    public static function section(string $key): string
    {
        return self::ROOT.'secciones/'.$key.'.webp';
    }

    public static function slide(string $key): string
    {
        return self::ROOT.'carrusel/'.$key.'.webp';
    }

    public static function productImage(?string $path): string
    {
        $path = ltrim((string) $path, '/');
        if (preg_match('#\Aimages/hf-studios/[a-z0-9/.-]+\.(?:webp|jpg|png)\z#', $path)
            && ! str_contains($path, '..') && is_file(public_path($path))) {
            return $path;
        }

        return self::FALLBACK;
    }
}
