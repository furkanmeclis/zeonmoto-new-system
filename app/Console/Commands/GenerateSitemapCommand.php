<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap.xml file for search engines';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating sitemap.xml...');

        $baseUrl = config('app.url');
        $urls = [];

        // Ana sayfa - en yüksek öncelik, günlük güncelleme
        $urls[] = [
            'loc' => $baseUrl,
            'lastmod' => Carbon::now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Shop sayfası - yüksek öncelik, günlük güncelleme
        $urls[] = [
            'loc' => $baseUrl . '/shop',
            'lastmod' => Carbon::now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ];

        // Aktif ürünler
        $products = Product::where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        $this->info("Found {$products->count()} active products");

        foreach ($products as $product) {
            $sku = $product->sku;
            $id = $product->id;
            $productUrl = $baseUrl . '/products/' . $sku . '-' . $id;
            $lastmod = $product->updated_at 
                ? Carbon::parse($product->updated_at)->toIso8601String()
                : Carbon::now()->toIso8601String();

            $urls[] = [
                'loc' => $productUrl,
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // XML içeriğini oluştur
        $xml = $this->generateXml($urls);

        // Public dizinine yaz
        $filePath = public_path('sitemap.xml');
        File::put($filePath, $xml);

        $this->info("Sitemap generated successfully at: {$filePath}");
        $this->info("Total URLs: " . count($urls));
        $this->info("Products: " . $products->count());

        return Command::SUCCESS;
    }

    /**
     * Generate XML content for sitemap.
     */
    private function generateXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            
            if (isset($url['lastmod'])) {
                $xml .= "    <lastmod>" . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            }
            
            if (isset($url['changefreq'])) {
                $xml .= "    <changefreq>" . htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            }
            
            if (isset($url['priority'])) {
                $xml .= "    <priority>" . htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
            }
            
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
