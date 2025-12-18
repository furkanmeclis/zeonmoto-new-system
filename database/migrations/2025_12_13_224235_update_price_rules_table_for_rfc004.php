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
        Schema::dropIfExists('price_rules');

        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // global, category, product
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('type'); // percentage, amount
            $table->decimal('value', 10, 2);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();

            $table->index(['scope', 'scope_id']);
            $table->index('priority');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_rules');

        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2)->nullable();
            $table->decimal('multiplier', 8, 4)->nullable();
            $table->decimal('add_amount', 10, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
