<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PriceRule;
use App\Models\Product;
use App\PriceRuleScope;
use App\PriceRuleType;
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

    public function test_calculates_price_with_global_percentage_rule(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(110.00, $result->final);
        $this->assertCount(1, $result->appliedRules);
    }

    public function test_calculates_price_with_category_amount_rule(): void
    {
        $category = Category::create([
            'external_name' => 'test-category',
            'display_name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-002',
            'base_price' => 100.00,
            'is_active' => true,
        ]);
        $product->categories()->attach($category);

        PriceRule::create([
            'scope' => PriceRuleScope::Category,
            'scope_id' => $category->id,
            'type' => PriceRuleType::Amount,
            'value' => 50,
            'priority' => 1,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(150.00, $result->final);
        $this->assertCount(1, $result->appliedRules);
    }

    public function test_calculates_price_with_product_percentage_rule(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-003',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Product,
            'scope_id' => $product->id,
            'type' => PriceRuleType::Percentage,
            'value' => 20,
            'priority' => 1,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(120.00, $result->final);
        $this->assertCount(1, $result->appliedRules);
    }

    public function test_applies_multiple_rules_in_priority_order(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-004',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        // Global rule: +10%
        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
        ]);

        // Product rule: +20%
        PriceRule::create([
            'scope' => PriceRuleScope::Product,
            'scope_id' => $product->id,
            'type' => PriceRuleType::Percentage,
            'value' => 20,
            'priority' => 2,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        // 100 + 10% = 110, then 110 + 20% = 132
        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(132.00, $result->final);
        $this->assertCount(2, $result->appliedRules);
    }

    public function test_handles_negative_values_for_discounts(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-005',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => -10,
            'priority' => 1,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(90.00, $result->final);
    }

    public function test_ignores_inactive_rules(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-006',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => false,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(100.00, $result->final);
        $this->assertCount(0, $result->appliedRules);
    }

    public function test_ignores_expired_rules(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-007',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(100.00, $result->final);
        $this->assertCount(0, $result->appliedRules);
    }

    public function test_ignores_future_rules(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-008',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(100.00, $result->base);
        $this->assertEquals(100.00, $result->final);
        $this->assertCount(0, $result->appliedRules);
    }

    public function test_returns_zero_for_zero_base_price(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-009',
            'base_price' => 0.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(0.00, $result->base);
        $this->assertEquals(0.00, $result->final);
        $this->assertCount(0, $result->appliedRules);
    }

    public function test_caches_price_result(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-010',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
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
            'sku' => 'TEST-011',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);

        $this->assertEquals(10.00, $result->getDifference());
    }

    public function test_price_result_to_array(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-012',
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        PriceRule::create([
            'scope' => PriceRuleScope::Global,
            'scope_id' => null,
            'type' => PriceRuleType::Percentage,
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
        ]);

        $result = $this->priceEngine->calculate($product);
        $array = $result->toArray();

        $this->assertArrayHasKey('base', $array);
        $this->assertArrayHasKey('final', $array);
        $this->assertArrayHasKey('difference', $array);
        $this->assertArrayHasKey('applied_rules', $array);
    }
}

