<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Product;
use App\Services\Pricing\PriceEngine;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $priceEngine = app(PriceEngine::class);
        $subtotal = 0;

        // OrderItems için snapshot alanlarını doldur
        if (isset($data['orderItems']) && is_array($data['orderItems'])) {
            foreach ($data['orderItems'] as &$item) {
                // Yeni eklenen item'lar için snapshot al
                if (isset($item['product_id']) && $item['product_id']) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        // Fiyat hesapla - form'dan gelen değerleri kullan, yoksa hesapla
                        $unitPrice = round($item['unit_price'] ?? ($priceEngine->calculate($product)->final), 2);
                        $quantity = $item['quantity'] ?? 1;
                        $lineDiscount = round($item['line_discount'] ?? 0, 2);
                        $totalPrice = round($item['total_price'] ?? ($unitPrice * $quantity - $lineDiscount), 2);
                        $lineTotal = round($item['line_total'] ?? $totalPrice, 2);

                        // PriceEngine ile snapshot için fiyat hesapla
                        $priceResult = $priceEngine->calculate($product);

                        // Sadece yeni item'lar için snapshot al (mevcut item'ların snapshot'ları korunur)
                        if (!isset($item['product_name_snapshot']) || empty($item['product_name_snapshot'])) {
                            $item['product_name_snapshot'] = $product->name;
                            $item['sku_snapshot'] = $product->sku;
                            $item['unit_price_snapshot'] = $unitPrice;
                            $item['line_total'] = $lineTotal;
                            $item['price_rules_snapshot'] = [
                                'base_price' => round($priceResult->base, 2),
                                'final_price' => round($priceResult->final, 2),
                                'applied_rules' => $priceResult->appliedRules,
                            ];
                        } else {
                            // Mevcut item'lar için sadece line_total'ı güncelle
                            $item['line_total'] = $lineTotal;
                        }

                        // Deprecated alanları da doldur (backward compatibility)
                        $item['unit_price'] = $unitPrice;
                        $item['total_price'] = $totalPrice;

                        $subtotal += $lineTotal;
                    }
                } else {
                    // Mevcut item'lar için line_total'ı kullan
                    $subtotal += round($item['line_total'] ?? $item['total_price'] ?? 0, 2);
                }
            }
        }

        // Toplam indirimi düş
        $totalDiscount = round($data['total_discount'] ?? 0, 2);
        $data['subtotal'] = round($subtotal, 2);
        $data['total'] = round(max(0, $subtotal - $totalDiscount), 2);
        $data['total_amount'] = $data['total']; // Backward compatibility

        return $data;
    }
}
