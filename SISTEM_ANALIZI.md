# ZeonMoto Sistem Analizi ve Dokümantasyonu

## 📋 İçindekiler
1. [Sistem Amacı](#sistem-amacı)
2. [Genel Mimari](#genel-mimari)
3. [Veritabanı Şeması](#veritabanı-şeması)
4. [Model İlişkileri](#model-ilişkileri)
5. [Servisler ve İş Mantığı](#servisler-ve-iş-mantığı)
6. [Filament Admin Paneli](#filament-admin-paneli)
7. [API Entegrasyonları](#api-entegrasyonları)
8. [Job'lar ve Komutlar](#joblar-ve-komutlar)
9. [Güvenlik ve Proxy](#güvenlik-ve-proxy)
10. [Eksikler ve Geliştirme Önerileri](#eksikler-ve-geliştirme-önerileri)

---

## 🎯 Sistem Amacı

**ZeonMoto**, motosiklet parçaları için bir e-ticaret yönetim sistemidir. Sistemin temel amacı:

1. **Dış Kaynak Entegrasyonu**: CKYMOTO servisinden ürün verilerini otomatik senkronize etme
2. **Fiyat Yönetimi**: Esnek fiyat kuralı sistemi ile ürün fiyatlarını dinamik hesaplama
3. **Sipariş Yönetimi**: Müşteri siparişlerini takip etme ve yönetme
4. **Admin Paneli**: Filament v4 ile modern ve kullanıcı dostu yönetim arayüzü
5. **Görsel Yönetimi**: External ve custom görselleri güvenli şekilde proxy üzerinden servis etme

---

## 🏗️ Genel Mimari

### Teknoloji Stack
- **Framework**: Laravel 12.0
- **Admin Panel**: Filament v4.3.1+
- **PHP**: 8.2+
- **Database**: MySQL/PostgreSQL (Laravel destekli)
- **Queue**: Database Queue Driver
- **Testing**: Pest PHP

### Proje Yapısı

```
app/
├── Console/Commands/          # Artisan komutları
├── Filament/Resources/        # Filament admin panel kaynakları
├── Http/Controllers/          # HTTP controller'lar
├── Jobs/                      # Queue job'ları
├── Models/                    # Eloquent modelleri
├── Observers/                 # Model observer'ları
├── Services/                  # İş mantığı servisleri
│   ├── Ckymoto/              # CKYMOTO entegrasyon servisleri
│   ├── Order/                # Sipariş servisleri
│   └── Pricing/              # Fiyat hesaplama servisleri
└── Providers/                # Service provider'lar
```

---

## 🗄️ Veritabanı Şeması

### 1. **products** (Ürünler)
```sql
- id (PK)
- name (string)
- sku (string, unique)
- base_price (decimal 10,2)
- final_price (decimal 10,2, nullable)
- is_active (boolean, default: true)
- sort_order (integer, default: 0)
- timestamps
```

**İlişkiler:**
- `hasMany` ProductImage
- `hasMany` ProductExternal
- `hasMany` OrderItem
- `hasMany` CartItem
- `hasMany` PriceRule (scope: product)
- `belongsToMany` Category

### 2. **categories** (Kategoriler)
```sql
- id (PK)
- external_name (string, unique)
- display_name (string)
- slug (string, unique)
- is_active (boolean, default: true)
- sort_order (integer, default: 0)
- timestamps
```

**İlişkiler:**
- `belongsToMany` Product
- `hasMany` PriceRule (scope: category)

**Özellikler:**
- `external_name`: Dış kaynaktan gelen kategori adı (unique)
- `display_name`: Admin tarafından düzenlenebilir görünen ad
- Slug otomatik oluşturulur (display_name'den)

### 3. **category_product** (Pivot Tablo)
```sql
- product_id (FK → products.id, cascade delete)
- category_id (FK → categories.id, cascade delete)
- PRIMARY KEY (product_id, category_id)
```

### 4. **product_externals** (Dış Kaynak Eşleştirmeleri)
```sql
- id (PK)
- product_id (FK → products.id, cascade delete)
- provider_key (string) - 'ckymoto' gibi
- external_uniqid (string)
- external_hash (string, unique) - sha1("provider|uniqid")
- timestamps
```

**Amaç:** External kaynaklardan gelen ürünleri deterministik şekilde eşleştirmek

### 5. **product_images** (Ürün Görselleri)
```sql
- id (PK)
- product_id (FK → products.id, cascade delete)
- type (enum: 'custom', 'external')
- path (string, nullable) - custom görseller için
- external_url (text, nullable) - external görseller için
- is_primary (boolean, default: false)
- sort_order (integer, default: 0)
- timestamps
```

**Özellikler:**
- Custom görseller: `path` ile storage'da saklanır
- External görseller: `external_url` ile proxy üzerinden servis edilir
- `url` accessor: Custom için storage URL, external için proxy URL döner

### 6. **price_rules** (Fiyat Kuralları)
```sql
- id (PK)
- scope (string) - 'global', 'category', 'product'
- scope_id (unsignedBigInteger, nullable)
- type (string) - 'percentage', 'amount'
- value (decimal 10,2)
- priority (integer, default: 0)
- is_active (boolean, default: true)
- starts_at (datetime, nullable)
- ends_at (datetime, nullable)
- timestamps

INDEXES:
- (scope, scope_id)
- priority
- is_active
```

**Kapsam (Scope) Sistemi:**
- **Global**: Tüm ürünlere uygulanır (`scope_id = null`)
- **Category**: Belirli kategoriye ait ürünlere uygulanır
- **Product**: Belirli ürüne uygulanır

**Tip Sistemi:**
- **Percentage**: Yüzde bazlı artış/indirim (örn: +10%, -5%)
- **Amount**: Sabit tutar artış/indirim (örn: +50 TL, -20 TL)

**Öncelik Sistemi:**
- Düşük priority değeri = önce uygulanır
- Kurallar priority sırasına göre sıralı uygulanır

### 7. **customers** (Müşteriler)
```sql
- id (PK)
- first_name (string)
- last_name (string)
- phone (string, unique)
- city (string, nullable)
- district (string, nullable)
- address (text, nullable)
- note (text, nullable)
- timestamps
```

**İlişkiler:**
- `hasMany` Order

**Özellikler:**
- `full_name` accessor: first_name + last_name

### 8. **orders** (Siparişler)
```sql
- id (PK)
- order_no (string, unique) - 'ORD-20251213-0001' formatı
- customer_id (FK → customers.id, cascade delete)
- status (string) - OrderStatus enum: 'DRAFT', 'NEW', 'PREPARING', 'COMPLETED', 'CANCELLED'
- subtotal (decimal 10,2)
- total (decimal 10,2)
- currency (string, default: 'TRY')
- total_amount (decimal 10,2) - DEPRECATED, backward compatibility için
- total_discount (decimal 10,2) - DEPRECATED
- admin_status (enum) - DEPRECATED, status kullanılmalı
- timestamps
```

**Özellikler:**
- `order_no` otomatik oluşturulur: `ORD-YYYYMMDD-XXXX` formatı
- `status` default: `OrderStatus::New`
- `currency` default: 'TRY'
- `total_items` accessor: OrderItem'ların quantity toplamı

### 9. **order_items** (Sipariş Kalemleri)
```sql
- id (PK)
- order_id (FK → orders.id, cascade delete)
- product_id (FK → products.id, cascade delete)
- quantity (integer)
- unit_price (decimal 10,2) - DEPRECATED
- total_price (decimal 10,2) - DEPRECATED
- line_discount (decimal 10,2) - DEPRECATED

-- Snapshot Fields (RFC-005, immutable after creation):
- product_name_snapshot (string, nullable)
- sku_snapshot (string, nullable)
- unit_price_snapshot (decimal 10,2, nullable)
- line_total (decimal 10,2)
- price_rules_snapshot (json, nullable)
- timestamps
```

**Özellikler:**
- Snapshot alanları sipariş oluşturulduktan sonra değiştirilemez (updating event ile korunur)
- `price_rules_snapshot`: Uygulanan fiyat kurallarının JSON snapshot'ı
- Deprecated alanlar backward compatibility için korunur

### 10. **carts** (Sepetler)
```sql
- id (PK)
- session_key (string, unique)
- expires_at (datetime)
- timestamps

INDEXES:
- session_key
- expires_at
```

**İlişkiler:**
- `hasMany` CartItem

**Özellikler:**
- `isExpired()`: Sepet süresi dolmuş mu kontrolü
- `total_items` accessor: CartItem'ların quantity toplamı

### 11. **cart_items** (Sepet Kalemleri)
```sql
- id (PK)
- cart_id (FK → carts.id, cascade delete)
- product_id (FK → products.id, cascade delete)
- quantity (integer, default: 1)
- timestamps

UNIQUE: (cart_id, product_id)
```

**İlişkiler:**
- `belongsTo` Cart
- `belongsTo` Product

---

## 🔗 Model İlişkileri

### Product Model
```php
// İlişkiler
categories(): BelongsToMany
images(): HasMany (ordered by sort_order)
externals(): HasMany
orderItems(): HasMany
priceRules(): HasMany (scope: product)

// Metodlar
calculatePrice(?int $dealerId = null): PriceResult
getFinalPriceAttribute(): float (PriceEngine ile hesaplanır)
```

### Category Model
```php
// İlişkiler
products(): BelongsToMany

// Boot Events
- creating: Slug otomatik oluşturulur (display_name'den)
- updating: display_name değişirse slug güncellenir (eğer slug manuel değiştirilmemişse)
```

### PriceRule Model
```php
// İlişkiler
category(): BelongsTo (scope: category)
product(): BelongsTo (scope: product)

// Scopes
scopeIsActive(Builder): Aktif ve tarih aralığında olan kurallar
scopeForScope(Builder, PriceRuleScope, ?int): Scope'a göre filtreleme

// Metodlar
isApplicable(): bool - Kural şu an uygulanabilir mi?
```

### Order Model
```php
// İlişkiler
customer(): BelongsTo
orderItems(): HasMany

// Boot Events
- creating: order_no otomatik oluşturulur, status default: NEW, currency default: TRY

// Metodlar
generateOrderNumber(): string - Benzersiz sipariş numarası
getTotalItemsAttribute(): int
```

### OrderItem Model
```php
// İlişkiler
order(): BelongsTo
product(): BelongsTo

// Boot Events
- updating: Snapshot alanları korunur (değiştirilemez)

// Accessors/Mutators
getPriceRulesSnapshotAttribute(): array
setPriceRulesSnapshotAttribute($value): void
```

### ProductImage Model
```php
// İlişkiler
product(): BelongsTo

// Accessors
getProxyUrlAttribute(): string - route('image-proxy', ['image' => $id])
getUrlAttribute(): ?string - Custom için storage URL, external için proxy URL
```

---

## ⚙️ Servisler ve İş Mantığı

### 1. PriceEngine (Fiyat Hesaplama Motoru)

**Dosya:** `app/Services/Pricing/PriceEngine.php`

**Amaç:** Ürün fiyatlarını base_price'dan başlayarak aktif fiyat kurallarını uygulayarak hesaplar.

**Metodlar:**
- `calculate(Product $product, ?int $dealerId = null): PriceResult`
  - Ürün için final fiyatı hesaplar
  - Cache kullanır (5 dakika TTL)
  - Global → Category → Product sırasıyla kuralları uygular
  - Priority sırasına göre sıralar

- `getActiveRules(Product $product): Collection`
  - Global, Category ve Product kurallarını toplar
  - Aktif ve tarih aralığında olanları filtreler
  - Priority'ye göre sıralar

- `applyRule(float $price, PriceRule $rule): float`
  - Tek bir kuralı fiyata uygular
  - Percentage: `price + (price * value / 100)`
  - Amount: `price + value`

- `flushForProduct(int $productId): void` - Ürün cache'ini temizler
- `flushAll(): void` - Tüm cache'i temizler

**Cache Stratejisi:**
- Key: `price:{productId}:{dealerId|null}`
- TTL: 5 dakika
- PriceRule değişikliklerinde otomatik temizlenir (PriceRuleObserver)

### 2. PriceResult (Fiyat Hesaplama Sonucu)

**Dosya:** `app/Services/Pricing/PriceResult.php`

**Özellikler:**
- `base`: Base fiyat
- `final`: Final fiyat (kurallar uygulandıktan sonra)
- `appliedRules`: Uygulanan kuralların detaylı listesi

**Metodlar:**
- `getDifference(): float` - Final - Base farkı
- `toArray(): array` - Array'e dönüştürme (cache için)

### 3. CkymotoApiClient (CKYMOTO API İstemcisi)

**Dosya:** `app/Services/Ckymoto/CkymotoApiClient.php`

**Amaç:** CKYMOTO servisinden ürün ve kategori verilerini çeker.

**Metodlar:**
- `fetchProducts(): array` - API'den tüm ürünleri ve kategorileri çeker
  - Returns: `['products' => [], 'categories' => []]`
  - Cookie-based authentication
  - Timeout: 30 saniye (config'den)
  - Error handling ve logging

- `testConnection(): bool` - API bağlantısını test eder

**Config:**
```php
'ckymoto' => [
    'api_url' => env('CKYMOTO_API_URL'),
    'cookie' => env('CKYMOTO_COOKIE'),
    'timeout' => env('CKYMOTO_TIMEOUT', 30),
]
```

### 4. ProductSyncService (Ürün Senkronizasyon Servisi)

**Dosya:** `app/Services/Ckymoto/ProductSyncService.php`

**Amaç:** External ürün verilerini sistemdeki ürünlerle eşleştirir ve senkronize eder.

**Metodlar:**
- `syncProduct(array $externalProduct, string $provider = 'ckymoto'): Product`
  - External hash hesaplar: `sha1("{$provider}|{$externalProduct['uniqid']}")`
  - Mevcut ProductExternal kaydını arar
  - Varsa: `updateProduct()`, Yoksa: `createProduct()`

- `createProduct(array $externalProduct, string $provider): Product`
  - Yeni Product oluşturur (is_active = false, admin kontrolü)
  - ProductExternal kaydı oluşturur
  - External görselleri ProductImage olarak ekler
  - Transaction içinde çalışır

- `updateProduct(Product $product, array $externalProduct, string $provider): Product`
  - Sadece name, sku, base_price güncellenir
  - is_active, sort_order, kategori ilişkileri güncellenmez (admin kontrolü)
  - Yeni external görselleri ekler (mevcut external görselleri silmez)

- `syncProductImages(Product $product, array $imageUrls): void`
  - Mevcut external görselleri kontrol eder
  - Yeni görselleri ekler (duplicate kontrolü)

- `generateExternalHash(string $provider, string $uniqid): string`
  - Deterministik hash: `sha1("{$provider}|{$uniqid}")`

**Özellikler:**
- Idempotent: Aynı veri tekrar gelirse sonuç değişmez
- Transaction-based: Her ürün ayrı transaction
- Admin kontrolü: Yeni ürünler is_active=false ile oluşturulur

### 5. CategorySyncService (Kategori Senkronizasyon Servisi)

**Dosya:** `app/Services/Ckymoto/CategorySyncService.php`

**Metodlar:**
- `syncCategory(string $externalCategoryName, string $provider = 'ckymoto'): Category`
  - external_name ile mevcut kategoriyi arar
  - Varsa: Hiçbir şey yapmaz (mevcut display_name korunur)
  - Yoksa: Yeni kategori oluşturur (is_active = false)

- `syncCategories(array $externalCategories, string $provider): array`
  - Toplu kategori senkronizasyonu
  - Hata durumunda devam eder (bir kategori hatası tüm sync'i durdurmaz)

**Özellikler:**
- Mevcut kategoriler güncellenmez (display_name korunur)
- Yeni kategoriler is_active=false ile oluşturulur

### 6. OrderCreationService (Sipariş Oluşturma Servisi)

**Dosya:** `app/Services/Order/OrderCreationService.php`

**Metodlar:**
- `createFromCart(Cart $cart, array $customerData): Order`
  - Sepetten sipariş oluşturur
  - Customer'ı resolve eder veya oluşturur (phone ile)
  - Her cart item için PriceEngine ile fiyat hesaplar
  - OrderItem'lara snapshot alanlarını doldurur
  - Order toplamlarını hesaplar
  - Transaction içinde çalışır

- `resolveCustomer(array $customerData): Customer`
  - Phone ile mevcut müşteriyi arar
  - Varsa: Bilgileri günceller
  - Yoksa: Yeni müşteri oluşturur

**Özellikler:**
- Snapshot mantığı: Sipariş oluşturulduğunda ürün bilgileri snapshot'lanır
- PriceEngine entegrasyonu: Her ürün için güncel fiyat hesaplanır
- Price rules snapshot: Uygulanan kurallar JSON olarak saklanır

---

## 🎨 Filament Admin Paneli

### Resource'lar

1. **ProductResource** (`app/Filament/Resources/Products/`)
   - Form: ProductForm
   - Table: ProductsTable
   - Relations: CategoriesRelationManager, ProductImagesRelationManager
   - Pages: List, Create, View, Edit

2. **CategoryResource** (`app/Filament/Resources/Categories/`)
   - Kategori yönetimi
   - external_name ve display_name yönetimi

3. **PriceRuleResource** (`app/Filament/Resources/PriceRules/`)
   - Fiyat kuralı yönetimi
   - Scope, type, priority, tarih aralığı yönetimi

4. **CustomerResource** (`app/Filament/Resources/Customers/`)
   - Müşteri yönetimi

5. **OrderResource** (`app/Filament/Resources/Orders/`)
   - Sipariş yönetimi
   - Relations: OrderItemsRelationManager
   - Status yönetimi

6. **UserResource** (`app/Filament/Resources/Users/`)
   - Admin kullanıcı yönetimi

### Navigation Grupları
- **Ürünler**: Products, Categories, PriceRules
- **Siparişler**: Orders, Customers

---

## 🔌 API Entegrasyonları

### CKYMOTO API

**Endpoint:** `https://ckymotoservice.com/api/zeonmoto-motor/export` (POST)

**Authentication:** Cookie-based

**Response Format:**
```json
{
  "products": [
    {
      "uniqid": "unique-id",
      "name": "Ürün Adı",
      "sku": "SKU-001",
      "price": 1000.00,
      "category": "Kategori Adı",
      "images": ["url1", "url2"]
    }
  ],
  "categories": ["Kategori 1", "Kategori 2"]
}
```

**Senkronizasyon Akışı:**
1. API'den veri çekilir (CkymotoApiClient)
2. Kategoriler senkronize edilir (CategorySyncService)
3. Ürünler senkronize edilir (ProductSyncService)
4. External hash ile eşleştirme yapılır
5. Yeni ürünler is_active=false ile oluşturulur

---

## 🔄 Job'lar ve Komutlar

### 1. SyncExternalProductsJob

**Dosya:** `app/Jobs/SyncExternalProductsJob.php`

**Amaç:** Queue'da çalışan ürün senkronizasyon job'ı

**Akış:**
1. CkymotoApiClient ile veri çeker
2. CategorySyncService ile kategorileri senkronize eder
3. Her ürün için ProductSyncService::syncProduct() çağırır
4. Hata durumunda devam eder (bir ürün hatası tüm sync'i durdurmaz)
5. Logging ve error handling

**Kullanım:**
```php
SyncExternalProductsJob::dispatch('ckymoto');
```

### 2. SyncCkymotoProductsCommand

**Dosya:** `app/Console/Commands/SyncCkymotoProductsCommand.php`

**Komut:** `php artisan products:sync-ckymoto`

**Options:**
- `--queue`: Queue'ya atar
- `--dry-run`: Test modu (değişiklik yapmaz)

**Akış:**
1. API bağlantısını test eder
2. Queue modunda: Job dispatch eder
3. Sync modunda: Direkt senkronizasyon yapar
4. Progress bar gösterir
5. Sonuç raporu

---

## 🔒 Güvenlik ve Proxy

### ImageProxyController

**Dosya:** `app/Http/Controllers/ImageProxyController.php`

**Route:** `GET /image-proxy/{image}`

**Amaç:** External görselleri güvenli şekilde proxy üzerinden servis etme

**Özellikler:**
- **Whitelist Kontrolü**: Sadece izin verilen domain'lerden görsel çekilir
- **MIME Type Doğrulama**: Sadece geçerli image MIME type'ları kabul edilir
- **ETag Desteği**: 304 Not Modified desteği
- **Cache Control**: Config'den cache max age
- **Streaming**: Büyük görseller için stream desteği

**Config:**
```php
'image_proxy' => [
    'allowed_domains' => ['ckymotoservice.com', 'ckymotoservice.com.tr'],
    'cache_max_age' => 86400, // 24 saat
]
```

**Görsel Tipleri:**
- **Custom**: Storage'da saklanan görseller (path)
- **External**: Dış kaynaktan proxy üzerinden servis edilen görseller (external_url)

---

## 👁️ Observer'lar

### PriceRuleObserver

**Dosya:** `app/Observers/PriceRuleObserver.php`

**Amaç:** PriceRule değişikliklerinde cache'i otomatik temizler

**Events:**
- `created`: Global → flushAll(), Category → flushCategoryCache(), Product → flushForProduct()
- `updated`: Aynı mantık
- `deleted`: Aynı mantık
- `restored`: Aynı mantık
- `forceDeleted`: Aynı mantık

**Kayıt:** `AppServiceProvider::boot()` içinde

---

## 📊 Enum'lar

### OrderStatus
```php
Draft = 'DRAFT'
New = 'NEW'
Preparing = 'PREPARING'
Completed = 'COMPLETED'
Cancelled = 'CANCELLED'
```

### PriceRuleScope
```php
Global = 'global'
Category = 'category'
Product = 'product'
```

### PriceRuleType
```php
Percentage = 'percentage'
Amount = 'amount'
```

---

## ⚠️ Eksikler ve Geliştirme Önerileri

### 1. **Eksik Özellikler**

#### A. Frontend/API
- ❌ Public API endpoint'leri (müşteri tarafı için)
- ❌ Sepet API endpoint'leri
- ❌ Sipariş oluşturma API endpoint'i
- ❌ Ürün listeleme/filtreleme API'leri
- ❌ Authentication/Authorization sistemi (müşteri için)

#### B. Ödeme Entegrasyonu
- ❌ Ödeme gateway entegrasyonu
- ❌ Ödeme durumu takibi
- ❌ İade/İptal işlemleri

#### C. Bildirimler
- ❌ Email bildirimleri (sipariş onayı, durum değişikliği)
- ❌ SMS bildirimleri
- ❌ Admin bildirimleri

#### D. Raporlama
- ❌ Satış raporları
- ❌ Ürün performans raporları
- ❌ Müşteri analitikleri
- ❌ Dashboard widget'ları

#### E. Stok Yönetimi
- ❌ Stok takibi
- ❌ Stok uyarıları
- ❌ Otomatik stok güncelleme

#### F. Kargo Entegrasyonu
- ❌ Kargo firması entegrasyonu
- ❌ Kargo takip numarası yönetimi
- ❌ Kargo maliyeti hesaplama

### 2. **Geliştirme Önerileri**

#### A. Performans
- ✅ Cache mekanizması mevcut (PriceEngine)
- ⚠️ Product listesi için cache eklenebilir
- ⚠️ Image proxy için cache layer eklenebilir
- ⚠️ Database index'leri optimize edilebilir

#### B. Güvenlik
- ✅ Image proxy whitelist mevcut
- ⚠️ Rate limiting eklenebilir
- ⚠️ CSRF koruması kontrol edilmeli
- ⚠️ XSS koruması kontrol edilmeli
- ⚠️ SQL injection koruması (Eloquent kullanılıyor, güvenli)

#### C. Test Coverage
- ✅ PriceEngine testleri mevcut
- ⚠️ Diğer servisler için testler eklenebilir
- ⚠️ Integration testleri eklenebilir
- ⚠️ Feature testleri eklenebilir

#### D. Dokümantasyon
- ⚠️ API dokümantasyonu (Swagger/OpenAPI)
- ⚠️ Kod içi dokümantasyon artırılabilir
- ⚠️ Deployment dokümantasyonu

#### E. Monitoring
- ⚠️ Error tracking (Sentry, Bugsnag)
- ⚠️ Performance monitoring
- ⚠️ Log aggregation
- ⚠️ Health check endpoint'leri

#### F. Code Quality
- ⚠️ PHPStan/Psalm eklenebilir
- ⚠️ Code coverage raporları
- ⚠️ CI/CD pipeline

### 3. **Mevcut Deprecated Alanlar**

Aşağıdaki alanlar backward compatibility için korunuyor ancak kullanılmamalı:

**Orders:**
- `total_amount` → `total` kullanılmalı
- `admin_status` → `status` kullanılmalı
- `total_discount` → Kaldırılabilir (şu an kullanılmıyor)

**OrderItems:**
- `unit_price` → `unit_price_snapshot` kullanılmalı
- `total_price` → `line_total` kullanılmalı
- `line_discount` → Kaldırılabilir (şu an kullanılmıyor)

### 4. **Potansiyel İyileştirmeler**

#### A. Fiyat Motoru
- ⚠️ Dealer bazlı fiyatlandırma (şu an dealerId parametresi var ama kullanılmıyor)
- ⚠️ Minimum/maksimum fiyat kontrolü
- ⚠️ Fiyat geçmişi (audit log)

#### B. Ürün Senkronizasyonu
- ⚠️ Incremental sync (sadece değişen ürünleri çekme)
- ⚠️ Conflict resolution (admin tarafından manuel müdahale)
- ⚠️ Sync history/audit log

#### C. Sipariş Yönetimi
- ⚠️ Sipariş durumu workflow'u
- ⚠️ Otomatik durum geçişleri
- ⚠️ Sipariş iptal/geri alma mekanizması

#### D. Kategori Yönetimi
- ⚠️ Hiyerarşik kategori yapısı (parent-child)
- ⚠️ Kategori görselleri
- ⚠️ Kategori açıklamaları

---

## 📝 Önemli Notlar

1. **Admin Kontrolü**: External kaynaklardan gelen ürünler ve kategoriler `is_active=false` ile oluşturulur. Admin tarafından aktif edilmelidir.

2. **Snapshot Mantığı**: Sipariş oluşturulduğunda ürün bilgileri snapshot'lanır. Bu sayede ürün bilgileri değişse bile sipariş bilgileri korunur.

3. **Fiyat Hesaplama**: Fiyatlar her zaman PriceEngine üzerinden hesaplanır. `final_price` alanı cache olarak kullanılabilir ama güvenilir kaynak PriceEngine'dir.

4. **External Hash**: Ürün eşleştirmesi için deterministik hash kullanılır: `sha1("provider|uniqid")`. Bu sayede aynı external ürün her zaman aynı sistem ürününe eşleşir.

5. **Transaction Güvenliği**: Kritik işlemler (sipariş oluşturma, ürün senkronizasyonu) transaction içinde çalışır.

6. **Error Handling**: Hata durumlarında sistem çalışmaya devam eder (bir ürün hatası tüm sync'i durdurmaz).

---

## 🔄 Sistem Akış Şemaları

### Ürün Senkronizasyon Akışı
```
1. Artisan Command veya Job tetiklenir
2. CkymotoApiClient → API'den veri çeker
3. CategorySyncService → Kategorileri senkronize eder
4. Her ürün için:
   a. External hash hesaplanır
   b. ProductExternal tablosunda aranır
   c. Varsa: updateProduct()
   d. Yoksa: createProduct()
   e. Görseller senkronize edilir
5. Logging ve hata raporlama
```

### Fiyat Hesaplama Akışı
```
1. Product::calculatePrice() veya getFinalPriceAttribute() çağrılır
2. PriceEngine::calculate() çağrılır
3. Cache kontrolü (varsa döner)
4. Base price alınır
5. Aktif kurallar toplanır (Global → Category → Product)
6. Priority'ye göre sıralanır
7. Her kural uygulanır (percentage veya amount)
8. Sonuç cache'lenir
9. PriceResult döner
```

### Sipariş Oluşturma Akışı
```
1. OrderCreationService::createFromCart() çağrılır
2. Customer resolve edilir/oluşturulur
3. Order oluşturulur (order_no otomatik)
4. Her cart item için:
   a. PriceEngine ile fiyat hesaplanır
   b. OrderItem oluşturulur (snapshot alanları doldurulur)
   c. Subtotal hesaplanır
5. Order toplamları güncellenir
6. Transaction commit
```

---

## 📚 Referanslar

- **Laravel**: https://laravel.com/docs/12.x
- **Filament v4**: https://filamentphp.com/docs/4.x
- **Pest PHP**: https://pestphp.com/docs

---

**Son Güncelleme:** 2025-12-13
**Versiyon:** 1.0.0
