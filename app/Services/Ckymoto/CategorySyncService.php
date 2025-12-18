<?php

namespace App\Services\Ckymoto;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategorySyncService
{
    /**
     * Tek bir kategoriyi senkronize eder
     *
     * @param string $externalCategoryName
     * @param string $provider
     * @return Category
     * @throws \Exception
     */
    public function syncCategory(string $externalCategoryName, string $provider = 'ckymoto'): Category
    {
        if (empty($externalCategoryName)) {
            throw new \Exception('External category name cannot be empty');
        }

        return DB::transaction(function () use ($externalCategoryName, $provider) {
            // external_name ile mevcut kategoriyi ara
            $category = Category::where('external_name', $externalCategoryName)->first();

            if ($category) {
                // Mevcut kategori bulundu - hiçbir şey yapma (isim hafızası korunur)
                Log::debug('Category already exists, skipping update', [
                    'external_name' => $externalCategoryName,
                    'category_id' => $category->id,
                    'display_name' => $category->display_name,
                ]);

                return $category;
            }

            // Yeni kategori oluştur
            $lastSortOrder = Category::max('sort_order') ?? 0;

            $category = Category::create([
                'external_name' => $externalCategoryName,
                'display_name' => $externalCategoryName, // Başlangıçta external_name ile aynı
                'slug' => \Illuminate\Support\Str::slug($externalCategoryName),
                'is_active' => false, // Admin kontrolü şart
                'sort_order' => $lastSortOrder + 1,
            ]);

            Log::info('Category created from external source', [
                'category_id' => $category->id,
                'external_name' => $externalCategoryName,
                'display_name' => $category->display_name,
                'provider' => $provider,
            ]);

            return $category;
        });
    }

    /**
     * Toplu kategori senkronizasyonu yapar
     *
     * @param array<string> $externalCategories
     * @param string $provider
     * @return array<Category>
     */
    public function syncCategories(array $externalCategories, string $provider = 'ckymoto'): array
    {
        $syncedCategories = [];
        $successCount = 0;
        $errorCount = 0;

        Log::info('Starting bulk category sync', [
            'provider' => $provider,
            'total' => count($externalCategories),
        ]);

        foreach ($externalCategories as $externalCategoryName) {
            try {
                // Eğer array ise, 'name' veya ilk elemanı al
                if (is_array($externalCategoryName)) {
                    // Eğer 'name' key'i varsa onu kullan
                    if (isset($externalCategoryName['name'])) {
                        $externalCategoryName = $externalCategoryName['name'];
                    } elseif (isset($externalCategoryName[0])) {
                        // İlk elemanı al
                        $externalCategoryName = $externalCategoryName[0];
                    } else {
                        // Array ama uygun key yok, atla
                        Log::warning('Invalid category array structure', [
                            'category' => $externalCategoryName,
                        ]);
                        continue;
                    }
                }

                // String kontrolü
                if (! is_string($externalCategoryName)) {
                    Log::warning('Category is not a string', [
                        'type' => gettype($externalCategoryName),
                        'value' => $externalCategoryName,
                    ]);
                    continue;
                }

                // Trim ve boş değer kontrolü
                $categoryName = trim($externalCategoryName);
                if (empty($categoryName)) {
                    continue;
                }

                $category = $this->syncCategory($categoryName, $provider);
                $syncedCategories[] = $category;
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $categoryNameForLog = is_string($externalCategoryName) 
                    ? $externalCategoryName 
                    : (is_array($externalCategoryName) 
                        ? json_encode($externalCategoryName) 
                        : 'unknown');
                
                Log::error('Failed to sync category', [
                    'provider' => $provider,
                    'external_name' => $categoryNameForLog,
                    'error' => $e->getMessage(),
                ]);

                // Hata durumunda devam et (bir kategori hatası tüm sync'i durdurmasın)
                continue;
            }
        }

        Log::info('Bulk category sync completed', [
            'provider' => $provider,
            'total' => count($externalCategories),
            'success' => $successCount,
            'errors' => $errorCount,
        ]);

        return $syncedCategories;
    }
}

