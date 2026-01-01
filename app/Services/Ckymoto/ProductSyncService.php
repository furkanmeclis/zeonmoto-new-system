<?php

namespace App\Services\Ckymoto;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductExternal;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductSyncService
{
    /**
     * External ürünü senkronize eder
     *
     * @param array<string, mixed> $externalProduct
     * @param string $provider
     * @param bool $syncImages
     * @param bool $priceOnly
     * @param bool $newProductsOnly
     * @return Product|null
     * @throws \Exception
     */
    public function syncProduct(
        array $externalProduct,
        string $provider = 'ckymoto',
        bool $syncImages = true,
        bool $priceOnly = false,
        bool $newProductsOnly = false
    ): ?Product {
        $hash = $this->generateExternalHash($provider, $externalProduct['uniqid'] ?? '');

        if (empty($hash)) {
            throw new \Exception('Invalid external product: uniqid is required');
        }

        $productExternal = ProductExternal::where('external_hash', $hash)->first();

        if ($productExternal) {
            // newProductsOnly modunda mevcut ürünleri atla
            if ($newProductsOnly) {
                return null;
            }

            return $this->updateProduct($productExternal->product, $externalProduct, $provider, $syncImages, $priceOnly);
        }

        return $this->createProduct($externalProduct, $provider, $syncImages);
    }

