<?php

namespace Tests\Feature\Api;

use App\Actions\Orders\CompleteOrder;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_success_validation_and_invalid_credentials(): void
    {
        User::factory()->create(['username' => 'kasir', 'password' => 'password']);
        $this->postJson('/api/auth/token', [])->assertStatus(422)->assertJsonPath('success', false);
        $this->postJson('/api/auth/token', ['login' => 'kasir', 'password' => 'salah'])->assertStatus(422);
        $this->postJson('/api/auth/token', ['login' => 'kasir', 'password' => 'password'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_protected_endpoint_rejects_unauthorized_and_catalog_eager_response_works(): void
    {
        $this->getJson('/api/categories')->assertUnauthorized()->assertJsonPath('success', false);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = Category::create(['name' => 'Kopi', 'slug' => 'kopi', 'is_active' => true]);
        Product::create(['category_id' => $category->id, 'name' => 'Kopi Susu', 'slug' => 'kopi-susu', 'sku' => 'KS-1', 'base_price' => 18000, 'is_active' => true, 'is_available' => true]);
        $this->getJson('/api/products')->assertOk()->assertJsonPath('data.0.name', 'Kopi Susu');
    }

    public function test_shift_open_validation_and_duplicate_rule(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/shifts/open', ['opening_cash' => -1])->assertStatus(422);
        $this->postJson('/api/shifts/open', ['opening_cash' => 200000])->assertCreated()->assertJsonPath('data.status', 'OPEN');
        $this->postJson('/api/shifts/open', ['opening_cash' => 100000])->assertStatus(422);
    }

    public function test_order_without_shift_is_rejected_and_identical_submission_is_idempotent(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = Category::create(['name' => 'Kopi', 'slug' => 'kopi', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Americano', 'slug' => 'americano', 'sku' => 'AM-1', 'base_price' => 15000, 'is_active' => true, 'is_available' => true]);
        $payload = ['submission_token' => (string) Str::uuid(), 'table_number' => 'A1', 'customer_name' => 'Budi', 'order_type' => OrderType::DINE_IN->value, 'items' => [['product_id' => $product->id, 'quantity' => 1, 'modifier_ids' => []]], 'payment' => ['method' => PaymentMethod::CASH->value, 'received_amount' => 20000]];
        $this->postJson('/api/orders', $payload)->assertStatus(422)->assertJsonValidationErrors('shift');
        $this->postJson('/api/shifts/open', ['opening_cash' => 0])->assertCreated();
        $first = $this->postJson('/api/orders', $payload)->assertCreated()->json('data.id');
        $second = $this->postJson('/api/orders', $payload)->assertCreated()->json('data.id');
        $this->assertSame($first, $second);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }
}
