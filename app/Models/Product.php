<?php

namespace App\Models;

use App\Services\Pricing\PriceEngine;
use App\Services\Pricing\PriceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'description',
        'base_price',
        'custom_price',
        'final_price',
        'retail_price',
        'is_active',
        'has_stock',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'custom_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'is_active' => 'boolean',
        'has_stock' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function externals(): HasMany
    {
        return $this->hasMany(ProductExternal::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set retail_price to final_price if it's 0 or null when creating
        static::created(function ($product) {
            if (($product->retail_price === null || $product->retail_price == 0) && $product->exists) {
                $finalPrice = $product->calculatePrice()->final;
                $product->updateQuietly(['retail_price' => $finalPrice]);
            }
        });

        // Set retail_price to final_price if it's 0 when updating
        static::updating(function ($product) {
            if ($product->retail_price == 0 && $product->isDirty(['base_price', 'custom_price'])) {
                // Calculate final price before update
                $finalPrice = $product->calculatePrice()->final;
                $product->retail_price = $finalPrice;
            }

            // Eğer has_stock = true ise ve is_active değişiyorsa, is_active'i koru
            if ($product->has_stock && $product->isDirty('is_active')) {
                // Mevcut is_active değerini koru (Ckymoto'dan gelen değişikliği engelle)
                $product->is_active = $product->getOriginal('is_active');
            }
        });
    }

    /**
     * Get final price using PriceEngine.
     */
    public function getFinalPriceAttribute(?float $value): float
    {
        // If final_price is already set and we're not in a calculation context,
        // we can use it, but for consistency, always calculate
        $result = $this->calculatePrice();
        return $result->final;
    }

    /**
     * Calculate price using PriceEngine.
     */
    public function calculatePrice(?int $dealerId = null): PriceResult
    {
        $engine = app(PriceEngine::class);
        return $engine->calculate($this, $dealerId);
    }

    /**
     * Get default/primary image URL.
     */
    public function getDefaultImageUrlAttribute(): ?string
    {
        $primaryImage = $this->images()->where('is_primary', true)->first();
        
        if ($primaryImage) {
            return $primaryImage->url;
        }

        $firstImage = $this->images()->first();
        
        if ($firstImage) {
            return $firstImage->url;
        }

        return null;
    }

    /**
     * Get formatted description with standard format.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return "{$this->name} - Stok Kodu: {$this->sku}. {$this->description}";
        }

        return "{$this->name} - Stok Kodu: {$this->sku}. Motosiklet yedek parça ve aksesuarları konusunda uzman ekibimizle hizmetinizdeyiz.";
    }

    
}
