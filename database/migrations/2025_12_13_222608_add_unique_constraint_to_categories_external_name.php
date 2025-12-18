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
        Schema::table('categories', function (Blueprint $table) {
            // Önce mevcut duplicate'leri kontrol et
            $duplicates = DB::table('categories')
                ->select('external_name', DB::raw('COUNT(*) as count'))
                ->groupBy('external_name')
                ->having('count', '>', 1)
                ->get();

            if ($duplicates->isNotEmpty()) {
                throw new \Exception('Duplicate external_name values found. Please fix duplicates before adding unique constraint.');
            }

            // Unique constraint ekle
            $table->unique('external_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['external_name']);
        });
    }
};
