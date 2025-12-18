<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->record;
        $priceResult = $record->calculatePrice();

        return $schema
            ->components([
                Section::make('Fiyat Hesaplama Detayları')
                    ->schema([
                        TextEntry::make('base_price')
                            ->label('Temel Fiyat')
                            ->money('TRY')
                            ->icon('heroicon-o-currency-dollar'),
                        TextEntry::make('final_price')
                            ->label('Final Fiyat')
                            ->formatStateUsing(fn () => '₺' . number_format($priceResult->final, 2))
                            ->icon('heroicon-o-currency-dollar')
                            ->color('success'),
                        TextEntry::make('price_difference')
                            ->label('Fark')
                            ->formatStateUsing(fn () => '₺' . number_format($priceResult->getDifference(), 2))
                            ->icon('heroicon-o-arrow-trending-up')
                            ->color(fn () => $priceResult->getDifference() >= 0 ? 'success' : 'danger'),
                        TextEntry::make('applied_rules_count')
                            ->label('Uygulanan Kural Sayısı')
                            ->formatStateUsing(fn () => count($priceResult->appliedRules))
                            ->icon('heroicon-o-calculator'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Uygulanan Kurallar')
                    ->schema([
                        TextEntry::make('applied_rules')
                            ->label('Kurallar')
                            ->formatStateUsing(function () use ($priceResult) {
                                if (empty($priceResult->appliedRules)) {
                                    return 'Henüz kural uygulanmadı.';
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($priceResult->appliedRules as $rule) {
                                    $scopeLabel = match ($rule['scope']) {
                                        'global' => 'Global',
                                        'category' => 'Kategori',
                                        'product' => 'Ürün',
                                        default => $rule['scope'],
                                    };
                                    $typeLabel = match ($rule['type']) {
                                        'percentage' => '%',
                                        'amount' => '₺',
                                        default => '',
                                    };
                                    $value = $rule['value'];
                                    $difference = $rule['difference'];
                                    $priceBefore = $rule['price_before'];
                                    $priceAfter = $rule['price_after'];

                                    $html .= '<div class="p-3 bg-gray-50 rounded-lg">';
                                    $html .= "<strong>{$scopeLabel}</strong> - ";
                                    $html .= "{$typeLabel}{$value} ";
                                    $html .= "(Öncelik: {$rule['priority']})<br>";
                                    $html .= "<small class='text-gray-600'>";
                                    $html .= "₺" . number_format($priceBefore, 2) . " → ";
                                    $html .= "₺" . number_format($priceAfter, 2);
                                    $html .= " (" . ($difference >= 0 ? '+' : '') . "₺" . number_format($difference, 2) . ")";
                                    $html .= "</small>";
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn () => ! empty($priceResult->appliedRules)),
            ]);
    }
}
