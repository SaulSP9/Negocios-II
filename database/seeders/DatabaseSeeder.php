<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = json_decode(file_get_contents(database_path('seeders/products.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($products as $p) {
            Product::firstOrCreate(['id' => $p['id']], ['name' => $p['name'], 'color' => $p['color'], 'category' => $p['category'],
                'price_cents' => (int) round($p['price'] * 100), 'original_price_cents' => (int) round($p['originalPrice'] * 100),
                'stock' => 30, 'image' => $p['image'], 'badge' => $p['badge'], 'description' => $p['description'], 'active' => true]);
        }
        Auction::firstOrCreate(['id' => 1], ['name' => 'HF Signature Hoodie (Sample 1/1)', 'image' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=800', 'current_bid_cents' => 350000, 'ends_at' => now()->addDays(7)]);
    }
}
