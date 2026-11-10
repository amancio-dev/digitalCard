<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Recharge;
use App\Models\User;
use App\Models\VirtualCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalBalance = (float) VirtualCard::sum('balance');
        $totalCashback = (float) VirtualCard::sum('cashback_earned');
        $pendingRechargesCount = Recharge::where('status', Recharge::STATUS_PENDING)->count();
        $pendingRechargesAmount = (float) Recharge::where('status', Recharge::STATUS_PENDING)->sum('amount');
        $totalSales = (float) Order::whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_RETURNED])->sum('total_amount');
        $pendingOrdersCount = Order::whereIn('status', [Order::STATUS_REQUESTED, Order::STATUS_SEPARATING, Order::STATUS_SHIPPED])->count();
        $deliveredOrdersCount = Order::where('status', Order::STATUS_DELIVERED)->count();

        $pendingRecharges = Recharge::with('virtualCard.user')
            ->where('status', Recharge::STATUS_PENDING)
            ->latest()
            ->limit(10)
            ->get();

        $pendingOrders = Order::with('user', 'items.product')
            ->whereIn('status', [Order::STATUS_REQUESTED, Order::STATUS_SEPARATING, Order::STATUS_SHIPPED])
            ->latest()
            ->limit(10)
            ->get();

        // Busca e paginação de produtos no painel administrativo
        $productQuery = Product::with('category')->latest();
        if ($productSearch = trim((string) $request->input('product_q'))) {
            $escaped = addcslashes($productSearch, '%_\\');
            $productQuery->whereRaw("name LIKE ? ESCAPE '\\'", ["%{$escaped}%"]);
        }

        $products = $productQuery->paginate(15)->withQueryString();
        $categories = Category::withCount(['products', 'children'])->orderBy('name')->get();
        $coupons = Coupon::orderByDesc('created_at')->get();
        $users = User::with('virtualCard')->latest()->limit(20)->get();
        $auditLogs = AuditLog::with('user')->latest('created_at')->limit(20)->get();

        return view('admin.dashboard', compact(
            'totalBalance',
            'totalCashback',
            'pendingRechargesCount',
            'pendingRechargesAmount',
            'totalSales',
            'pendingOrdersCount',
            'deliveredOrdersCount',
            'pendingRecharges',
            'pendingOrders',
            'products',
            'categories',
            'coupons',
            'users',
            'auditLogs',
        ));
    }

    /**
     * Cadastra produto sem fabricar compare_at_price falso, limitando cashback_percent e suportando upload de imagem.
     */
    public function storeProduct(Request $request): RedirectResponse
    {
        $maxCashback = (float) config('digitalcard.max_cashback_percent', 15.0);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0.01'],
            'cashback_percent' => ['required', 'numeric', 'min:0', 'max:'.$maxCashback],
            'stock' => ['required', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:1000'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $imagePath = $validated['image_path'] ?? null;
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('products', 'public');
        }

        // Somente grava compare_at_price quando realmente informado pelo administrador e maior que o preço
        $compareAt = ! empty($validated['compare_at_price']) && (float) $validated['compare_at_price'] > (float) $validated['price']
            ? (float) $validated['compare_at_price']
            : null;

        $product = Product::create([
            'category_id' => (int) $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'price' => (float) $validated['price'],
            'compare_at_price' => $compareAt,
            'cashback_percent' => (float) $validated['cashback_percent'],
            'stock' => (int) $validated['stock'],
            'image_path' => $imagePath,
            'is_active' => true,
        ]);

        AuditLog::record(
            'product.created',
            $product,
            null,
            ['name' => $product->name, 'price' => (float) $product->price, 'stock' => $product->stock],
            $request->user()->id
        );

        return back()->with('success', "Produto \"{$validated['name']}\" cadastrado com sucesso no catálogo.");
    }

    /**
     * Permite editar descrição, limpar compare_at_price e enviar upload de imagem.
     */
    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $maxCashback = (float) config('digitalcard.max_cashback_percent', 15.0);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0.01'],
            'clear_compare_at_price' => ['nullable', 'boolean'],
            'cashback_percent' => ['required', 'numeric', 'min:0', 'max:'.$maxCashback],
            'stock' => ['required', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:1000'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldValues = [
            'name' => $product->name,
            'price' => (float) $product->price,
            'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
            'stock' => (int) $product->stock,
        ];

        $imagePath = ($validated['image_path'] ?? null) ?: $product->image_path;
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('products', 'public');
        }

        if ($request->boolean('clear_compare_at_price') || ($request->has('compare_at_price') && empty($validated['compare_at_price']))) {
            $compareAt = null;
        } elseif (! empty($validated['compare_at_price'])) {
            $compareAt = (float) $validated['compare_at_price'];
        } else {
            $compareAt = $product->compare_at_price;
        }

        $product->update([
            'name' => $validated['name'],
            'category_id' => (int) $validated['category_id'],
            'description' => array_key_exists('description', $validated) && $validated['description'] !== null && $validated['description'] !== ''
                ? $validated['description']
                : $product->description,
            'price' => (float) $validated['price'],
            'compare_at_price' => $compareAt,
            'cashback_percent' => (float) $validated['cashback_percent'],
            'stock' => (int) $validated['stock'],
            'image_path' => $imagePath,
        ]);

        AuditLog::record(
            'product.updated',
            $product,
            $oldValues,
            [
                'name' => $product->name,
                'price' => (float) $product->price,
                'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                'stock' => (int) $product->stock,
            ],
            $request->user()->id
        );

        return back()->with('success', "Produto \"{$product->name}\" atualizado com sucesso.");
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'icon' => ['nullable', 'string', 'max:20'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => ! empty($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'icon' => ($validated['icon'] ?? null) ?: 'package',
        ]);

        Cache::forget('nav_categories_tree');

        AuditLog::record('category.created', $category, null, ['name' => $category->name], $request->user()->id);

        return back()->with('success', "Categoria \"{$validated['name']}\" criada com sucesso.");
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:20'],
        ]);

        $oldName = $category->name;
        $category->update([
            'name' => $validated['name'],
            'icon' => ($validated['icon'] ?? null) ?: $category->icon,
        ]);

        Cache::forget('nav_categories_tree');

        AuditLog::record('category.updated', $category, ['name' => $oldName], ['name' => $category->name], $request->user()->id);

        return back()->with('success', "Categoria \"{$category->name}\" atualizada com sucesso.");
    }

    /**
     * Exclui categoria com segurança (bloqueia se possuir produtos ou subcategorias).
     */
    public function destroyCategory(Request $request, Category $category): RedirectResponse
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Não é possível excluir uma categoria que possui produtos ou subcategorias vinculadas.',
            ]);
        }

        $name = $category->name;
        AuditLog::record('category.deleted', $category, ['name' => $name], null, $request->user()->id);
        $category->delete();

        Cache::forget('nav_categories_tree');

        return back()->with('success', "Categoria \"{$name}\" excluída com sucesso.");
    }

    public function toggleProduct(Request $request, Product $product): RedirectResponse
    {
        $oldActive = (bool) $product->is_active;
        $product->update(['is_active' => ! $oldActive]);

        AuditLog::record(
            'product.toggled',
            $product,
            ['is_active' => $oldActive],
            ['is_active' => (bool) $product->is_active],
            $request->user()->id
        );

        return back()->with('success', $product->is_active
            ? "Produto \"{$product->name}\" reativado no catálogo."
            : "Produto \"{$product->name}\" desativado do catálogo.");
    }

    /**
     * CRUD de Cupons Promocionais no painel administrativo.
     */
    public function storeCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_total' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $code = strtoupper(trim($validated['code']));

        $coupon = Coupon::updateOrCreate(
            ['code' => $code],
            [
                'label' => $validated['label'],
                'type' => $validated['type'],
                'value' => (float) $validated['value'],
                'min_total' => (float) ($validated['min_total'] ?? 0.0),
                'max_uses' => $validated['max_uses'] ?? null,
                'per_user_limit' => (int) ($validated['per_user_limit'] ?? 1),
                'starts_at' => now()->subMinute(),
                'ends_at' => ! empty($validated['ends_at']) ? $validated['ends_at'] : null,
                'is_active' => true,
            ]
        );

        AuditLog::record(
            'coupon.saved',
            $coupon,
            null,
            ['code' => $coupon->code, 'type' => $coupon->type, 'value' => (float) $coupon->value],
            $request->user()->id
        );

        return back()->with('success', "Cupom \"{$coupon->code}\" salvo com sucesso.");
    }

    public function toggleCoupon(Request $request, Coupon $coupon): RedirectResponse
    {
        $oldActive = (bool) $coupon->is_active;
        $coupon->update(['is_active' => ! $oldActive]);

        AuditLog::record(
            'coupon.toggled',
            $coupon,
            ['is_active' => $oldActive],
            ['is_active' => (bool) $coupon->is_active],
            $request->user()->id
        );

        return back()->with('success', $coupon->is_active
            ? "Cupom {$coupon->code} ativado."
            : "Cupom {$coupon->code} desativado.");
    }

    /**
     * Exporta relatório de vendas por período em formato CSV.
     */
    public function exportSalesCsv(Request $request): StreamedResponse
    {
        $orders = Order::with('user')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'relatorio-vendas-digitalcard-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Pedido', 'Data', 'Cliente', 'Email', 'Status', 'Subtotal (R$)', 'Desconto (R$)', 'Frete (R$)', 'Total Pago (R$)', 'Cashback (R$)'], ';');

            foreach ($orders as $order) {
                fputcsv($handle, [
                    '#PED-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                    $order->created_at?->format('d/m/Y H:i'),
                    $order->user?->name ?? 'N/A',
                    $order->user?->email ?? 'N/A',
                    $order->status,
                    number_format((float) $order->subtotal_amount, 2, ',', ''),
                    number_format((float) $order->discount_amount, 2, ',', ''),
                    number_format((float) $order->shipping_cost, 2, ',', ''),
                    number_format((float) $order->total_amount, 2, ',', ''),
                    number_format((float) $order->cashback_total, 2, ',', ''),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
