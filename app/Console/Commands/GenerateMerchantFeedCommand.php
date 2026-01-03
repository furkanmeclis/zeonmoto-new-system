<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class GenerateMerchantFeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merchant:generate-feed {--format=xml : Output format (tsv or xml)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Google Merchant Center product feed file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        
        if (!in_array($format, ['tsv', 'xml'])) {
            $this->error('Format must be either "tsv" or "xml"');
            return Command::FAILURE;
        }

        $this->info("Generating Google Merchant Center feed ({$format} format)...");

        $baseUrl = config('app.url');
        $products = Product::where('is_active', true)
            ->with('images')
            ->orderBy('updated_at', 'desc')
            ->get();

        $this->info("Found {$products->count()} active products");

        // Filtrele: retail_price > 0 olan ürünleri al
        $validProducts = $products->filter(function ($product) {
            $retailPrice = (float) ($product->retail_price ?? 0);
            return $retailPrice > 0;
        });

        $this->info("Products with valid retail price (> 0): {$validProducts->count()}");

        if ($validProducts->isEmpty()) {
            $this->warn('No products found with retail price > 0');
            return Command::FAILURE;
        }

        // Dosya içeriğini oluştur
        if ($format === 'tsv') {
            $content = $this->generateTsv($validProducts, $baseUrl);
            $filePath = public_path('merchant-feed.tsv');
            $fileName = 'merchant-feed.tsv';
        } else {
            $content = $this->generateXml($validProducts, $baseUrl);
            $filePath = public_path('merchant-feed.xml');
            $fileName = 'merchant-feed.xml';
        }

        // Public dizinine yaz
        File::put($filePath, $content);

        $this->info("Merchant feed generated successfully at: {$filePath}");
        $this->info("Total products: " . $validProducts->count());
        $this->info("Format: {$format}");
        $this->info("File size: " . $this->formatBytes(filesize($filePath)));

        return Command::SUCCESS;
    }

    /**
     * Generate TSV (Tab-Separated Values) content for Merchant Center.
     */
    private function generateTsv($products, string $baseUrl): string
    {
        // TSV Header
        $lines = [
            implode("\t", [
                'id',
                'title',
                'description',
                'price',
                'condition',
                'link',
                'availability',
                'image_link',
            ])
        ];

        foreach ($products as $product) {
            $retailPrice = (float) $product->retail_price;
            
            // ID: SKU-ID kombinasyonu
            $productId = $product->sku . '-' . $product->id;
            
            // Title: Ürün adı
            $title = $product->name;
            
            // Description: Formatlanmış açıklama veya varsayılan
            $description = $product->formatted_description ?? $product->description ?? $product->name;
            
            // Price: "449.00 TRY" formatı
            $price = number_format($retailPrice, 2, '.', '') . ' TRY';
            
            // Condition: Her zaman "yeni" (new)
            $condition = 'yeni';
            
            // Link: Ürün URL'i
            $link = $baseUrl . '/products/' . $product->sku . '-' . $product->id;
            
            // Availability: Stok durumu
            $availability = $product->is_active ? 'in stock' : 'out of stock';
            
            // Image Link: Varsayılan görsel URL'i
            $imageLink = $product->default_image_url ?? asset('images/placeholder.png');
            
            // Tam URL'e dönüştür (relative ise)
            if (!filter_var($imageLink, FILTER_VALIDATE_URL)) {
                $imageLink = $baseUrl . '/' . ltrim($imageLink, '/');
            }

            // TSV satırını oluştur (tab ile ayrılmış)
            $line = implode("\t", [
                $this->escapeTsv($productId),
                $this->escapeTsv($title),
                $this->escapeTsv($description),
                $this->escapeTsv($price),
                $this->escapeTsv($condition),
                $this->escapeTsv($link),
                $this->escapeTsv($availability),
                $this->escapeTsv($imageLink),
            ]);

            $lines[] = $line;
        }

        // UTF-8 BOM ekle (Google Merchant Center için önerilir)
        return "\xEF\xBB\xBF" . implode("\n", $lines);
    }

    /**
     * Generate XML content for Merchant Center.
     */
    private function generateXml($products, string $baseUrl): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . htmlspecialchars(config('app.name', 'Products'), ENT_XML1, 'UTF-8') . '</title>' . "\n";
        $xml .= '    <link>' . htmlspecialchars($baseUrl, ENT_XML1, 'UTF-8') . '</link>' . "\n";
        $xml .= '    <description>Google Merchant Center Product Feed</description>' . "\n";

        foreach ($products as $product) {
            $retailPrice = (float) $product->retail_price;
            
            $productId = $product->sku . '-' . $product->id;
            $title = $product->name;
            $description = $product->formatted_description ?? $product->description ?? $product->name;
            $price = number_format($retailPrice, 2, '.', '') . ' TRY';
            $link = $baseUrl . '/products/' . $product->sku . '-' . $product->id;
            $availability = $product->is_active ? 'in stock' : 'out of stock';
            $imageLink = $product->default_image_url ?? asset('images/placeholder.png');
            
            if (!filter_var($imageLink, FILTER_VALIDATE_URL)) {
                $imageLink = $baseUrl . '/' . ltrim($imageLink, '/');
            }

            $xml .= '    <item>' . "\n";
            $xml .= '      <g:id>' . htmlspecialchars($productId, ENT_XML1, 'UTF-8') . '</g:id>' . "\n";
            $xml .= '      <g:title>' . htmlspecialchars($title, ENT_XML1, 'UTF-8') . '</g:title>' . "\n";
            $xml .= '      <g:description>' . htmlspecialchars($description, ENT_XML1, 'UTF-8') . '</g:description>' . "\n";
            $xml .= '      <g:price>' . htmlspecialchars($price, ENT_XML1, 'UTF-8') . '</g:price>' . "\n";
            $xml .= '      <g:condition>new</g:condition>' . "\n";
            $xml .= '      <g:link>' . htmlspecialchars($link, ENT_XML1, 'UTF-8') . '</g:link>' . "\n";
            $xml .= '      <g:availability>' . htmlspecialchars($availability, ENT_XML1, 'UTF-8') . '</g:availability>' . "\n";
            $xml .= '      <g:image_link>' . htmlspecialchars($imageLink, ENT_XML1, 'UTF-8') . '</g:image_link>' . "\n";
            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Escape TSV special characters.
     */
    private function escapeTsv(string $value): string
    {
        // Tab, newline ve carriage return karakterlerini temizle
        $value = str_replace(["\t", "\n", "\r"], [' ', ' ', ''], $value);
        
        // Çift tırnakları escape et
        $value = str_replace('"', '""', $value);
        
        // Tab, newline veya çift tırnak içeriyorsa tırnak içine al
        if (strpos($value, "\t") !== false || strpos($value, "\n") !== false || strpos($value, '"') !== false) {
            $value = '"' . $value . '"';
        }
        
        return $value;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

