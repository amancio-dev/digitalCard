<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualCard extends Model
{
    use SoftDeletes;

    public const DEFAULT_EXPIRATION_DATE = '12/30';

    /**
     * Prefixo claramente fictício (9900) fora de faixas reais de bandeiras de pagamento.
     */
    public const FICTITIOUS_PREFIX = '9900';

    protected $fillable = [
        'user_id',
        'card_last_four',
        'card_hash',
        'card_number',
        'cvv',
        'expiration_date',
        'security_pin',
        'balance',
        'cashback_earned',
        'is_active',
    ];

    protected $hidden = [
        'card_number',
        'cvv',
        'security_pin',
        'card_hash',
    ];

    protected function casts(): array
    {
        return [
            'card_number' => 'encrypted',
            'cvv' => 'encrypted',
            'balance' => 'decimal:2',
            'cashback_earned' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (VirtualCard $card) {
            if ($card->isDirty('card_number') && ! empty($card->card_number)) {
                $clean = preg_replace('/\D/', '', (string) $card->card_number);
                $card->card_last_four = substr($clean, -4) ?: '0000';
                $card->card_hash = hash('sha256', $clean);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recharges(): HasMany
    {
        return $this->hasMany(Recharge::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CardTransaction::class);
    }

    public static function generateUniqueCardNumber(): string
    {
        do {
            $number = implode(' ', [
                self::FICTITIOUS_PREFIX,
                str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            ]);
            $clean = preg_replace('/\D/', '', $number);
            $hash = hash('sha256', $clean);
        } while (static::withTrashed()->where('card_hash', $hash)->exists());

        return $number;
    }

    public static function generateCvv(): string
    {
        return str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    public function getFormattedNumberAttribute(): string
    {
        $clean = preg_replace('/\D/', '', (string) $this->card_number);

        return implode(' ', str_split($clean, 4));
    }

    public function getLastFourDigitsAttribute(): string
    {
        if (! empty($this->card_last_four) && $this->card_last_four !== '0000') {
            return $this->card_last_four;
        }

        $clean = preg_replace('/\D/', '', (string) $this->card_number);

        return substr($clean, -4) ?: '0000';
    }

    public function getMaskedNumberAttribute(): string
    {
        return self::FICTITIOUS_PREFIX.' •••• •••• '.$this->last_four_digits;
    }
}
