<?php

namespace App\Jobs;

use App\Services\Ckymoto\CategorySyncService;
use App\Services\Ckymoto\CkymotoApiClient;
use App\Services\Ckymoto\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncExternalProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $provider;

    /**
     * Create a new job instance.
     */
    public function __construct(string $provider = 'ckymoto')
    {
        $this->provider = $provider;
    }

    /**
     * Execute the job.
     */
    public function handle(
        CkymotoApiClient $apiClient,
        ProductSyncService $productSyncService,
        CategorySyncService $categorySyncService
    ): void {
        Log::info('Starting external products sync', [
            'provider' => $this->provider,
        ]);

        try {
            // API'den veri çek
            $data = $apiClient->fetchProducts();
            $products = $data['products'] ?? [];
            $categories = $data['categories'] ?? [];

            // Önce kategorileri senkronize et
            if (! empty($categories)) {
                Log::info('Starting category sync', [
                    'provider' => $this->provider,
                    'total_categories' => count($categories),
                ]);

                try {
                    $categorySyncService->syncCategories($categories, $this->provider);
                } catch (\Exception $e) {
                    Log::error('Category sync failed, continuing with products', [
                        'provider' => $this->provider,
                        'error' => $e->getMessage(),
                    ]);
                    // Kategori hatası tüm sync'i durdurmasın
                }
            }

            // Sonra ürünleri senkronize et
            if (empty($products)) {
                Log::warning('No products found in API response', [
                    'provider' => $this->provider,
                ]);

                return;
            }

            $successCount = 0;
            $errorCount = 0;

            // Her ürünü senkronize et
            foreach ($products as $externalProduct) {
                try {
                    $productSyncService->syncProduct($externalProduct, $this->provider);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Failed to sync product', [
                        'provider' => $this->provider,
                        'sku' => $externalProduct['sku'] ?? 'unknown',
                        'uniqid' => $externalProduct['uniqid'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    // Hata durumunda devam et (bir ürün hatası tüm sync'i durdurmasın)
                    continue;
                }
            }

            Log::info('External products sync completed', [
                'provider' => $this->provider,
                'total' => count($products),
                'success' => $successCount,
                'errors' => $errorCount,
            ]);
        } catch (\Exception $e) {
            Log::error('External products sync failed', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Job'u fail et
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncExternalProductsJob failed permanently', [
            'provider' => $this->provider,
            'error' => $exception->getMessage(),
        ]);
    }
}

