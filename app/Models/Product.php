<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'compare_at_price',
        'cashback_percent',
        'stock',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cashback_percent' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Accessor inteligente com fallback local por slug:
     * 1) image_path customizado no storage público
     * 2) Caminho canônico da imagem WebP local do produto em /images/products/{slug}.webp
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && (str_starts_with($this->image_path, 'products/') || str_starts_with($this->image_path, 'uploads/'))) {
            return asset('storage/'.ltrim($this->image_path, '/'));
        }

        return asset('images/products/'.($this->slug ?: 'produto').'.webp');
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->image_path && (str_starts_with($this->image_path, 'products/') || str_starts_with($this->image_path, 'uploads/'))) {
            return asset('storage/'.ltrim($this->image_path, '/'));
        }

        return asset('images/products/'.($this->slug ?: 'produto').'-400.webp');
    }

    public function hasImageFile(): bool
    {
        if ($this->image_path && (str_starts_with($this->image_path, 'products/') || str_starts_with($this->image_path, 'uploads/'))) {
            return file_exists(storage_path('app/public/'.ltrim($this->image_path, '/')));
        }

        return (bool) ($this->slug && file_exists(public_path('images/products/'.$this->slug.'.webp')));
    }

    public function getDiscountPercentAttribute(): int
    {
        if ($this->compare_at_price && (float) $this->compare_at_price > (float) $this->price) {
            return (int) round((1 - ((float) $this->price / (float) $this->compare_at_price)) * 100);
        }

        return 0;
    }

    public function getOriginalPriceAttribute(): float
    {
        if ($this->compare_at_price && (float) $this->compare_at_price > (float) $this->price) {
            return (float) $this->compare_at_price;
        }

        return (float) $this->price;
    }

    /**
     * Atualiza média e contagem de avaliações a partir de product_reviews.
     * Não há colunas persistidas: os valores ficam nos atributos agregados
     * (reviews_avg_rating / reviews_count) lidos pelos accessors abaixo.
     *
     * @return array{rating: float, count: int}
     */
    public function recalculateRating(): array
    {
        $this->loadAvg('reviews', 'rating')->loadCount('reviews');

        return ['rating' => $this->rating, 'count' => $this->reviews_count];
    }

    public function getRatingAttribute(): float
    {
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return $this->attributes['reviews_avg_rating'] !== null
                ? round((float) $this->attributes['reviews_avg_rating'], 1)
                : 0.0;
        }

        if ($this->relationLoaded('reviews')) {
            return $this->reviews->isNotEmpty()
                ? round((float) $this->reviews->avg('rating'), 1)
                : 0.0;
        }

        $avg = $this->reviews()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    public function getReviewsCountAttribute(): int
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        if ($this->relationLoaded('reviews')) {
            return $this->reviews->count();
        }

        return (int) $this->reviews()->count();
    }
}
