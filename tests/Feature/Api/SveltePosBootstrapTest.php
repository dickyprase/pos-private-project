<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SveltePosBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_returns_user_store_payment_and_shift_configuration(): void
    {
        $user = User::factory()->create(['name' => 'Kasir Svelte']);
        StoreSetting::create([
            'store_name' => 'KopiKita POS',
            'tax_enabled' => true,
            'tax_rate' => 10,
            'qris_enabled' => true,
            'qris_image_path' => 'qris/store.png',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/pos/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Kasir Svelte')
            ->assertJsonPath('data.store.name', 'KopiKita POS')
            ->assertJsonPath('data.store.tax_rate', 10)
            ->assertJsonPath('data.payments.qris_enabled', true)
            ->assertJsonPath('data.shift', null);
    }

    public function test_catalog_exposes_visual_fields_needed_by_svelte_template(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Latte',
            'slug' => 'latte',
            'sku' => 'LATTE',
            'base_price' => 30000,
            'image_path' => 'products/latte.png',
            'is_active' => true,
            'is_available' => true,
            'is_favorite' => true,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.category_slug', 'coffee')
            ->assertJsonPath('data.0.is_favorite', true)
            ->assertJsonPath('data.0.image_url', url('/storage/products/latte.png'));
    }
}
