<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sipariş Bilgileri')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Müşteri')
                            ->relationship('customer', 'first_name', fn ($query) => $query->orderBy('first_name'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable(['first_name', 'last_name', 'phone'])
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label('Durum')
                            ->options([
                                \App\OrderStatus::Draft->value => 'Taslak',
                                \App\OrderStatus::New->value => 'Yeni',
                                \App\OrderStatus::Preparing->value => 'Hazırlanıyor',
                                \App\OrderStatus::Completed->value => 'Tamamlandı',
                                \App\OrderStatus::Cancelled->value => 'İptal Edildi',
                            ])
                            ->required()
                            ->default(\App\OrderStatus::New->value),
                    ])
                    ->columns(2),
                Section::make('Ürünler')
                    ->schema([
                        Repeater::make('orderItems')
                            ->label('Sipariş Kalemleri')
                            ->relationship('orderItems')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                // line_total'ı mutlaka doldur
                                if (!isset($data['line_total']) || $data['line_total'] === null) {
                                    $data['line_total'] = round($data['total_price'] ?? 0, 2);
                                }
                                return $data;
                            })
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        View::make('filament.forms.components.product-image-preview')
                                            ->viewData(fn ($get) => [
                                                'productId' => $get('product_id'),
                                            ])
                                            ->columnSpan(1),
                                        Select::make('product_id')
                                            ->label('Ürün')
                                            ->relationship(
                                                'product',
                                                'name',
                                                fn (Builder $query) => $query->where('is_active', true)->orderBy('name')
                                            )
                                            ->getOptionLabelFromRecordUsing(fn (Product $record) => "{$record->name} (SKU: {$record->sku}) - " . number_format($record->final_price, 2) . " ₺")
                                            ->searchable(['name', 'sku'])
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $finalPrice = round($product->final_price ?? $product->base_price, 2);
                                                        $set('unit_price', $finalPrice);
                                                        $quantity = $get('quantity') ?? 1;
                                                        $lineDiscount = round($get('line_discount') ?? 0, 2);
                                                        $totalPrice = round(($finalPrice * $quantity) - $lineDiscount, 2);
                                                        $set('total_price', $totalPrice);
                                                        $set('line_total', $totalPrice);
                                                    }
                                                }
                                            })
                                            ->columnSpan(11),
                                    ])
                                    ->columnSpan(12),
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('quantity')
                                            ->label('Adet')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->minValue(1)
                                            ->live(onBlur: false)
                                            ->partiallyRenderComponentsAfterStateUpdated(['total_price', 'line_total'])
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                $unitPrice = round($get('unit_price') ?? 0, 2);
                                                $lineDiscount = round($get('line_discount') ?? 0, 2);
                                                $quantity = $state ?? 1;
                                                $totalPrice = round(($unitPrice * $quantity) - $lineDiscount, 2);
                                                $set('total_price', $totalPrice);
                                                $set('line_total', $totalPrice);
                                            }),
                                        TextInput::make('unit_price')
                                            ->label('Birim Fiyat')
                                            ->numeric()
                                            ->prefix('₺')
                                            ->required()
                                            ->step(0.01)
                                            ->live(onBlur: false)
                                            ->partiallyRenderComponentsAfterStateUpdated(['total_price', 'line_total'])
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                $unitPrice = round($state ?? 0, 2);
                                                $quantity = $get('quantity') ?? 1;
                                                $lineDiscount = round($get('line_discount') ?? 0, 2);
                                                $totalPrice = round(($unitPrice * $quantity) - $lineDiscount, 2);
                                                $set('unit_price', $unitPrice);
                                                $set('total_price', $totalPrice);
                                                $set('line_total', $totalPrice);
                                            }),
                                        TextInput::make('line_discount')
                                            ->label('Satır İndirimi')
                                            ->numeric()
                                            ->prefix('₺')
                                            ->default(0)
                                            ->minValue(0)
                                            ->live(onBlur: false)
                                            ->partiallyRenderComponentsAfterStateUpdated(['total_price', 'line_total'])
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                $unitPrice = round($get('unit_price') ?? 0, 2);
                                                $quantity = $get('quantity') ?? 1;
                                                $lineDiscount = round($state ?? 0, 2);
                                                $totalPrice = round(($unitPrice * $quantity) - $lineDiscount, 2);
                                                $set('line_discount', $lineDiscount);
                                                $set('total_price', $totalPrice);
                                                $set('line_total', $totalPrice);
                                            }),
                                        TextInput::make('total_price')
                                            ->label('Toplam Fiyat')
                                            ->numeric()
                                            ->prefix('₺')
                                            ->required()
                                            ->step(0.01)
                                            ->live(onBlur: false)
                                            ->skipRenderAfterStateUpdated()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $totalPrice = round($state ?? 0, 2);
                                                $set('total_price', $totalPrice);
                                                $set('line_total', $totalPrice);
                                            }),
                                    ])
                                    ->columnSpan(12),
                                // Hidden field for line_total (required by database)
                                TextInput::make('line_total')
                                    ->label('Satır Toplamı (Snapshot)')
                                    ->numeric()
                                    ->default(fn ($get) => round($get('total_price') ?? 0, 2))
                                    ->required()
                                    ->hidden()
                                    ->dehydrated()
                                    ->dehydrateStateUsing(fn ($state, $get) => round($get('total_price') ?? $state ?? 0, 2)),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->itemLabel(fn (array $state): ?string => 
                                $state['product_id'] 
                                    ? Product::find($state['product_id'])?->name 
                                    : 'Yeni Ürün'
                            )
                            ->collapsible()
                            ->reorderable()
                            ->addActionLabel('Ürün Ekle')
                            ->required()
                            ->live(onBlur: false)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // OrderItems değiştiğinde toplam tutarı güncelle
                                $subtotal = 0;
                                if (is_array($state)) {
                                    foreach ($state as $item) {
                                        $subtotal += round($item['total_price'] ?? $item['line_total'] ?? 0, 2);
                                    }
                                }
                                $set('subtotal', round($subtotal, 2));
                                $totalDiscount = round($get('total_discount') ?? 0, 2);
                                $set('total', round(max(0, $subtotal - $totalDiscount), 2));
                            }),
                    ]),
                Section::make('Fiyat Özeti')
                    ->schema([
                        TextInput::make('total_discount')
                            ->label('Toplam İndirim')
                            ->numeric()
                            ->prefix('₺')
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: false)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $subtotal = round($get('subtotal') ?? 0, 2);
                                $totalDiscount = round($state ?? 0, 2);
                                $total = round(max(0, $subtotal - $totalDiscount), 2);
                                $set('total_discount', $totalDiscount);
                                $set('total', $total);
                            }),
                        TextInput::make('subtotal')
                            ->label('Ara Toplam')
                            ->numeric()
                            ->prefix('₺')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Ürün toplamlarının toplamı'),
                        TextInput::make('total')
                            ->label('Toplam Tutar')
                            ->numeric()
                            ->prefix('₺')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Ara toplamdan toplam indirim düşülerek hesaplanır'),
                    ])
                    ->columns(3),
            ]);
    }
}
