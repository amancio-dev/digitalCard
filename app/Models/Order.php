<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const STATUS_REQUESTED = 'Produto solicitado';

    public const STATUS_SEPARATING = 'Em separação';

    public const STATUS_SHIPPED = 'Enviado';

    public const STATUS_DELIVERED = 'Produto entregue';

    public const STATUS_CANCELLED = 'Pedido cancelado';

    public const STATUS_RETURNED = 'Devolvido (Arrependimento)';

    protected $fillable = [
        'user_id',
        'idempotency_key',
        'subtotal_amount',
        'discount_amount',
        'coupon_code',
        'shipping_cost',
        'total_amount',
        'cashback_total',
        'status',
        'shipping_address',
        'shipping_cep',
        'tracking_code',
        'delivered_at',
        'cashback_credited_at',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cashback_total' => 'decimal:2',
        'delivered_at' => 'datetime',
        'cashback_credited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function statusEnum(): OrderStatus
    {
        return OrderStatus::tryFrom((string) $this->status) ?? OrderStatus::REQUESTED;
    }

    /**
     * Verifica se o pedido entregue está dentro do prazo legal de 7 dias corridos de arrependimento (F4).
     */
    public function canRequestRegretReturn(): bool
    {
        if ($this->status !== self::STATUS_DELIVERED || ! $this->delivered_at) {
            return false;
        }

        return now()->lte($this->delivered_at->copy()->addDays(7));
    }
}
