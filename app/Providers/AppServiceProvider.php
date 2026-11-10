<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Recharge;
use App\Models\User;
use App\Policies\RechargePolicy;
use App\Services\Payment\SimulatedPixGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, SimulatedPixGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale', 'pt_BR'));

        Gate::define('admin', fn (User $user) => (bool) $user->is_admin);
        Gate::policy(Recharge::class, RechargePolicy::class);

        Password::defaults(function () {
            $rule = Password::min(10)->letters()->numbers();

            return $this->app->isProduction()
                ? $rule->mixedCase()->symbols()->uncompromised()
                : Password::min(8);
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
            DB::statement('PRAGMA journal_mode = WAL;');
            DB::statement('PRAGMA busy_timeout = 5000;');
        }

        // Armazena APENAS arrays primitivos no Cache::remember('nav_categories_tree')
        // para compatibilidade total com config('cache.serializable_classes') === false quando CACHE_STORE=database.
        View::composer('layouts.navigation', function ($view) {
            $navCategories = [];
            if (Schema::hasTable('categories')) {
                $cachedTree = Cache::remember('nav_categories_tree', 3600, function (): array {
                    return Category::with('children')
                        ->whereNull('parent_id')
                        ->orderBy('id')
                        ->get()
                        ->map(fn (Category $cat): array => [
                            'id' => (int) $cat->id,
                            'name' => (string) $cat->name,
                            'slug' => (string) $cat->slug,
                            'icon' => $cat->icon,
                            'children' => $cat->children
                                ->map(fn (Category $child): array => [
                                    'id' => (int) $child->id,
                                    'name' => (string) $child->name,
                                    'slug' => (string) $child->slug,
                                    'icon' => $child->icon,
                                ])
                                ->values()
                                ->all(),
                        ])
                        ->values()
                        ->all();
                });

                $navCategories = is_array($cachedTree) ? $cachedTree : [];
            }

            $adminPendingRechargesCount = auth()->check() && auth()->user()->is_admin
                ? Recharge::where('status', Recharge::STATUS_PENDING)->count()
                : 0;
            $adminPendingOrdersCount = auth()->check() && auth()->user()->is_admin
                ? Order::whereIn('status', [Order::STATUS_REQUESTED, Order::STATUS_SEPARATING, Order::STATUS_SHIPPED])->count()
                : 0;

            $rawCart = session('cart', []);
            $cartItemCount = collect($rawCart)->sum('quantity');
            $wishlistCount = count(session('wishlist', []));

            $drawerItems = [];
            $drawerTotal = 0.0;
            $drawerCashback = 0.0;

            if (! empty($rawCart) && Schema::hasTable('products')) {
                $drawerProducts = Product::whereIn('id', array_keys($rawCart))->get()->keyBy('id');
                $maxCb = (float) config('digitalcard.max_cashback_percent', 15.0);
                foreach ($rawCart as $pid => $entry) {
                    $prod = $drawerProducts->get((int) $pid);
                    if ($prod && $prod->is_active) {
                        $qty = min((int) $entry['quantity'], max(1, $prod->stock));
                        $sub = round((float) $prod->price * $qty, 2);
                        $cbPct = min((float) $prod->cashback_percent, $maxCb);
                        $cb = round($sub * ($cbPct / 100), 2);
                        $drawerItems[] = [
                            'product' => $prod,
                            'quantity' => $qty,
                            'subtotal' => $sub,
                            'cashback' => $cb,
                        ];
                        $drawerTotal += $sub;
                        $drawerCashback += $cb;
                    }
                }
            }

            $view->with(compact(
                'navCategories',
                'adminPendingRechargesCount',
                'adminPendingOrdersCount',
                'cartItemCount',
                'wishlistCount',
                'drawerItems',
                'drawerTotal',
                'drawerCashback',
            ));
        });
    }
}
