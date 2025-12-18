<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Snapshot fields - these are immutable after order creation
            $table->string('product_name_snapshot')->nullable()->after('product_id');
            $table->string('sku_snapshot')->nullable()->after('product_name_snapshot');
            $table->decimal('unit_price_snapshot', 10, 2)->nullable()->after('sku_snapshot');
            $table->decimal('line_total', 10, 2)->after('unit_price_snapshot');
            $table->json('price_rules_snapshot')->nullable()->after('line_total');
            
            // Rename existing fields for clarity
            // unit_price -> unit_price_snapshot (already exists, we'll keep both for migration)
            // total_price -> line_total (we'll migrate and keep both temporarily)
        });
        
        // Migrate existing data to snapshot fields
        DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('order_items.*', 'products.name', 'products.sku', 'order_items.unit_price', 'order_items.total_price')
            ->get()
            ->each(function ($item) {
                DB::table('order_items')
                    ->where('id', $item->id)
                    ->update([
                        'product_name_snapshot' => $item->name,
                        'sku_snapshot' => $item->sku,
                        'unit_price_snapshot' => $item->unit_price,
                        'line_total' => $item->total_price,
                        'price_rules_snapshot' => json_encode([
                            'base_price' => $item->unit_price,
                            'final_price' => $item->unit_price,
                            'applied_rules' => [],
                        ]),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_name_snapshot',
                'sku_snapshot',
                'unit_price_snapshot',
                'line_total',
                'price_rules_snapshot',
            ]);
        });
    }
};
