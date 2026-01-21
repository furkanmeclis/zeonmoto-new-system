<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
                        Select::make('categories')
                            ->label('Kategoriler')
                            ->multiple()
                            ->relationship(
                                'categories',
                                'display_name',
                                fn (Builder $query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('display_name')
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Ürünü kategorilere bağlayın'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Toggle::make('has_stock')
                            ->label('Ekstra Stok Var (Kapanma Engelleyici)')
                            ->default(false)
                            ->helperText('Açık olduğunda, Ckymoto\'dan is_active=0 gelse bile is_active kapanmaz'),
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
                        TextInput::make('retail_price')
                            ->label('Perakende Satış Fiyatı')
                            ->numeric()
                            ->prefix('₺')
                            ->nullable()
                            ->step(0.01)
                            ->helperText('Şifresi olmayan müşteriler için görüntülenecek fiyat. Boş bırakılırsa final fiyat kullanılır.'),
                    ])
                    ->columns(2),
                Section::make('Ürün Görselleri')
                    ->schema([
                        Repeater::make('images')
                            ->relationship('images')
                            ->label('Görseller')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                // Custom görsel için type'ı ayarla
                                $data['type'] = 'custom';
                                return $data;
                            })
                            ->schema([
                                FileUpload::make('path')
                                    ->label('Görsel')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->image()
                                    ->directory(fn (?Model $record): string => $record && isset($record->product_id)
                                        ? "products/{$record->product_id}/custom"
                                        : 'products/temp')
                                    ->required()
                                    ->maxSize(5120) // 5MB
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ]),
                                Toggle::make('is_primary')
                                    ->label('Birincil Görsel')
                                    ->default(false)
                                    ->helperText('Bu görseli ana görsel olarak işaretle'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['path'] ?? null)
                            ->collapsible()
                            ->reorderableWithButtons()
                            ->cloneable()
                            ->helperText('Ürün görsellerini ekleyebilirsiniz. İlk görsel otomatik olarak birincil görsel olarak ayarlanır.'),
                    ]),
            ]);
    }
}
