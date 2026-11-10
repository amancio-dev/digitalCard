<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_request_with_database_cache_store_returns_200_without_incomplete_class_error(): void
    {
        $this->seed(CatalogSeeder::class);

        // Força o driver 'database' (onde serializable_classes => false está ativo)
        config(['cache.default' => 'database']);
        Cache::store('database')->forget('nav_categories_tree');

        $first = $this->get(route('catalog.index'));
        $first->assertOk();
        $first->assertSee('Eletrônicos');

        $second = $this->get(route('catalog.index'));
        $second->assertOk();
        $second->assertSee('Eletrônicos');
        $second->assertSee('Smartphones');
    }

    public function test_mass_assignment_cannot_escalate_is_admin_and_security_headers_are_present(): void
    {
        $user = new User([
            'name' => 'Tentativa Admin',
            'email' => 'hacker@example.com',
            'password' => 'password1234',
            'is_admin' => true,
        ]);
        $user->save();

        $this->assertFalse((bool) $user->fresh()->is_admin);

        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_no_fabricated_ratings_or_fake_discounts_and_sitemap_works(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Teste Real', 'slug' => 'teste-real']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Produto Sem Desconto Falso',
            'category_id' => $category->id,
            'description' => 'Descrição real.',
            'price' => 100.00,
            'cashback_percent' => 5.0,
            'stock' => 10,
        ])->assertSessionHas('success');

        $product = Product::where('slug', 'produto-sem-desconto-falso')->firstOrFail();

        $this->assertNull($product->compare_at_price);
        $this->assertEquals(0, $product->discount_percent);
        $this->assertEquals(0.0, $product->rating);
        $this->assertEquals(0, $product->reviews_count);

        $sitemap = $this->get(route('sitemap'));
        $sitemap->assertOk();
        $sitemap->assertSee(route('catalog.product', $product->slug), false);
    }

    public function test_local_webp_images_and_dedicated_admin_layout_and_seo_meta(): void
    {
        $this->seed(CatalogSeeder::class);
        $product = Product::firstOrFail();

        // Imagem principal e miniatura apontam para arquivos WebP locais em /images/products/
        $this->assertStringContainsString('/images/products/'.$product->slug.'.webp', $product->image_url);
        $this->assertStringContainsString('/images/products/'.$product->slug.'-400.webp', $product->thumbnail_url);

        // Painel admin usa layout dedicado sem cabeçalho de comprador ("SALDO DIGITALCARD") e com noindex
        $admin = User::factory()->admin()->create();
        $adminResp = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminResp->assertOk();
        $adminResp->assertSee('Painel Admin');
        $adminResp->assertSee('Voltar à Loja');
        $adminResp->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $adminResp->assertDontSee('SALDO DIGITALCARD');
    }
}
