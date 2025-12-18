<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'external_name',
        'display_name',
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($category) {
            // Slug boşsa, display_name'den oluştur
            if (empty($category->slug) && ! empty($category->display_name)) {
                $category->slug = Str::slug($category->display_name);
            }
        });

        static::updating(function ($category) {
            // display_name değiştiyse ve slug manuel değiştirilmemişse güncelle
            if ($category->isDirty('display_name') && ! $category->isDirty('slug')) {
                // Slug'ı sadece display_name'den güncelle (eğer slug değiştirilmemişse)
                $originalSlug = $category->getOriginal('slug');
                $newSlug = Str::slug($category->display_name);
                
                // Eğer yeni slug ile aynıysa veya slug boşsa, güncelle
                if (empty($originalSlug) || $originalSlug === Str::slug($category->getOriginal('display_name'))) {
                    $category->slug = $newSlug;
                }
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
