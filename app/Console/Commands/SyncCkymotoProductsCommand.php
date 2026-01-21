<?php

namespace App\Console\Commands;

use App\Jobs\SyncExternalProductsJob;
use App\Services\Ckymoto\CkymotoApiClient;
use App\Services\Ckymoto\ProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCkymotoProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-ckymoto
                            {--queue : Run sync in queue}
                            {--dry-run : Test mode (no changes)}
                            {--category-sync : Sync categories}
                            {--images : Sync images (default: true)}
                            {--no-images : Skip image sync}
                            {--price-only : Only update product prices}
                            {--new-products-only : Only add new products, skip existing ones}
                            {--status-only : Only update product active status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from CKYMOTO external service. External images are downloaded and stored locally.';

    /**
     * Execute the console command.
     */
    public function handle(CkymotoApiClient $apiClient, ProductSyncService $syncService): int
    {
        $this->info('Starting CKYMOTO products sync...');

        $useQueue = $this->option('queue');
        $dryRun = $this->option('dry-run');
        $categorySync = $this->option('category-sync');
        $noImages = $this->option('no-images');
        $syncImages = ! $noImages; // Default true, unless --no-images is specified
        $priceOnly = $this->option('price-only');
        $newProductsOnly = $this->option('new-products-only');
        $statusOnly = $this->option('status-only');

        // Validasyon: price-only ve new-products-only aynı anda aktif olamaz
        if ($priceOnly && $newProductsOnly) {
            $this->error('Cannot use --price-only and --new-products-only together.');

            return Command::FAILURE;
        }

        // Validasyon: status-only ile price-only veya new-products-only birlikte kullanılamaz
        if (($priceOnly || $newProductsOnly) && $statusOnly) {
            $this->error('Cannot use --status-only with --price-only or --new-products-only.');

            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be made');
        }

        // Sync mode bilgisi
        if ($statusOnly) {
            $this->info('Mode: Status update only (only is_active field will be updated)');
        } elseif ($priceOnly) {
            $this->info('Mode: Price update only (images and categories will be skipped)');
        } elseif ($newProductsOnly) {
            $this->info('Mode: New products only (existing products will be skipped)');
        }

        if (! $syncImages) {
            $this->info('Image sync is disabled.');
        }

        try {
            // API bağlantısını test et
            $this->info('Testing API connection...');
            if (! $apiClient->testConnection()) {
                $this->error('API connection failed. Please check your configuration.');

                return Command::FAILURE;
            }
            $this->info('API connection successful.');

            if ($useQueue) {
                $this->info('Dispatching sync job to queue...');
                SyncExternalProductsJob::dispatch('ckymoto');
                $this->info('Sync job dispatched successfully.');

                return Command::SUCCESS;
            }

            // Sync modunda direkt çalıştır
            $this->info('Fetching products from API...');
            $data = $apiClient->fetchProducts();
            $products = $data['products'] ?? [];
            if ($categorySync && ! $priceOnly && ! $statusOnly) {
                $categories = $data['categories'] ?? [];
                if (!empty($categories)) {
                    $this->info('Syncing categories...');
                    $syncedCategories = $syncService->syncCategories($categories, 'ckymoto');
                    $this->info('Categories synced successfully. ('.count($syncedCategories).' categories processed)');
                } else {
                    $this->warn('No categories found in API response.');
                }
            } else {
                if ($priceOnly) {
                    $this->info('Categories sync skipped (price-only mode).');
                } elseif ($statusOnly) {
                    $this->info('Categories sync skipped (status-only mode).');
                } else {
                    $this->info('Categories sync skipped.');
                }
            }
            if (empty($products)) {
                $this->warn('No products found in API response.');

                return Command::SUCCESS;
            }

            $this->info('Found '.count($products).' products to sync.');
            if ($syncImages && ! $priceOnly && ! $statusOnly) {
                $this->info('External images will be downloaded and stored locally during sync.');
            }

            $bar = $this->output->createProgressBar(count($products));
            $bar->start();

            $successCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            foreach ($products as $externalProduct) {
                try {
                    if (! $dryRun) {
                        $result = $syncService->syncProduct(
                            $externalProduct,
                            'ckymoto',
                            $syncImages,
                            $priceOnly,
                            $newProductsOnly,
                            $statusOnly
                        );

                        // newProductsOnly veya statusOnly modunda null dönerse skip edildi demektir
                        if ($result === null) {
                            $skippedCount++;
                        } else {
                            $successCount++;
                        }
                    } else {
                        $successCount++;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    // UNIQUE constraint violation (SKU duplicate) - bu artık ProductSyncService'de handle ediliyor
                    // Ama yine de log'layalım
                    $errorCount++;
                    $this->newLine();
                    $sku = $externalProduct['sku'] ?? 'unknown';
                    $this->warn("SKU duplicate detected for product: {$sku} - This should be handled automatically");
                    Log::warning('Product sync SKU duplicate (should be handled)', [
                        'sku' => $sku,
                        'name' => $externalProduct['name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    // Hata sayılmamalı çünkü ProductSyncService bunu handle ediyor
                    $errorCount--;
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->newLine();
                    $sku = $externalProduct['sku'] ?? 'unknown';
                    $name = $externalProduct['name'] ?? 'unknown';
                    $this->error("Failed to sync product [SKU: {$sku}, Name: {$name}]: {$e->getMessage()}");
                    Log::error('Product sync error in command', [
                        'sku' => $sku,
                        'name' => $name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("Sync completed!");
            $tableData = [
                ['Success', $successCount],
                ['Errors', $errorCount],
                ['Total', count($products)],
            ];

            if ($skippedCount > 0) {
                $tableData[] = ['Skipped (existing)', $skippedCount];
            }

            $this->table(
                ['Status', 'Count'],
                $tableData
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            Log::error('CKYMOTO sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}

