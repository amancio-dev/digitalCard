<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'label',
        'type',
        'value',
        'min_total',
        'starts_at',
        'ends_at',
        'max_uses',
        'used_count',
        'per_user_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_total' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'per_user_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isValidForUser(?User $user, int $subtotalCents): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        $minTotalCents = (int) round((float) $this->min_total * 100);
        if ($subtotalCents < $minTotalCents) {
            return false;
        }

        if ($user && $this->per_user_limit !== null) {
            $userUses = $this->redemptions()->where('user_id', $user->id)->count();
            if ($userUses >= $this->per_user_limit) {
                return false;
            }
        }

        return true;
    }
}