    /**
     * Yeni ürün oluşturur
     *
     * @param array<string, mixed> $externalProduct
     * @param string $provider
     * @param bool $syncImages
     * @return Product
     * @throws \Exception
     */
    protected function createProduct(array $externalProduct, string $provider, bool $syncImages = true): Product
    {
        return DB::transaction(function () use ($externalProduct, $provider, $syncImages) {
            $sku = $externalProduct['sku'] ?? '';
            
            // SKU ile mevcut ürün kontrolü
            $existingProduct = !empty($sku) ? Product::where('sku', $sku)->first() : null;
            
            if ($existingProduct) {
                // Ürün var ama ProductExternal kaydı yoksa, mevcut ürünü kullan
                $hash = $this->generateExternalHash($provider, $externalProduct['uniqid'] ?? '');
                $productExternal = ProductExternal::where('external_hash', $hash)->first();
                
                if (! $productExternal) {
                    // ProductExternal kaydı oluştur
                    ProductExternal::create([
                        'product_id' => $existingProduct->id,
                        'provider_key' => $provider,
                        'external_uniqid' => $externalProduct['uniqid'] ?? '',
                        'external_hash' => $hash,
                    ]);
                }
                
                // Mevcut ürünü güncelle
                return $this->updateProduct($existingProduct, $externalProduct, $provider, $syncImages, false);
            }

            // Son sort_order değerini al
            $lastSortOrder = Product::max('sort_order') ?? 0;

            // Yeni ürün oluştur - try-catch ile UNIQUE constraint hatasını yakala
            try {
                $product = Product::create([
                    'name' => $externalProduct['name'] ?? 'Unknown Product',
                    'sku' => $sku,
                    'base_price' => $externalProduct['price'] ?? 0,
                    // final_price dinamik hesaplanır, set edilmez
                    // custom_price kullanıcı tarafından manuel girilir
                    'is_active' => false, // Admin kontrolü şart
                    'sort_order' => $lastSortOrder + 1,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // UNIQUE constraint violation (SKU duplicate)
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                    // SKU ile mevcut ürünü bul
                    $existingProduct = Product::where('sku', $sku)->first();
                    
                    if ($existingProduct) {
                        // External hash hesapla
                        $hash = $this->generateExternalHash($provider, $externalProduct['uniqid'] ?? '');
                        
                        // ProductExternal kaydı oluştur (yoksa)
                        $productExternal = ProductExternal::where('external_hash', $hash)->first();
                        if (! $productExternal) {
                            ProductExternal::create([
                                'product_id' => $existingProduct->id,
                                'provider_key' => $provider,
                                'external_uniqid' => $externalProduct['uniqid'] ?? '',
                                'external_hash' => $hash,
                            ]);
                        }
                        
                        // Mevcut ürünü güncelle
                        return $this->updateProduct($existingProduct, $externalProduct, $provider, $syncImages, false);
                    }
                }
                
                // Diğer hatalar için fırlat
                throw $e;
            }

            // External hash hesapla
            $hash = $this->generateExternalHash($provider, $externalProduct['uniqid'] ?? '');

            // ProductExternal kaydı oluştur
            ProductExternal::create([
                'product_id' => $product->id,
                'provider_key' => $provider,
                'external_uniqid' => $externalProduct['uniqid'] ?? '',
                'external_hash' => $hash,
            ]);

            // External görselleri ekle (syncImages true ise)
            if ($syncImages) {
                $this->syncProductImages($product, $externalProduct['images'] ?? []);
            }

            // Kategori ilişkisi kur (syncImages true ise - yeni ürünlerde kategori de dahil)
            if ($syncImages && ! empty($externalProduct['category'])) {
                $categoryName = trim($externalProduct['category']);
                $category = Category::where('external_name', $categoryName)->first();
                if ($category) {
                    $product->categories()->syncWithoutDetaching([$category->id]);
                }
            }

            // Kategori bilgisi varsa logla
            $categoryInfo = [];
            if (! empty($externalProduct['category'])) {
                $categoryName = trim($externalProduct['category']);
                $category = Category::where('external_name', $categoryName)->first();
                $categoryInfo = [
                    'external_category' => $categoryName,
                    'category_exists' => $category !== null,
                    'category_id' => $category?->id,
                ];
            }

            Log::info('Product created from external source', array_merge([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'provider' => $provider,
                'hash' => $hash,
            ], $categoryInfo));

            return $product;
        });
    }

    /**
     * Mevcut ürünü günceller
     *
     * @param Product $product
     * @param array<string, mixed> $externalProduct
     * @param string $provider
     * @param bool $syncImages
     * @param bool $priceOnly
     * @return Product
     */
    protected function updateProduct(
        Product $product,
        array $externalProduct,
        string $provider,
        bool $syncImages = true,
        bool $priceOnly = false
    ): Product {
        return DB::transaction(function () use ($product, $externalProduct, $provider, $syncImages, $priceOnly) {
            // priceOnly modunda sadece fiyat güncellenir
            if ($priceOnly) {
                $product->update([
                    'base_price' => $externalProduct['price'] ?? $product->base_price,
                ]);
            } else {
                // Sadece güncellenebilir alanları güncelle
                $product->update([
                    'name' => $externalProduct['name'] ?? $product->name,
                    'sku' => $externalProduct['sku'] ?? $product->sku,
                    'base_price' => $externalProduct['price'] ?? $product->base_price,
                    // custom_price güncellenmez (kullanıcı tarafından manuel girilir)
                    // final_price güncellenmez (dinamik hesaplanır)
                ]);
            }

            // is_active, sort_order, custom_price, final_price güncellenmez (admin kontrolünde)

            // priceOnly modunda resim ve kategori sync edilmez
            if (! $priceOnly) {
                // Yeni external görselleri ekle (mevcut external görselleri silmez)
                if ($syncImages) {
                    $this->syncProductImages($product, $externalProduct['images'] ?? []);
                }

                // Kategori ilişkisi kur (syncImages true ise)
                if ($syncImages && ! empty($externalProduct['category'])) {
                    $categoryName = trim($externalProduct['category']);
                    $category = Category::where('external_name', $categoryName)->first();
                    if ($category) {
                        $product->categories()->syncWithoutDetaching([$category->id]);
                    }
                }
            }

            // Kategori bilgisi varsa logla
            $categoryInfo = [];
            if (! empty($externalProduct['category'])) {
                $categoryName = trim($externalProduct['category']);
                $category = Category::where('external_name', $categoryName)->first();
                $categoryInfo = [
                    'external_category' => $categoryName,
                    'category_exists' => $category !== null,
                    'category_id' => $category?->id,
                ];
            }

            Log::info('Product updated from external source', array_merge([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'provider' => $provider,
            ], $categoryInfo));

            return $product->fresh();
        });
    }

    /**
     * Ürün görsellerini senkronize eder
     *
     * @param Product $product
     * @param array<string> $imageUrls
     * @return void
     */
    protected function syncProductImages(Product $product, array $imageUrls): void
    {
        if (empty($imageUrls)) {
            return;
        }

        // Mevcut external görsellerin path'lerini kontrol et (URL hash'ine göre)
        $existingImageHashes = $product->images()
            ->where('type', 'external')
            ->whereNotNull('path')
            ->get()
            ->map(function ($image) {
                // Path'ten hash'i çıkar (dosya adı hash olacak)
                $path = $image->path;
                if ($path && preg_match('/\/([a-f0-9]{40})\.(jpg|jpeg|png|gif|webp)$/i', $path, $matches)) {
                    return $matches[1];
                }
                return null;
            })
            ->filter()
            ->toArray();

        // Yeni görselleri ekle
        $sortOrder = $product->images()->max('sort_order') ?? 0;
        $isFirstImage = $product->images()->where('type', 'external')->count() === 0;

        foreach ($imageUrls as $index => $imageUrl) {
            try {
                // URL'den hash oluştur
                $imageHash = sha1($imageUrl);

                // Eğer bu hash ile görsel zaten varsa atla
                if (in_array($imageHash, $existingImageHashes, true)) {
                    continue;
                }

                // External URL'den görseli indir
                $response = Http::timeout(30)
                    ->withOptions([
                        'stream' => false,
                    ])
                    ->get($imageUrl);

                if (! $response->successful()) {
                    Log::warning('Failed to download external image', [
                        'product_id' => $product->id,
                        'url' => $imageUrl,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                // MIME type kontrolü
                $contentType = $response->header('Content-Type');
                $contentTypeParts = explode(';', $contentType);
                $mimeType = trim($contentTypeParts[0]);

                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                ];

                if (! in_array(strtolower($mimeType), $allowedMimeTypes, true)) {
                    Log::warning('Invalid MIME type for external image', [
                        'product_id' => $product->id,
                        'url' => $imageUrl,
                        'mime_type' => $mimeType,
                    ]);
                    continue;
                }

                // Extension belirle
                $extension = match (strtolower($mimeType)) {
                    'image/jpeg', 'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };

                // Storage path oluştur
                $directory = "products/{$product->id}/external";
                $filename = "{$imageHash}.{$extension}";
                $storagePath = "{$directory}/{$filename}";

                // Storage'a kaydet
                $saved = Storage::disk('public')->put($storagePath, $response->body());

                if (! $saved) {
                    Log::error('Failed to save external image to storage', [
                        'product_id' => $product->id,
                        'url' => $imageUrl,
                        'path' => $storagePath,
                    ]);
                    continue;
                }

                // ProductImage kaydı oluştur
                ProductImage::create([
                    'product_id' => $product->id,
                    'type' => 'external',
                    'path' => $storagePath,
                    'is_primary' => $isFirstImage && $index === 0,
                    'sort_order' => $sortOrder + $index + 1,
                ]);

                Log::info('External image synced and saved', [
                    'product_id' => $product->id,
                    'url' => $imageUrl,
                    'path' => $storagePath,
                ]);
            } catch (\Exception $e) {
                Log::error('Error syncing external image', [
                    'product_id' => $product->id,
                    'url' => $imageUrl,
                    'error' => $e->getMessage(),
                ]);
                // Hata durumunda devam et (bir görsel hatası tüm sync'i durdurmasın)
                continue;
            }
        }
    }

    /**
     * External hash üretir
     *
     * @param string $provider
     * @param string $uniqid
     * @return string
     */
    public function generateExternalHash(string $provider, string $uniqid): string
    {
        return sha1("{$provider}|{$uniqid}");
    }
    /**
     * Kategorileri senkronize eder
     *
     * @param array<string, mixed> $externalCategories
     * @param string $provider
     * @return array<Category>
     */
    public function syncCategories(array $externalCategories, string $provider = 'ckymoto'): array
    {
        // CategorySyncService'i çağırarak kategorileri senkronize et
        return app(\App\Services\Ckymoto\CategorySyncService::class)->syncCategories($externalCategories, $provider);
    }
}

