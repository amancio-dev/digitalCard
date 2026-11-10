<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardTransaction extends Model
{
    use SoftDeletes;

    public const TYPE_RECHARGE = 'recarga';

    public const TYPE_PURCHASE = 'compra';

    public const TYPE_CASHBACK = 'cashback';

    public const TYPE_REFUND = 'estorno';

    protected $fillable = [
        'virtual_card_id',
        'type',
        'amount',
        'balance_after',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function virtualCard(): BelongsTo
    {
        return $this->belongsTo(VirtualCard::class);
    }
}
