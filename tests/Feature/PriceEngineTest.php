<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Pricing\PriceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected PriceEngine $priceEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceEngine = app(PriceEngine::class);
    }

    public function test_calculates_price_with_base_price(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(100.00, $result->final);
    }

    public function test_uses_custom_price_when_available(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-002',
            'base_price' => 100.00,
            'custom_price' => 150.00,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(150.00, $result->final);
    }

    public function test_uses_base_price_when_custom_price_is_zero(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-003',
            'base_price' => 100.00,
            'custom_price' => 0.00,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(100.00, $result->final);
    }

    public function test_uses_base_price_when_custom_price_is_null(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-004',
            'base_price' => 100.00,
            'custom_price' => null,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(100.00, $result->final);
    }

    public function test_returns_zero_for_zero_base_price(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-005',
            'base_price' => 0.00,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(0.00, $result->base);
        $this->assertEquals(0.00, $result->final);
    }

    public function test_caches_price_result(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-006',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        $result1 = $this->priceEngine->calculate($product);
        $result2 = $this->priceEngine->calculate($product);

        $this->assertEquals($result1->final, $result2->final);
    }

    public function test_price_result_has_difference_method(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-007',
            'base_price' => 100.00,
            'custom_price' => 120.00,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(20.00, $result->getDifference());
    }

    public function test_price_result_to_array(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-008',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);
        $array = $result->toArray();

        $this->assertArrayHasKey('base', $array);
        $this->assertArrayHasKey('final', $array);
        $this->assertArrayHasKey('difference', $array);
        $this->assertEquals(100.00, $array['base']);
        $this->assertEquals(100.00, $array['final']);
        $this->assertEquals(0.00, $array['difference']);
    }
}

