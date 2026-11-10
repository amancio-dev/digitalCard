<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_page_can_be_rendered_with_seeded_categories_and_products(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->get(route('catalog.index'));

        $response->assertStatus(200);
        $response->assertSee('Eletrônicos');
        $response->assertSee('Informática');
        $response->assertSee('Casa &amp; Cozinha', false);
        $response->assertSee('Games');
    }

    public function test_catalog_can_filter_by_parent_category(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->get(route('catalog.category', 'eletronicos'));

        $response->assertStatus(200);
        $response->assertSee('Smartphone Galaxy S24 Ultra');
        $response->assertSee('Headphone Sony WH-1000XM5');
        $response->assertDontSee('PlayStation 5 Slim');
    }

    public function test_catalog_can_filter_by_child_category(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->get(route('catalog.category', 'consoles'));

        $response->assertStatus(200);
        $response->assertSee('PlayStation 5 Slim');
        $response->assertDontSee('Air Fryer Philips Walita');
    }

    public function test_catalog_search_filters_products_by_keyword(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->get(route('catalog.index', ['q' => 'PlayStation']));

        $response->assertStatus(200);
        $response->assertSee('PlayStation 5 Slim');
        $response->assertDontSee('Notebook Dell Inspiron');
    }

    public function test_catalog_can_filter_by_price_range(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->get(route('catalog.index', [
            'min_price' => 500,
            'max_price' => 1000,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Mouse Sem Fio Logitech MX Master 3S');
        $response->assertSee('Air Fryer Philips Walita');
        $response->assertDontSee('Smartphone Galaxy S24 Ultra');
    }

    public function test_catalog_can_sort_by_lowest_and_highest_price(): void
    {
        $this->seed(CatalogSeeder::class);

        $lowest = $this->get(route('catalog.index', ['sort' => 'menor_preco']));
        $lowest->assertStatus(200);
        $lowest->assertSeeInOrder(['Mouse Sem Fio Logitech MX Master 3S', 'Air Fryer Philips Walita']);

        $highest = $this->get(route('catalog.index', ['sort' => 'maior_preco']));
        $highest->assertStatus(200);
        $highest->assertSeeInOrder(['Apple iPhone 16 Pro Max', 'Smartphone Galaxy S24 Ultra']);
    }

    public function test_product_detail_page_can_be_viewed(): void
    {
        $this->seed(CatalogSeeder::class);

        $product = Product::where('slug', 'playstation-5-slim-1tb')->firstOrFail();

        $response = $this->get(route('catalog.product', $product->slug));

        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee($product->description);
        $response->assertSee(number_format((float) $product->price, 2, ',', '.'));
    }

    public function test_product_and_category_factories_work(): void
    {
        $category = Category::factory()->create([
            'name' => 'Acessórios Especiais',
            'slug' => 'acessorios-especiais',
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Produto de Teste Exclusivo',
            'slug' => 'produto-teste-exclusivo',
            'price' => 299.90,
            'stock' => 15,
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'acessorios-especiais']);
        $this->assertDatabaseHas('products', ['slug' => 'produto-teste-exclusivo']);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_authenticated_user_can_review_product_only_after_delivered_purchase(): void
    {
        $this->seed(CatalogSeeder::class);

        $user = User::factory()->create();
        $product = Product::where('slug', 'playstation-5-slim-1tb')->firstOrFail();

        // Usuário que NÃO comprou/recebeu o produto é bloqueado de avaliar
        $this->actingAs($user)->post(route('catalog.review', $product->slug), [
            'rating' => 5,
            'comment' => 'Tentativa sem compra.',
        ])->assertSessionHasErrors('rating');

        // Cria pedido entregue para o usuário
        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => $product->price,
            'cashback_total' => 10.00,
            'status' => Order::STATUS_DELIVERED,
            'shipping_address' => 'Rua Verificada, 100',
            'delivered_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'item_status' => Order::STATUS_DELIVERED,
        ]);

        $response = $this->actingAs($user)->post(route('catalog.review', $product->slug), [
            'rating' => 5,
            'comment' => 'Console espetacular, entrega muito rápida!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Console espetacular, entrega muito rápida!',
            'is_verified_purchase' => true,
        ]);
    }

    public function test_admin_can_create_and_update_products_and_categories(): void
    {
        $this->seed(CatalogSeeder::class);

        $admin = User::factory()->admin()->create();

        $catResponse = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Áudio Hi-Fi',
            'icon' => '🎵',
        ]);
        $catResponse->assertRedirect();
        $category = Category::where('slug', 'audio-hi-fi')->firstOrFail();

        $prodResponse = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Amplificador Valvulado Studio Pro',
            'category_id' => $category->id,
            'description' => 'Som cristalino de alta fidelidade.',
            'price' => 3299.00,
            'compare_at_price' => 3899.00,
            'cashback_percent' => 4.5,
            'stock' => 8,
        ]);
        $prodResponse->assertRedirect();
        $product = Product::where('slug', 'amplificador-valvulado-studio-pro')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'name' => 'Amplificador Valvulado Studio Pro MK2',
            'category_id' => $category->id,
            'price' => 2999.00,
            'compare_at_price' => 3899.00,
            'cashback_percent' => 5.0,
            'stock' => 12,
        ]);
        $updateResponse->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Amplificador Valvulado Studio Pro MK2',
            'price' => 2999.00,
            'stock' => 12,
        ]);
    }
}
