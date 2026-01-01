<?php

namespace App\Filament\Pages;

use App\Services\Ckymoto\CkymotoApiClient;
use App\Services\Ckymoto\ProductSyncService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class SyncCkymotoProducts extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

    protected string $view = 'filament.pages.sync-ckymoto-products';

    protected static ?string $navigationLabel = 'Ürün Senkronizasyonu';

    protected static ?string $title = 'Ürün Senkronizasyonu';

    protected static string | UnitEnum | null $navigationGroup = 'Senkronizasyon';

    protected static ?int $navigationSort = 1;

    public ?array $data = [
        'use_queue' => false,
        'dry_run' => false,
        'category_sync' => true,
        'sync_images' => true,
        'price_only' => false,
        'new_products_only' => false,
    ];

    public string $syncStatus = '';

    public array $syncResults = [];

    public function content(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Senkronizasyon Ayarları')
                    ->description('CKYMOTO API\'den ürün ve kategori senkronizasyonu yapmak için ayarları yapın. External görseller otomatik olarak indirilip storage\'a kaydedilir.')
                    ->schema([
                        Checkbox::make('sync_images')
                            ->label('Resimler Dahil Edilsin')
                            ->helperText('External görseller indirilip storage\'a kaydedilir.')
                            ->default(true)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('price_only', false);
                                }
                            }),

                        Checkbox::make('price_only')
                            ->label('Sadece Fiyat Güncellemesi')
                            ->helperText('Sadece ürün fiyatları güncellenir. Resimler ve kategoriler sync edilmez.')
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('sync_images', false);
                                    $set('new_products_only', false);
                                    $set('category_sync', false);
                                }
                            }),

                        Checkbox::make('new_products_only')
                            ->label('Sadece Yeni Ürünler Eklensin')
                            ->helperText('Mevcut ürünler atlanır, sadece yeni ürünler eklenir. Resimler ve kategoriler otomatik dahil edilir.')
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('sync_images', true);
                                    $set('category_sync', true);
                                    $set('price_only', false);
                                }
                            }),

                        Checkbox::make('category_sync')
                            ->label('Kategorileri de Senkronize Et')
                            ->helperText('Ürünlerle birlikte kategorileri de senkronize eder.')
                            ->default(true)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('price_only', false);
                                }
                            }),

                        Checkbox::make('use_queue')
                            ->label('Kuyrukta Çalıştır(Desteklenmiyor)')
                            ->helperText('Sync işlemi arka planda kuyrukta çalışacak. Büyük veri setleri için önerilir.')
                            ->default(false)
                            ->disabled(true),

                        Checkbox::make('dry_run')
                            ->label('Test Modu (Dry Run)')
                            ->helperText('Değişiklik yapmadan test etmek için aktif edin. Veritabanına kayıt yapılmaz.')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Senkronizasyonu Başlat')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Senkronizasyonu Başlat')
                ->modalDescription('CKYMOTO ürün senkronizasyonunu başlatmak istediğinizden emin misiniz?')
                ->modalSubmitActionLabel('Evet, Başlat')
                ->action('startSync'),

            Action::make('testConnection')
                ->label('API Bağlantısını Test Et')
                ->icon('heroicon-o-wifi')
                ->color('gray')
                ->action('testConnection'),
        ];
    }

    public function testConnection(): void
    {
        try {
            $apiClient = app(CkymotoApiClient::class);

            if ($apiClient->testConnection()) {
                Notification::make()
                    ->title('Bağlantı Başarılı')
                    ->body('CKYMOTO API bağlantısı başarıyla test edildi.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Bağlantı Başarısız')
                    ->body('CKYMOTO API bağlantısı başarısız. Lütfen yapılandırmanızı kontrol edin.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Bağlantı testi sırasında bir hata oluştu: '.$e->getMessage())
                ->danger()
                ->send();

            Log::error('CKYMOTO API connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function startSync(): void
    {
        $data = $this->data;

        $useQueue = $data['use_queue'] ?? false;
        $dryRun = $data['dry_run'] ?? false;
        $categorySync = $data['category_sync'] ?? false;
        $syncImages = $data['sync_images'] ?? true;
        $priceOnly = $data['price_only'] ?? false;
        $newProductsOnly = $data['new_products_only'] ?? false;

        // Validasyon: price_only ve new_products_only aynı anda aktif olamaz
        if ($priceOnly && $newProductsOnly) {
            Notification::make()
                ->title('Geçersiz Seçim')
                ->body('"Sadece Fiyat Güncellemesi" ve "Sadece Yeni Ürünler Eklensin" aynı anda seçilemez.')
                ->danger()
                ->send();

            return;
        }

        try {
            $this->syncStatus = 'running';

            if ($useQueue) {
                // Kuyruk modunda job dispatch et
                \App\Jobs\SyncExternalProductsJob::dispatch(
                    'ckymoto',
                    $syncImages,
                    $priceOnly,
                    $newProductsOnly,
                    $categorySync
                );

                Notification::make()
                    ->title('Senkronizasyon Başlatıldı')
                    ->body('Senkronizasyon işlemi kuyruğa eklendi ve arka planda çalışacak.')
                    ->success()
                    ->send();

                $this->syncStatus = 'queued';
                $this->syncResults = [
                    'status' => 'queued',
                    'message' => 'İşlem kuyruğa eklendi.',
                ];
            } else {
                // Direkt çalıştır
                $this->syncStatus = 'running';

                // Artisan command'ı çalıştır
                $command = 'products:sync-ckymoto';

                $options = [];
                if ($dryRun) {
                    $options['--dry-run'] = true;
                }
                if ($categorySync) {
                    $options['--category-sync'] = true;
                }
                if ($syncImages) {
                    $options['--images'] = true;
                } else {
                    $options['--no-images'] = true;
                }
                if ($priceOnly) {
                    $options['--price-only'] = true;
                }
                if ($newProductsOnly) {
                    $options['--new-products-only'] = true;
                }

                // Command çıktısını yakalamak için
                $exitCode = Artisan::call($command, $options);
                $output = Artisan::output();

                if ($exitCode === 0) {
                    Notification::make()
                        ->title('Senkronizasyon Tamamlandı')
                        ->body('Ürün senkronizasyonu başarıyla tamamlandı.')
                        ->success()
                        ->send();

                    $this->syncStatus = 'completed';
                    $this->syncResults = [
                        'status' => 'success',
                        'message' => 'Senkronizasyon başarıyla tamamlandı.',
                        'output' => $output,
                    ];
                } else {
                    Notification::make()
                        ->title('Senkronizasyon Başarısız')
                        ->body('Ürün senkronizasyonu sırasında hata oluştu.')
                        ->danger()
                        ->send();

                    $this->syncStatus = 'failed';
                    $this->syncResults = [
                        'status' => 'error',
                        'message' => 'Senkronizasyon sırasında hata oluştu.',
                        'output' => $output,
                    ];
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Senkronizasyon sırasında bir hata oluştu: '.$e->getMessage())
                ->danger()
                ->send();

            $this->syncStatus = 'failed';
            $this->syncResults = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];

            Log::error('CKYMOTO sync failed from Filament page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
        }
    }

    public static function getMaxWidth(): MaxWidth
    {
        return MaxWidth::SixXL;
    }

    /**
     * Cron komutunu oluşturur
     */
    public function getCronCommand(string $mode = 'default'): string
    {
        $basePath = base_path();
        $phpPath = PHP_BINARY ?: 'php';

        // PHP path'i düzenle (mutlak path olmalı)
        if (! str_starts_with($phpPath, '/')) {
            // Relative path ise, which komutu ile bulmaya çalış
            $phpPath = 'php'; // Fallback olarak sadece php
        }

        $command = 'products:sync-ckymoto';
        $options = '--category-sync';

        // Mode'a göre seçenekleri ekle
        switch ($mode) {
            case 'price-only':
                $options .= ' --price-only';
                break;
            case 'new-products-only':
                $options .= ' --new-products-only';
                break;
            case 'no-images':
                $options .= ' --no-images';
                break;
            case 'images':
                $options .= ' --images';
                break;
            default:
                // Default: category-sync ve images (default true)
                break;
        }

        // Cron formatı: Her gün saat 02:00
        return "0 2 * * * cd {$basePath} && {$phpPath} artisan {$command} {$options} >> /dev/null 2>&1";
    }

    /**
     * Tüm cron komut örneklerini döndürür
     */
    public function getCronExamples(): array
    {
        return [
            'default' => [
                'label' => 'Varsayılan (Resimler ve Kategoriler Dahil)',
                'command' => $this->getCronCommand('default'),
            ],
            'price-only' => [
                'label' => 'Sadece Fiyat Güncellemesi',
                'command' => $this->getCronCommand('price-only'),
            ],
            'new-products-only' => [
                'label' => 'Sadece Yeni Ürünler',
                'command' => $this->getCronCommand('new-products-only'),
            ],
            'no-images' => [
                'label' => 'Resimler Olmadan',
                'command' => $this->getCronCommand('no-images'),
            ],
        ];
    }
}

