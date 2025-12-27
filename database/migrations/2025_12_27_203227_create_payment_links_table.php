<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->string('paytr_link_id')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->text('link_url');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('TRY');
            $table->string('link_type'); // Product veya Collection
            $table->integer('max_installment')->default(12);
            $table->dateTime('expiry_date')->nullable();
            $table->string('status')->default('pending'); // pending, paid, expired, cancelled
            $table->string('merchant_oid'); // Sipariş numarası (order_no)
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->json('callback_data')->nullable();
            $table->dateTime('callback_received_at')->nullable();
            $table->dateTime('sms_sent_at')->nullable();
            $table->dateTime('email_sent_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('paytr_link_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('merchant_oid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
