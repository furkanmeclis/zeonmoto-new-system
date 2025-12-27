<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ürün Bilgileri')
                    ->schema([
                        Grid::make(12)
                            ->schema([
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
                                                $unitPrice = round($product->final_price ?? $product->base_price, 2);
                                                $set('unit_price_snapshot', $unitPrice);
                                                $quantity = $get('quantity') ?? 1;
                                                $lineTotal = round($unitPrice * $quantity, 2);
                                                $set('line_total', $lineTotal);
                                            }
                                        }
                                    })
                                    ->columnSpan(12),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextInput::make('quantity')
                                    ->label('Adet')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $unitPrice = round($get('unit_price_snapshot') ?? 0, 2);
                                        $quantity = $state ?? 1;
                                        $lineTotal = round($unitPrice * $quantity, 2);
                                        $set('line_total', $lineTotal);
                                    }),
                                TextInput::make('unit_price_snapshot')
                                    ->label('Birim Fiyat')
                                    ->numeric()
                                    ->prefix('₺')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $unitPrice = round($state ?? 0, 2);
                                        $quantity = $get('quantity') ?? 1;
                                        $lineTotal = round($unitPrice * $quantity, 2);
                                        $set('line_total', $lineTotal);
                                    }),
                                TextInput::make('line_total')
                                    ->label('Satır Toplamı')
                                    ->numeric()
                                    ->prefix('₺')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                ImageColumn::make('product.default_image_url')
                    ->label('Görsel')
                    ->getStateUsing(function ($record) {
                        return $record->product?->default_image_url;
                    })
                    ->size(60)
                    ->circular(false),
                TextColumn::make('product_name_snapshot')
                    ->label('Ürün (Snapshot)')
                    ->searchable()
                    ->sortable()
                    ->tooltip('Sipariş anındaki ürün adı'),
                TextColumn::make('sku_snapshot')
                    ->label('SKU (Snapshot)')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Sipariş anındaki SKU'),
                TextColumn::make('quantity')
                    ->label('Miktar')
                    ->sortable(),
                TextColumn::make('unit_price_snapshot')
                    ->label('Birim Fiyat (Snapshot)')
                    ->money('TRY')
                    ->sortable()
                    ->tooltip('Sipariş anındaki birim fiyat'),
                TextColumn::make('line_total')
                    ->label('Satır Toplamı')
                    ->money('TRY')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }

    /**
     * After order item is created, recalculate order totals.
     */
    protected function afterCreate(): void
    {
        $this->getOwnerRecord()->recalculateTotals();
    }

    /**
     * After order item is updated, recalculate order totals.
     */
    protected function afterUpdate(): void
    {
        $this->getOwnerRecord()->recalculateTotals();
    }

    /**
     * After order item is deleted, recalculate order totals.
     */
    protected function afterDelete(): void
    {
        $this->getOwnerRecord()->recalculateTotals();
    }
}
