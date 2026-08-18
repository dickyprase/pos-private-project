<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::updateOrCreate(['username' => 'owner'], [
            'name' => 'Owner Kopi Senja',
            'email' => 'owner@example.test',
            'password' => Hash::make(env('COFFEE_POS_OWNER_PASSWORD', 'ChangeMe123!')),
            'pin' => Hash::make('123456'),
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);
        User::updateOrCreate(['username' => 'manager'], [
            'name' => 'Manager', 'email' => 'manager@example.test',
            'password' => Hash::make('manager123'), 'pin' => Hash::make('654321'),
            'role' => UserRole::MANAGER, 'is_active' => true,
        ]);
        User::updateOrCreate(['username' => 'cashier'], [
            'name' => 'Kasir Demo', 'email' => 'cashier@example.test',
            'password' => Hash::make('cashier123'), 'role' => UserRole::CASHIER,
            'is_active' => true,
        ]);

        StoreSetting::updateOrCreate(['id' => 1], [
            'store_name' => 'Kopi Senja',
            'address' => 'Coffee shop demo · Indonesia',
            'phone' => '08xx-xxxx-xxxx',
            'currency' => 'IDR', 'timezone' => 'Asia/Jakarta',
            'tax_enabled' => true, 'tax_rate' => 10, 'service_charge_rate' => 0,
            'receipt_footer' => 'Terima kasih. Sampai jumpa lagi!',
            'allow_negative_stock' => false, 'transaction_prefix' => 'KS',
        ]);

        $coffee = Category::updateOrCreate(['slug' => 'coffee'], ['name' => 'Coffee', 'sort_order' => 1, 'is_active' => true]);
        $nonCoffee = Category::updateOrCreate(['slug' => 'non-coffee'], ['name' => 'Non Coffee', 'sort_order' => 2, 'is_active' => true]);
        $food = Category::updateOrCreate(['slug' => 'food'], ['name' => 'Food', 'sort_order' => 3, 'is_active' => true]);

        $products = [
            [$coffee, 'Espresso', 'espresso', 'COF-ESP', 18000, true],
            [$coffee, 'Americano', 'americano', 'COF-AME', 22000, true],
            [$coffee, 'Cafe Latte', 'cafe-latte', 'COF-LAT', 30000, true],
            [$coffee, 'Cappuccino', 'cappuccino', 'COF-CAP', 30000, false],
            [$coffee, 'Caramel Macchiato', 'caramel-macchiato', 'COF-CAM', 35000, true],
            [$nonCoffee, 'Matcha Latte', 'matcha-latte', 'NC-MAT', 32000, true],
            [$nonCoffee, 'Chocolate', 'chocolate', 'NC-CHO', 30000, false],
            [$nonCoffee, 'Lychee Tea', 'lychee-tea', 'NC-LYC', 26000, false],
            [$food, 'Croissant Butter', 'croissant-butter', 'FD-CRO', 25000, true],
            [$food, 'Toast Kaya', 'toast-kaya', 'FD-TOS', 28000, false],
        ];
        foreach ($products as [$category, $name, $slug, $sku, $price, $favorite]) {
            Product::updateOrCreate(['sku' => $sku], [
                'category_id' => $category->id, 'name' => $name, 'slug' => $slug,
                'base_price' => $price, 'cost_estimate' => (int) ($price * .35),
                'is_active' => true, 'is_available' => true,
                'is_favorite' => $favorite, 'sort_order' => 1,
            ]);
        }

        $size = ModifierGroup::updateOrCreate(['name' => 'Size'], [
            'selection_type' => 'SINGLE', 'min_selection' => 1,
            'max_selection' => 1, 'is_required' => true,
        ]);
        foreach ([['Regular', 0], ['Large', 5000]] as [$name, $price]) {
            $size->options()->updateOrCreate(['name' => $name], ['price_adjustment' => $price, 'is_active' => true]);
        }
        $milk = ModifierGroup::updateOrCreate(['name' => 'Milk'], [
            'selection_type' => 'SINGLE', 'min_selection' => 0,
            'max_selection' => 1, 'is_required' => false,
        ]);
        foreach ([['Fresh Milk', 0], ['Oat Milk', 7000], ['Soy Milk', 5000]] as [$name, $price]) {
            $milk->options()->updateOrCreate(['name' => $name], ['price_adjustment' => $price, 'is_active' => true]);
        }
        Product::where('category_id', $coffee->id)->each(fn (Product $product) => $product->modifierGroups()->syncWithoutDetaching([$size->id, $milk->id]));

        $gram = Unit::updateOrCreate(['symbol' => 'g'], ['name' => 'Gram', 'precision' => 2]);
        $ml = Unit::updateOrCreate(['symbol' => 'ml'], ['name' => 'Milliliter', 'precision' => 2]);
        $pcs = Unit::updateOrCreate(['symbol' => 'pcs'], ['name' => 'Pieces', 'precision' => 0]);
        $bean = InventoryItem::updateOrCreate(['sku' => 'ING-BEAN'], ['unit_id' => $gram->id, 'name' => 'Coffee Bean', 'current_stock' => 5000, 'minimum_stock' => 500, 'average_cost' => 300, 'is_active' => true]);
        $milkItem = InventoryItem::updateOrCreate(['sku' => 'ING-MILK'], ['unit_id' => $ml->id, 'name' => 'Fresh Milk', 'current_stock' => 10000, 'minimum_stock' => 2000, 'average_cost' => 25, 'is_active' => true]);
        $cup = InventoryItem::updateOrCreate(['sku' => 'ING-CUP'], ['unit_id' => $pcs->id, 'name' => 'Cup 12 oz', 'current_stock' => 300, 'minimum_stock' => 50, 'average_cost' => 1200, 'is_active' => true]);
        Product::where('category_id', $coffee->id)->each(function (Product $product) use ($bean, $milkItem, $cup) {
            $product->recipes()->updateOrCreate(['inventory_item_id' => $bean->id, 'product_variant_id' => null], ['quantity' => $product->slug === 'espresso' ? 18 : 20]);
            $product->recipes()->updateOrCreate(['inventory_item_id' => $cup->id, 'product_variant_id' => null], ['quantity' => 1]);
            if (! in_array($product->slug, ['espresso', 'americano'], true)) {
                $product->recipes()->updateOrCreate(['inventory_item_id' => $milkItem->id, 'product_variant_id' => null], ['quantity' => 180]);
            }
        });

        $this->call(DemoDataSeeder::class);
    }
}
