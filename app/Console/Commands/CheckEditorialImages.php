<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Categoria;
use App\Models\Product;
use App\Support\Editorial;
use Illuminate\Console\Command;

class CheckEditorialImages extends Command
{
    protected $signature = 'hf:verificar-imagenes';

    protected $description = 'Comprueba que el catálogo y la tienda tienen imágenes locales válidas';

    public function handle(): int
    {
        $references = [
            ...Product::query()->pluck('image', 'id')->mapWithKeys(fn ($path, $id) => ["Producto #{$id}" => $path]),
            ...Categoria::query()->pluck('imagen_url', 'slug')->mapWithKeys(fn ($path, $slug) => ["Categoría {$slug}" => $path]),
            ...Auction::query()->pluck('image', 'id')->mapWithKeys(fn ($path, $id) => ["Subasta #{$id}" => $path]),
            'Respaldo' => Editorial::FALLBACK,
        ];
        foreach (array_keys(Editorial::SECTIONS) as $key) {
            $references['Sección '.$key] = Editorial::section($key);
        }
        foreach (Editorial::SLIDES as [$key]) {
            $references['Carrusel '.$key] = Editorial::slide($key);
        }
        $failed = 0;
        foreach ($references as $label => $path) {
            $file = public_path(ltrim((string) $path, '/'));
            if (! str_starts_with(ltrim((string) $path, '/'), Editorial::ROOT) || ! file_exists($file)
                || ! str_starts_with(realpath($file), realpath(public_path()).DIRECTORY_SEPARATOR)
                || ! @getimagesize($file)) {
                $this->error($label.' · imagen local inválida: '.$path);
                $failed++;
            }
        }
        $this->line((count($references) - $failed).'/'.count($references).' referencias con imágenes locales válidas.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
