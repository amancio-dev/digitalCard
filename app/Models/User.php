<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * is_admin removido de $fillable para impedir escalação de privilégio via Mass Assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->virtualCard()->create([
                'card_number' => VirtualCard::generateUniqueCardNumber(),
                'cvv' => VirtualCard::generateCvv(),
                'expiration_date' => VirtualCard::DEFAULT_EXPIRATION_DATE,
                'balance' => 0.00,
                'cashback_earned' => 0.00,
            ]);
        });
    }

    public function virtualCard(): HasOne
    {
        return $this->hasOne(VirtualCard::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }
}
