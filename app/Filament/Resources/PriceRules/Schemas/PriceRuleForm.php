<?php

namespace App\Filament\Resources\PriceRules\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\PriceRuleScope;
use App\PriceRuleType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PriceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kural Kapsamı')
                    ->schema([
                        Select::make('scope')
                            ->label('Kapsam')
                            ->options([
                                PriceRuleScope::Global->value => 'Global',
                                PriceRuleScope::Category->value => 'Kategori',
                                PriceRuleScope::Product->value => 'Ürün',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('scope_id', null)),
                        Select::make('scope_id')
                            ->label('Kapsam Seçimi')
                            ->visible(fn (callable $get) => in_array($get('scope'), [
                                PriceRuleScope::Category->value,
                                PriceRuleScope::Product->value,
                            ]))
                            ->required(fn (callable $get) => in_array($get('scope'), [
                                PriceRuleScope::Category->value,
                                PriceRuleScope::Product->value,
                            ]))
                            ->options(function (callable $get) {
                                $scope = $get('scope');
                                
                                if ($scope === PriceRuleScope::Category->value) {
                                    return Category::query()
                                        ->orderBy('display_name')
                                        ->pluck('display_name', 'id')
                                        ->toArray();
                                }
                                
                                if ($scope === PriceRuleScope::Product->value) {
                                    return Product::query()
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Product $product) => [
                                            $product->id => "{$product->name} (SKU: {$product->sku})"
                                        ])
                                        ->toArray();
                                }
                                
                                return [];
                            })
                            ->searchable()
                            ->reactive(),
                    ])
                    ->columns(2),
                Section::make('Kural Tipi ve Değer')
                    ->schema([
                        Select::make('type')
                            ->label('Tip')
                            ->options([
                                PriceRuleType::Percentage->value => 'Yüzde (%)',
                                PriceRuleType::Amount->value => 'Sabit Tutar (₺)',
                            ])
                            ->required()
                            ->reactive(),
                        TextInput::make('value')
                            ->label('Değer')
                            ->numeric()
                            ->required()
                            ->prefix(fn (callable $get) => $get('type') === PriceRuleType::Percentage->value ? '%' : '₺')
                            ->helperText(fn (callable $get) => match ($get('type')) {
                                PriceRuleType::Percentage->value => 'Örnek: 10 = %10 artış, -5 = %5 indirim',
                                PriceRuleType::Amount->value => 'Örnek: 50 = 50₺ artış, -20 = 20₺ indirim',
                                default => '',
                            })
                            ->step(fn (callable $get) => $get('type') === PriceRuleType::Percentage->value ? 0.01 : 0.01),
                    ])
                    ->columns(2),
                Section::make('Öncelik ve Durum')
                    ->schema([
                        TextInput::make('priority')
                            ->label('Öncelik')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Düşük sayı = yüksek öncelik'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Tarih Aralığı')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Başlangıç Tarihi')
                            ->nullable()
                            ->helperText('Boş bırakılırsa hemen başlar'),
                        DateTimePicker::make('ends_at')
                            ->label('Bitiş Tarihi')
                            ->nullable()
                            ->helperText('Boş bırakılırsa süresiz geçerlidir'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
