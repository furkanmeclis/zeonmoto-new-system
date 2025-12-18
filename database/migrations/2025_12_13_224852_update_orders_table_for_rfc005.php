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
        // Önce mevcut kayıt sayısını kontrol et
        $orderCount = DB::table('orders')->count();
        
        Schema::table('orders', function (Blueprint $table) {
            // Order number (unique identifier) - önce nullable ekle
            $table->string('order_no')->nullable()->after('id');
            
            // Status using OrderStatus enum
            $table->string('status')->default('NEW')->after('order_no');
            
            // Financial fields - mevcut kayıtlar için nullable
            $table->decimal('subtotal', 10, 2)->nullable()->after('total_amount');
            $table->decimal('total', 10, 2)->nullable()->after('subtotal');
            $table->string('currency', 3)->default('TRY')->after('total');
        });
        
        // Migrate existing admin_status to status
        if ($orderCount > 0) {
            DB::table('orders')->get()->each(function ($order) {
                $statusMap = [
                    'pending' => 'NEW',
                    'processing' => 'PREPARING',
                    'completed' => 'COMPLETED',
                    'cancelled' => 'CANCELLED',
                ];
                
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'order_no' => 'ORD-' . str_pad($order->id, 8, '0', STR_PAD_LEFT),
                        'status' => $statusMap[$order->admin_status ?? 'pending'] ?? 'NEW',
                        'subtotal' => $order->total_amount ?? 0,
                        'total' => ($order->total_amount ?? 0) - ($order->total_discount ?? 0),
                    ]);
            });
        }
        
        // Unique index ekle (SQLite'da unique constraint için)
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS orders_order_no_unique ON orders(order_no)');
        } catch (\Exception $e) {
            // Index zaten varsa devam et
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_no', 'status', 'subtotal', 'total', 'currency']);
        });
    }
};
