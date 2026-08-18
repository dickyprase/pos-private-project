<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagerUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_product_with_image_from_modal_form(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $category = Category::create([
            'name' => 'Coffee',
            'slug' => 'coffee',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test(ProductManager::class)
            ->call('create')
            ->assertSet('formOpen', true)
            ->set('name', 'Flat White')
            ->set('sku', 'COF-FLW')
            ->set('categoryId', $category->id)
            ->set('basePrice', 32000)
            ->set('costEstimate', 11000)
            ->set('image', UploadedFile::fake()->image('flat-white.jpg', 600, 600))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formOpen', false);

        $product = Product::query()->where('sku', 'COF-FLW')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_management_renders_table_dropdown_and_modal_trigger(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        Category::create([
            'name' => 'Coffee',
            'slug' => 'coffee',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get('/products')
            ->assertOk()
            ->assertSee('Produk baru')
            ->assertSee('data-table', false);

        Livewire::test(ProductManager::class)
            ->call('create')
            ->assertSee('Pilih kategori')
            ->assertSee('Atur detail, harga, gambar, dan status menu.');
    }
}
