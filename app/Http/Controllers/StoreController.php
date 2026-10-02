<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Cliente;
use App\Models\Order;
use App\Models\Product;
use App\Models\Publication;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreController extends Controller
{
    public function products(Request $request)
    {
        return Product::when($request->user()?->role !== 'admin', fn ($q) => $q->where('active', true))->orderBy('id')->get()->map->presentation();
    }

    public function saveProduct(Request $request, InventoryService $inventory, ?Product $product = null)
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'color' => 'nullable|string|max:100', 'category' => 'required|in:Hoodie,T-Shirt,Pants,Accessory',
            'price' => 'required|numeric|min:0.01|max:1000000|decimal:0,2', 'stock' => 'required|integer|min:0|max:1000000',
            'image' => 'required|url:http,https|max:2048', 'description' => 'required|string|min:3|max:3000', 'active' => 'required|boolean',
            'badge' => 'nullable|string|max:40', 'originalPrice' => 'nullable|numeric|gte:price|max:1000000|decimal:0,2']);
        $data['price_cents'] = (int) round($data['price'] * 100);
        $data['original_price_cents'] = isset($data['originalPrice']) ? (int) round($data['originalPrice'] * 100) : null;
        unset($data['price'],$data['originalPrice']);
        $data['color'] = $data['color'] ?? '';

        return DB::transaction(function () use ($data, $product, $request, $inventory) {
            $desired = (int) $data['stock'];
            if ($product) {
                $product = Product::lockForUpdate()->findOrFail($product->id);
                unset($data['stock']);
                $product->fill($data)->save();
                $inventory->setStock($product->id, $desired, $request->user()->id);
            } else {
                $product = Product::create($data);
            }
            $inventory->checkPush($product->id, $request->user()->id);

            return $product->fresh()->presentation();
        }, 3);
    }

    public function toggleProduct(Product $product)
    {
        $product->update(['active' => ! $product->active]);

        return $product->presentation();
    }

    public function orders(Request $request)
    {
        return Order::with(['items', 'user'])->when($request->user()->role !== 'admin', fn ($q) => $q->where('user_id', $request->user()->id))->latest('id')->get()->map->presentation();
    }

    public function checkout(Request $request, InventoryService $inventory)
    {
        $data = $request->validate(['items' => 'required|array|min:1|max:50', 'items.*.id' => 'required|integer|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|between:1,99', 'address' => 'required|string|min:5|max:255',
            'city' => 'required|string|min:2|max:120', 'zip' => 'required|digits:5', 'checkout_key' => 'required|uuid',
            'discount_code' => 'nullable|in:HF10']);

        return DB::transaction(function () use ($data, $request, $inventory) {
            $existing = Order::where('user_id', $request->user()->id)->where('checkout_key', $data['checkout_key'])->first();
            if ($existing) {
                return $existing->presentation();
            }
            $subtotal = 0;
            $items = [];
            // Siempre se calculan precio y existencia con datos del servidor.
            foreach (collect($data['items'])->sortBy('id') as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                if (! $product->active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => "Sin existencias suficientes: {$product->name}."]);
                }
                $subtotal += $product->price_cents * $item['quantity'];
                $items[] = ['product_id' => $product->id, 'name' => $product->name, 'price_cents' => $product->price_cents, 'quantity' => $item['quantity']];
            }
            $client = Cliente::firstOrCreate(['correo' => $request->user()->email], ['nombre' => $request->user()->name]);
            $discount = ($data['discount_code'] ?? '') === 'HF10' ? (int) round($subtotal * 0.10) : 0;
            $order = Order::create(['folio' => 'HF-'.strtoupper((string) Str::ulid()), 'user_id' => $request->user()->id, 'cliente_id' => $client->id,
                'checkout_key' => $data['checkout_key'], 'address' => $data['address'], 'city' => $data['city'], 'zip' => $data['zip'],
                'subtotal_cents' => $subtotal, 'discount_cents' => $discount, 'total_cents' => $subtotal - $discount]);
            $order->items()->createMany($items);
            foreach ($items as $item) {
                $inventory->move($item['product_id'], 'salida', $item['quantity'], 'venta', $request->user()->id,
                    ['order_id' => $order->id, 'referencia' => 'Reserva de tienda '.$order->folio]);
            }

            return response()->json($order->presentation(), 201);
        });
    }

    public function orderStatus(Request $request, Order $order, InventoryService $inventory)
    {
        $data = $request->validate(['status' => 'required|in:Enviado,Entregado,Cancelado']);

        return DB::transaction(function () use ($data, $order, $inventory, $request) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $allowed = ['Pendiente' => ['Enviado', 'Cancelado'], 'Enviado' => ['Entregado'], 'Entregado' => [], 'Cancelado' => []];
            abort_unless(in_array($data['status'], $allowed[$order->status] ?? [], true), 422, 'Transición de pedido no permitida.');
            if ($data['status'] === 'Cancelado') {
                foreach ($order->items()->orderBy('product_id')->get() as $item) {
                    $inventory->move($item->product_id, 'entrada', $item->quantity, 'ajuste', $request->user()->id,
                        ['order_id' => $order->id, 'referencia' => 'Cancelación de tienda '.$order->folio]);
                }
            }
            $order->update($data);

            return $order->presentation();
        });
    }

    public function stats()
    {
        return ['sales' => '$'.number_format(Order::where('status', '!=', 'Cancelado')->sum('total_cents') / 100, 2),
            'orders' => Order::whereIn('status', ['Pendiente', 'Enviado'])->count(), 'users' => Cliente::count()];
    }

    public function publications(Request $request)
    {
        return Publication::where('user_id', $request->user()->id)->latest('id')->get()->map->presentation();
    }

    public function savePublication(Request $request, ?Publication $publication = null)
    {
        if ($publication) {
            abort_unless($publication->user_id === $request->user()->id, 403);
        }
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'price' => 'required|numeric|min:0.01|max:1000000|decimal:0,2',
            'description' => 'required|string|min:3|max:3000', 'image' => 'required|url:http,https|max:2048']);
        $data['price_cents'] = (int) round($data['price'] * 100);
        unset($data['price']);
        $publication ??= new Publication(['user_id' => $request->user()->id]);
        $publication->fill($data)->save();

        return $publication->presentation();
    }

    public function deletePublication(Request $request, Publication $publication)
    {
        abort_unless($publication->user_id === $request->user()->id, 403);
        $publication->delete();

        return response()->noContent();
    }

    public function comments()
    {
        return DB::table('comments')->join('users', 'users.id', '=', 'comments.user_id')->select('comments.id', 'comments.text', 'users.name')->orderByDesc('comments.id')->limit(100)->get();
    }

    public function comment(Request $request)
    {
        $data = $request->validate(['text' => 'required|string|min:3|max:2000']);
        DB::table('comments')->insert([...$data, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return $this->comments();
    }

    public function auction()
    {
        $auction = Auction::latest('id')->first();
        if (! $auction) {
            return ['timerSeconds' => 0, 'product' => ['name' => 'Sin subasta abierta', 'image' => '', 'currentBid' => 0], 'offers' => []];
        }

        return ['id' => $auction->id, 'timerSeconds' => max(0, (int) now()->diffInSeconds($auction->ends_at, false)),
            'product' => ['name' => $auction->name, 'image' => $auction->image, 'currentBid' => $auction->current_bid_cents / 100],
            'offers' => DB::table('bids')->join('users', 'users.id', '=', 'bids.user_id')->where('auction_id', $auction->id)->orderByDesc('bids.id')->limit(50)->get(['users.name as user', 'amount_cents'])->map(fn ($b) => ['user' => $b->user, 'amount' => $b->amount_cents / 100])];
    }

    public function bid(Request $request, Auction $auction)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:1000000|decimal:0,2']);

        return DB::transaction(function () use ($data, $request, $auction) {
            $auction = Auction::lockForUpdate()->findOrFail($auction->id);
            $cents = (int) round($data['amount'] * 100);
            abort_if($auction->ends_at->isPast(), 422, 'La subasta terminó.');
            abort_if($cents <= $auction->current_bid_cents, 422, 'La oferta debe superar la actual.');
            // Comparación atómica para evitar ofertas concurrentes con importe inferior.
            $changed = Auction::whereKey($auction->id)->where('current_bid_cents', $auction->current_bid_cents)->update(['current_bid_cents' => $cents, 'updated_at' => now()]);
            abort_unless($changed, 409, 'La oferta actual cambió. Actualiza e inténtalo de nuevo.');
            DB::table('bids')->insert(['auction_id' => $auction->id, 'user_id' => $request->user()->id, 'amount_cents' => $cents, 'created_at' => now(), 'updated_at' => now()]);

            return $this->auction();
        });
    }

    public function contact(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255', 'message' => 'required|string|min:3|max:3000']);
        $data['email'] = strtolower(trim($data['email']));
        DB::table('contact_messages')->insert([...$data, 'created_at' => now(), 'updated_at' => now()]);

        return ['message' => 'Mensaje guardado para atención del equipo.'];
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        DB::table('subscriptions')->insertOrIgnore(['email' => strtolower(trim($data['email'])), 'created_at' => now(), 'updated_at' => now()]);

        return ['message' => 'Suscripción guardada.'];
    }
}
