<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Genel Bilgiler')
                    ->schema([
                        TextInput::make('name')
                            ->label('Ürün Adı')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Sıralama')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
                Section::make('Fiyatlar')
                    ->schema([
                        TextInput::make('base_price')
                            ->label('Temel Fiyat')
                            ->numeric()
                            ->prefix('₺')
                            ->required()
                            ->disabled(fn ($record, string $operation) => $operation === 'edit' && $record && $record->externals()->exists())
                            ->dehydrated()
                            ->helperText(fn ($record, string $operation) => ($operation === 'edit' && $record && $record->externals()->exists()) 
                                ? 'Harici sağlayıcıdan gelen temel fiyat' 
                                : 'Temel fiyat (harici sağlayıcıdan gelmiyorsa manuel girebilirsiniz)'),
                        TextInput::make('custom_price')
                            ->label('Özel Fiyat')
                            ->numeric()
                            ->prefix('₺')
                            ->nullable()
                            ->step(0.01)
                            ->helperText('Manuel fiyat girmek isterseniz bu alanı kullanın. Boş bırakılırsa temel fiyat kullanılır. Fiyat kuralları bu fiyata uygulanır.'),
                        TextInput::make('final_price')
                            ->label('Final Fiyat')
                            ->numeric()
                            ->prefix('₺')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($record) {
                                if (! $record) {
                                    return null;
                                }
                                return $record->calculatePrice()->final;
                            })
                            ->helperText('Fiyat kuralları uygulanarak hesaplanan final fiyat'),
                    ])
                    ->columns(2),
            ]);
    }
}
