<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VirtualCardController;
use App\Models\Recharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Catálogo Público e Sitemap SEO (V6)
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/sitemap.xml', [CatalogController::class, 'sitemap'])->name('sitemap');
Route::get('/categoria/{categorySlug}', [CatalogController::class, 'index'])->name('catalog.category');
Route::get('/produto/{product:slug}', [CatalogController::class, 'show'])->name('catalog.product');

// CEP / Endereço Rápido e Lista de Favoritos
Route::post('/endereco-entrega', [CatalogController::class, 'saveAddress'])->name('address.save');
Route::get('/favoritos', [CatalogController::class, 'wishlistIndex'])->name('wishlist.index');
Route::post('/favoritos/{product}', [CatalogController::class, 'toggleWishlist'])->name('wishlist.toggle');

// Carrinho de Compras acessível para visitantes (sessão) e persistido para usuários autenticados
Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrinho/adicionar/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrinho/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrinho/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/carrinho', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/carrinho/cupom', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/carrinho/cupom', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/dashboard', function () {
    return redirect()->route('catalog.index');
})->middleware(['auth'])->name('dashboard');

// Rotas Autenticadas de Perfil (Breeze)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rotas financeiras, cartão virtual, checkout e pedidos exigem e-mail verificado ('verified')
Route::middleware(['auth', 'verified'])->group(function () {
    // Cartão Virtual, PIX Simulado (com status JSON para polling B5), Revelação de CVV sob demanda e Recarga Privada
    Route::get('/meu-cartao', [VirtualCardController::class, 'show'])->name('card.show');
    Route::post('/meu-cartao/recarga', [VirtualCardController::class, 'storeRecharge'])->name('card.recharge');
    Route::post('/meu-cartao/pix/gerar', [VirtualCardController::class, 'generatePixCharge'])->name('card.pix.generate');
    Route::get('/meu-cartao/pix/{recharge}/status', [VirtualCardController::class, 'checkPixStatus'])->name('card.pix.status');
    Route::post('/webhooks/pix/simular', [VirtualCardController::class, 'simulatePixWebhook'])->name('webhooks.pix.simulate');
    Route::post('/meu-cartao/revelar-cvv', [VirtualCardController::class, 'revealCvv'])->name('card.reveal-cvv');
    Route::get('/recargas/{recharge}/comprovante', [VirtualCardController::class, 'showReceipt'])->name('recharges.receipt');
    Route::patch('/meu-cartao/bloqueio', [VirtualCardController::class, 'toggleBlock'])->name('card.toggle-block');
    Route::patch('/meu-cartao/pin', [VirtualCardController::class, 'updatePin'])->name('card.update-pin');

    // Compras, Acompanhamento de Pedidos e Direito de Arrependimento (7 dias)
    Route::post('/checkout', [OrderController::class, 'checkout'])->name('orders.checkout');
    Route::get('/meus-pedidos', [OrderController::class, 'myOrders'])->name('orders.index');
    Route::get('/meus-pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/meus-pedidos/{order}/cancelar', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/meus-pedidos/{order}/arrependimento', [OrderController::class, 'requestRegretReturn'])->name('orders.return');

    // Avaliações Verificadas de Produtos
    Route::post('/produto/{product:slug}/avaliar', [CatalogController::class, 'storeReview'])->name('catalog.review');
});

// Rotas de Administração
Route::middleware(['auth', 'verified', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/relatorios/vendas.csv', [AdminDashboardController::class, 'exportSalesCsv'])->name('reports.sales.csv');

    Route::get('/recargas', function (Request $request) {
        $currentTab = $request->input('tab', 'pendente');

        $query = Recharge::with(['virtualCard.user', 'reviewer'])->latest();

        if (in_array($currentTab, [Recharge::STATUS_PENDING, Recharge::STATUS_APPROVED, Recharge::STATUS_REJECTED], true)) {
            $query->where('status', $currentTab);
        }

        $recharges = $query->paginate(15)->withQueryString();

        $counts = [
            'pendente' => Recharge::where('status', Recharge::STATUS_PENDING)->count(),
            'aprovado' => Recharge::where('status', Recharge::STATUS_APPROVED)->count(),
            'recusado' => Recharge::where('status', Recharge::STATUS_REJECTED)->count(),
            'todos' => Recharge::count(),
        ];

        return view('admin.recharges.index', compact('recharges', 'currentTab', 'counts'));
    })->name('recharges.index');

    Route::patch('/recargas/{recharge}/aprovar', [VirtualCardController::class, 'approveRecharge'])
        ->name('recharges.approve');
    Route::patch('/recargas/{recharge}/recusar', [VirtualCardController::class, 'rejectRecharge'])
        ->name('recharges.reject');

    Route::get('/pedidos', [OrderController::class, 'adminIndex'])->name('orders.index');
    Route::patch('/pedidos/{order}/entregar', [OrderController::class, 'markAsDelivered'])
        ->name('orders.deliver');
    Route::patch('/pedidos/{order}/status', [OrderController::class, 'updateStatus'])
        ->name('orders.status');
    Route::patch('/pedidos/{order}/cancelar', [OrderController::class, 'adminCancelOrder'])
        ->name('orders.cancel');

    Route::post('/produtos', [AdminDashboardController::class, 'storeProduct'])
        ->name('products.store');
    Route::patch('/produtos/{product}', [AdminDashboardController::class, 'updateProduct'])
        ->name('products.update');
    Route::patch('/produtos/{product}/status', [AdminDashboardController::class, 'toggleProduct'])
        ->name('products.toggle');

    Route::post('/categorias', [AdminDashboardController::class, 'storeCategory'])
        ->name('categories.store');
    Route::patch('/categorias/{category}', [AdminDashboardController::class, 'updateCategory'])
        ->name('categories.update');
    Route::delete('/categorias/{category}', [AdminDashboardController::class, 'destroyCategory'])
        ->name('categories.destroy');

    Route::post('/cupons', [AdminDashboardController::class, 'storeCoupon'])
        ->name('coupons.store');
    Route::patch('/cupons/{coupon}/status', [AdminDashboardController::class, 'toggleCoupon'])
        ->name('coupons.toggle');
});

require __DIR__.'/auth.php';
