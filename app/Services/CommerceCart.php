<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\CarritoDetalle;
use App\Models\Cliente;
use App\Models\Product;
use App\Models\User;
use App\Support\Editorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommerceCart
{
    public function __construct(private readonly CommercePricing $pricing) {}

    public function client(User $user): Cliente
    {
        $client = DB::transaction(function () use ($user) {
            $account = User::lockForUpdate()->findOrFail($user->id);
            abort_unless($account->role === 'cliente', 403, 'Inicia sesión con una cuenta de cliente.');
            if ($account->cliente_id) {
                return Cliente::findOrFail($account->cliente_id);
            }

            $client = Cliente::firstOrCreate(['correo' => $account->email], ['nombre' => $account->name]);
            abort_if(User::where('cliente_id', $client->id)->exists(), 409, 'Ese perfil ya está vinculado.');
            $account->forceFill(['cliente_id' => $client->id])->save();

            return $client;
        }, 3);
        abort_unless($client->estado === 'activo', 403, 'Tu perfil de cliente está inactivo.');

        return $client;
    }

    public function bag(Cliente $client): Carrito
    {
        return Carrito::firstOrCreate(['cliente_id' => $client->id]);
    }

    public function snapshot(Carrito $cart): array
    {
        $lines = $cart->detalles()->with('producto')->orderBy('id')->get();

        $promotions = $this->pricing->promotions();
        $quantities = $lines->groupBy('producto_id')->map(fn ($group) => $group->sum('cantidad'));

        return ['cliente_id' => $cart->cliente_id, 'cantidad' => $lines->sum('cantidad'),
            'items' => $lines->map(fn ($line) => [
                'id' => $line->id, 'producto_id' => $line->producto_id, 'talla' => $line->talla,
                'cantidad' => $line->cantidad, 'nombre' => $line->producto->name,
                'stock' => $line->producto->active && ! $line->producto->trashed() ? $line->producto->stock : 0,
                'cantidad_producto' => $quantities[$line->producto_id], 'color' => $line->producto->color,
                'discount' => $this->pricing->discount($line->producto, $promotions),
                'precio_unitario' => (int) $line->producto->price_cents,
                'imagen_url' => asset(Editorial::productImage($line->producto->image)),
            ])->all(), ...$this->pricing->quote($lines, $promotions)];
    }

    public function save(Cliente $client, int $productId, ?string $size, int $quantity, ?int $lineId = null): array
    {
        return DB::transaction(function () use ($client, $productId, $size, $quantity, $lineId) {
            $cart = $this->lockedBag($client);
            $this->saveLine($cart, $productId, $size, $quantity, $lineId);

            return $this->snapshot($cart);
        }, 3);
    }

    private function saveLine(Carrito $cart, int $productId, ?string $size, int $quantity, ?int $lineId = null): void
    {
        $product = Product::whereKey($productId)->where('active', true)->lockForUpdate()->first();
        if (! $product) {
            throw ValidationException::withMessages(['producto_id' => 'Esta pieza ya no está disponible.']);
        }
        $line = $lineId ? $cart->detalles()->findOrFail($lineId) : $cart->detalles()
            ->where('producto_id', $productId)->where('talla', $size)->first();
        $size = $line?->talla ?? $size;
        $desired = $quantity + ($lineId ? 0 : ($line?->cantidad ?? 0));
        $other = $cart->detalles()->where('producto_id', $productId)->where('id', '<>', $line?->id ?? 0)->sum('cantidad');
        if (! in_array($size, $product->tallas(), true)) {
            throw ValidationException::withMessages(['talla' => 'Selecciona una talla disponible.']);
        }
        if ($desired > 99 || $desired + $other > $product->stock) {
            throw ValidationException::withMessages(['cantidad' => 'Existencias insuficientes para la cantidad seleccionada.']);
        }
        if (! $line && $cart->detalles()->count() >= 50) {
            throw ValidationException::withMessages(['cantidad' => 'El carrito admite hasta 50 renglones.']);
        }
        $line ??= new CarritoDetalle(['carrito_id' => $cart->id, 'producto_id' => $productId, 'talla' => $size]);
        $line->fill(['cantidad' => $desired, 'precio_unitario_cents' => $product->price_cents])->save();
    }

    public function merge(Cliente $client, string $key, array $items): array
    {
        $hash = hash('sha256', json_encode($items, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($client, $key, $items, $hash) {
            $cart = $this->lockedBag($client);
            $imports = DB::table('importaciones_carrito');
            $previous = (clone $imports)->where('carrito_id', $cart->id)->where('request_key', $key)->first();
            if ($previous) {
                abort_unless(hash_equals($previous->payload_hash, $hash), 409, 'La referencia del carrito ya tiene otros artículos.');

                return [...$this->snapshot($cart), 'rechazados' => json_decode($previous->rechazados, true, flags: JSON_THROW_ON_ERROR)];
            }

            $rejected = [];
            foreach ($items as $index => $line) {
                try {
                    $this->saveLine($cart, $line['producto_id'], $line['talla'], $line['cantidad']);
                } catch (ValidationException $error) {
                    $rejected[] = ['indice' => $index, 'mensaje' => $error->getMessage()];
                }
            }
            $imports->insert(['carrito_id' => $cart->id, 'request_key' => $key, 'payload_hash' => $hash, 'rechazados' => json_encode($rejected, JSON_THROW_ON_ERROR)]);

            return [...$this->snapshot($cart), 'rechazados' => $rejected];
        }, 3);
    }

    public function remove(Cliente $client, ?int $lineId = null): array
    {
        return DB::transaction(function () use ($client, $lineId) {
            $cart = $this->lockedBag($client);
            if ($lineId !== null) {
                $cart->detalles()->findOrFail($lineId)->delete();
            } else {
                $cart->detalles()->delete();
            }

            return $this->snapshot($cart);
        }, 3);
    }

    public function lockedBag(Cliente $client): Carrito
    {
        abort_unless(Cliente::lockForUpdate()->findOrFail($client->id)->estado === 'activo', 403, 'Tu perfil de cliente está inactivo.');

        return $this->bag($client);
    }
}
