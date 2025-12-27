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
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'custom_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'is_active' => 'boolean',
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

    /**
     * Get the route key for the model.
     * Only use {sku}-{id} format for web routes, not Filament admin routes.
     */
    public function getRouteKey(): string
    {
        // Filament admin route'ları için normal ID döndür
        if (request()->is('admin/*')) {
            return (string) $this->id;
        }

        return $this->sku . '-' . $this->id;
    }

    /**
     * Retrieve the model for bound value.
     * Only use custom binding for web routes, not Filament admin routes.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Filament admin route'ları için normal ID binding kullan
        if (request()->is('admin/*')) {
            return $this->where('id', $value)->first();
        }

        // Web route'ları için custom binding
        // Eğer değer sadece ID ise (eski format için geriye dönük uyumluluk)
        if (is_numeric($value)) {
            return $this->where('id', $value)->first();
        }

        // {sku}-{id} formatını parse et
        if (strpos($value, '-') !== false) {
            $parts = explode('-', $value);
            $id = array_pop($parts); // Son kısım ID
            $sku = implode('-', $parts); // Geri kalan kısım SKU (SKU içinde de - olabilir)

            return $this->where('id', $id)
                ->where('sku', $sku)
                ->first();
        }

        return null;
    }
}
