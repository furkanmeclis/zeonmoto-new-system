<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $product = $this->record;

        // Geçici dizindeki görselleri product_id dizinine taşı
        $images = $product->images()->where('type', 'custom')->get();

        foreach ($images as $index => $image) {
            if ($image->path && str_starts_with($image->path, 'products/temp/')) {
                // Geçici dizinden dosyayı taşı
                $oldPath = $image->path;
                $newPath = "products/{$product->id}/custom/" . basename($oldPath);

                if (Storage::disk('public')->exists($oldPath)) {
                    // Yeni dizin oluştur
                    Storage::disk('public')->makeDirectory("products/{$product->id}/custom");

                    // Dosyayı taşı
                    Storage::disk('public')->move($oldPath, $newPath);

                    // Path'i güncelle
                    $image->update(['path' => $newPath]);
                }
            }

            // İlk görseli primary yap
            if ($index === 0 && ! $image->is_primary) {
                // Önce tüm görsellerin primary'sini kaldır
                $product->images()->update(['is_primary' => false]);
                // İlk görseli primary yap
                $image->update(['is_primary' => true]);
            }

            // Sort order ayarla
            if (! $image->sort_order || $image->sort_order === 0) {
                $image->update(['sort_order' => $index + 1]);
            }
        }
    }
}
