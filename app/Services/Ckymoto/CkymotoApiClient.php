<?php

namespace App\Services\Ckymoto;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CkymotoApiClient
{
    protected string $apiUrl;
    protected ?string $cookie;
    protected int $timeout;

    public function __construct()
    {
        $config = config('services.ckymoto', []);
        $this->apiUrl = $config['api_url'] ?? 'https://ckymotoservice.com/api/zeonmoto-motor/export';
        $this->cookie = $config['cookie'] ?? null;
        $this->timeout = $config['timeout'] ?? 30;
    }

    /**
     * CKYMOTO API'den tüm ürünleri çeker
     *
     * @return array{products: array, categories: array}
     * @throws \Exception
     */
    public function fetchProducts(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($this->apiUrl);

            if (! $response->successful()) {
                Log::error('CKYMOTO API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("CKYMOTO API request failed with status: {$response->status()}");
            }

            $data = $response->json();

            if (! isset($data['products']) || ! is_array($data['products'])) {
                throw new \Exception('Invalid API response: products array not found');
            }

            return [
                'products' => $data['products'] ?? [],
                'categories' => $data['categories'] ?? [],
            ];
        } catch (RequestException $e) {
            Log::error('CKYMOTO API connection error', [
                'message' => $e->getMessage(),
            ]);

            throw new \Exception("CKYMOTO API connection failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * HTTP headers hazırlar
     *
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->cookie) {
            $headers['Cookie'] = $this->cookie;
        }

        return $headers;
    }

    /**
     * API bağlantısını test eder
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $this->fetchProducts();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}




