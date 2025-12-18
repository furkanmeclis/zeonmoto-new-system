---
name: Ürün Senkronizasyonu ve External Mapping
overview: CKYMOTO servisinden gelen ürün verilerini deterministik, güvenli ve idempotent şekilde senkronize eden sistem kurulumu. External hash mantığı ile ürün eşleştirmesi, queue tabanlı işleme ve admin kontrollü ürün yönetimi sağlanacak.
todos:
  - id: create-api-client
    content: CkymotoApiClient servisi oluştur - HTTP client, authentication, response parsing
    status: completed
  - id: create-sync-service
    content: ProductSyncService oluştur - hash hesaplama, create/update mantığı, görsel senkronizasyonu
    status: completed
  - id: create-queue-job
    content: SyncExternalProductsJob oluştur - queue tabanlı işleme, error handling
    status: completed
    dependencies:
      - create-api-client
      - create-sync-service
  - id: create-command
    content: SyncCkymotoProductsCommand oluştur - artisan command, progress tracking
    status: completed
    dependencies:
      - create-api-client
      - create-sync-service
  - id: add-config
    content: config/services.php'ye ckymoto konfigürasyonu ekle
    status: completed
  - id: add-route
    content: ImageProxyController route'unu kontrol et (zaten mevcut olabilir)
    status: completed
---

# RFC-001 Ürün Senkronizasyonu ve External Mapping Implementasyonu

## Genel Mimari

Sistem üç ana katmandan oluşur:

1. **API Client**: CKYMOTO servisinden veri çekme
2. **Sync Service**: Ürün eşleştirme ve güncelleme mantığı
3. **Queue Jobs**: Asenkron işleme

## Dosya Yapısı

```
app/
├── Services/
│   └── Ckymoto/
│       ├── CkymotoApiClient.php      # API client
│       └── ProductSyncService.php    # Sync mantığı
├── Jobs/
│   └── SyncExternalProductsJob.php   # Queue job
└── Console/
    └── Commands/
        └── SyncCkymotoProductsCommand.php  # Artisan command
```

## 1. API Client Servisi

**Dosya**: `app/Services/Ckymoto/CkymotoApiClient.php`

- CKYMOTO API endpoint'ine HTTP istekleri
- Cookie-based authentication desteği
- Response validation ve error handling
- JSON response parsing
- Timeout ve retry mekanizması

**Özellikler**:

- `fetchProducts()`: Tüm ürünleri çeker
- `fetchCategories()`: Kategorileri çeker (opsiyonel)
- Config'den endpoint ve auth bilgileri okur

## 2. Product Sync Service

**Dosya**: `app/Services/Ckymoto/ProductSyncService.php`

**Core Metodlar**:

### `syncProduct(array $externalProduct, string $provider = 'ckymoto')`

- External hash hesaplama: `sha1("{$provider}|{$externalProduct['uniqid']}")`
- Mevcut `ProductExternal` kaydını hash ile bulma
- Varsa: `updateProduct()` çağırır
- Yoksa: `createProduct()` çağırır

### `createProduct(array $externalProduct, string $provider)`

- Yeni `Product` oluşturur:
  - `name` = external.name
  - `sku` = external.sku
  - `base_price` = external.price
  - `is_active` = **false** (admin kontrolü)
  - `sort_order` = son sıra + 1
- `ProductExternal` kaydı oluşturur:
  - `provider_key` = provider
  - `external_uniqid` = external.uniqid
  - `external_hash` = hesaplanan hash
- External görselleri `ProductImage` olarak kaydeder:
  - `type` = 'external'
  - `external_url` = image URL
  - `is_primary` = ilk görsel için true
  - `sort_order` = sıra

### `updateProduct(Product $product, array $externalProduct)`

- **Sadece şu alanları günceller**:
  - `name`
  - `sku`
  - `base_price`
- **Güncellemez**:
  - `is_active` (admin kontrolünde)
  - `sort_order`
  - Kategori ilişkileri
- External görselleri kontrol eder, yeni olanları ekler (mevcut external görselleri silmez)

### `generateExternalHash(string $provider, string $uniqid): string`

- `sha1("{$provider}|{$uniqid}")` döndürür

## 3. Queue Job

**Dosya**: `app/Jobs/SyncExternalProductsJob.php`

- `CkymotoApiClient` ile veri çeker
- Her ürün için `ProductSyncService::syncProduct()` çağırır
- Batch processing (her ürün ayrı job olabilir veya toplu işlenebilir)
- Error handling ve logging
- Transaction kullanımı (her ürün için ayrı transaction)

**Özellikler**:

- Failed job handling
- Progress tracking (opsiyonel)
- Retry mekanizması

## 4. Artisan Command

**Dosya**: `app/Console/Commands/SyncCkymotoProductsCommand.php`

- `php artisan products:sync-ckymoto` komutu
- API'den veri çeker
- Sync işlemini başlatır (sync veya queue modunda)
- Progress bar gösterir
- Hata raporlama

**Options**:

- `--queue`: Queue'ya atar (default: sync)
- `--dry-run`: Test modu (değişiklik yapmaz)

## 5. Configuration

**Dosya**: `config/services.php` (ekleme)

```php
'ckymoto' => [
    'api_url' => env('CKYMOTO_API_URL', 'https://ckymotoservice.com/api/moto-gpt-motor/export'),
    'cookie' => env('CKYMOTO_COOKIE'),
    'timeout' => env('CKYMOTO_TIMEOUT', 30),
],
```

## 6. Database Transaction ve Idempotency

- Her ürün sync işlemi transaction içinde
- `external_hash` UNIQUE constraint ile korunur
- Duplicate hash durumunda exception fırlatılır
- Aynı veri tekrar gelirse sonuç değişmez (idempotent)

## 7. Error Handling

- API connection hataları: Log + retry
- Invalid data: Log + skip (ürün atlanır)
- Duplicate hash: Log + skip (zaten mevcut)
- Database errors: Transaction rollback + log

## 8. Logging

- Sync başlangıç/bitiş
- Her ürün için create/update işlemleri
- Hata durumları
- Performance metrikleri (opsiyonel)

## 9. Kategori Eşleştirmesi (Basit Yaklaşım)

RFC-002 kapsamında olmasına rağmen, temel eşleştirme:

- External category name ile `Category::where('external_name', $categoryName)` eşleştirme
- Bulunamazsa kategori oluşturulmaz (sadece log)
- Kategori ilişkileri admin tarafından manuel yönetilir

## 10. Test Senaryoları

- Yeni ürün oluşturma
- Mevcut ürün güncelleme
- Duplicate hash durumu
- Invalid data handling
- API connection failure
- Transaction rollback

## Notlar

- Görsel proxy mekanizması zaten mevcut (`ImageProxyController`)
- Queue driver: database (mevcut config)
- Kategori eşleştirmesi detayları RFC-002'de ele alınacak
- API authentication cookie-based (kullanıcı örneğinde mevcut)