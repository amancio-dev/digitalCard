<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recharge extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pendente';

    public const STATUS_APPROVED = 'aprovado';

    public const STATUS_REJECTED = 'recusado';

    protected $fillable = [
        'virtual_card_id',
        'payment_method',
        'txid',
        'pix_payload',
        'expires_at',
        'amount',
        'approved_amount',
        'receipt_path',
        'receipt_hash',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function virtualCard(): BelongsTo
    {
        return $this->belongsTo(VirtualCard::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
