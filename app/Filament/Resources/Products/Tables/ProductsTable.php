<?php

namespace App\Filament\Resources\Products\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Filament\Resources\Products\RelationManagers\ProductImagesRelationManager;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Guava\FilamentModalRelationManagers\Actions\RelationManagerAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        
        // Check if livewire has isGridLayout method (for TableLayoutToggle plugin)
        // If not available (e.g., in ModalTableSelect context), use list layout
        $isGridLayout = method_exists($livewire, 'isGridLayout') && $livewire->isGridLayout();
        $isListLayout = method_exists($livewire, 'isListLayout') && $livewire->isListLayout();

        return $table
            ->columns(
                $isGridLayout
                    ? static::getGridTableColumns()
                    : static::getListTableColumns()
            )
            ->contentGrid(
                fn () => $isListLayout
                    ? null
                    : [
                        'md' => 2,
                        'lg' => 3,
                        'xl' => 4,
                    ]
            )
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiflik Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif Ürünler')
                    ->falseLabel('Pasif Ürünler'),
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('categories', 'display_name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Filter::make('sku')
                    ->label('SKU')
                    ->form([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->placeholder('SKU ile ara...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['sku'],
                            fn (Builder $query, $sku): Builder => $query->where('sku', 'like', "%{$sku}%"),
                        );
                    }),
                Filter::make('price_range')
                    ->label('Final Fiyat Aralığı')
                    ->form([
                        TextInput::make('min_price')
                            ->label('Min Fiyat')
                            ->numeric()
                            ->prefix('₺'),
                        TextInput::make('max_price')
                            ->label('Max Fiyat')
                            ->numeric()
                            ->prefix('₺'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn (Builder $query, $price): Builder => $query->where('final_price', '>=', $price),
                            )
                            ->when(
                                $data['max_price'],
                                fn (Builder $query, $price): Builder => $query->where('final_price', '<=', $price),
                            );
                    }),
                Filter::make('base_price_range')
                    ->label('Temel Fiyat Aralığı')
                    ->form([
                        TextInput::make('min_base_price')
                            ->label('Min Temel Fiyat')
                            ->numeric()
                            ->prefix('₺'),
                        TextInput::make('max_base_price')
                            ->label('Max Temel Fiyat')
                            ->numeric()
                            ->prefix('₺'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_base_price'],
                                fn (Builder $query, $price): Builder => $query->where('base_price', '>=', $price),
                            )
                            ->when(
                                $data['max_base_price'],
                                fn (Builder $query, $price): Builder => $query->where('base_price', '<=', $price),
                            );
                    }),
                TernaryFilter::make('has_custom_price')
                    ->label('Özel Fiyat')
                    ->placeholder('Tümü')
                    ->trueLabel('Özel Fiyatı Olanlar')
                    ->falseLabel('Özel Fiyatı Olmayanlar')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('custom_price'),
                        false: fn (Builder $query) => $query->whereNull('custom_price'),
                    ),
                Filter::make('created_at')
                    ->label('Oluşturulma Tarihi')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Başlangıç Tarihi'),
                        DatePicker::make('created_until')
                            ->label('Bitiş Tarihi'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('updated_at')
                    ->label('Güncelleme Tarihi')
                    ->form([
                        DatePicker::make('updated_from')
                            ->label('Başlangıç Tarihi'),
                        DatePicker::make('updated_until')
                            ->label('Bitiş Tarihi'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['updated_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('updated_at', '>=', $date),
                            )
                            ->when(
                                $data['updated_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('updated_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Action::make('quickPriceEdit')
                    ->label('Hızlı Fiyat Düzenle')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->color('warning')
                    ->fillForm(fn ($record): array => [
                        'base_price' => $record->base_price,
                        'custom_price' => $record->custom_price,
                    ])
                    ->schema([
                        TextInput::make('base_price')
                            ->label('Temel Fiyat')
                            ->numeric()
                            ->prefix('₺')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('custom_price')
                            ->label('Özel Fiyat')
                            ->numeric()
                            ->prefix('₺')
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('Boş bırakılırsa temel fiyat kullanılır')
                            ->belowContent(
                                Action::make('setCustomPriceFromBase')
                                    ->label('Baz Fiyat Ayarla')
                                    ->icon(Heroicon::OutlinedArrowDown)
                                    ->color('gray')
                                    ->size('sm')
                                    ->action(function (Get $get, Set $set) {
                                        $basePrice = $get('base_price');
                                        if ($basePrice) {
                                            $set('custom_price', $basePrice);
                                        }
                                    })
                            ),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->update([
                            'base_price' => $data['base_price'],
                            'custom_price' => $data['custom_price'] ?: null,
                        ]);

                        Notification::make()
                            ->title('Fiyat başarıyla güncellendi')
                            ->success()
                            ->send();
                    }),
                RelationManagerAction::make('images')
                    ->label('Görseller')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->relationManager(ProductImagesRelationManager::make())
                    ->record(fn ($record) => $record)
                    ->compact(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label('Dışa Aktar')
                    ->fileName('urunler')
                    ->defaultFormat('xlsx')
                    ->disableTableColumns()
                    ->withColumns([
                        TextColumn::make('name')
                            ->label('Ürün Adı'),
                        TextColumn::make('sku')
                            ->label('SKU'),
                        TextColumn::make('base_price')
                            ->label('Temel Fiyat')
                            ->money('TRY'),
                        TextColumn::make('custom_price')
                            ->label('Özel Fiyat')
                            ->money('TRY')
                            ->placeholder('-'),
                        TextColumn::make('final_price')
                            ->label('Final Fiyat')
                            ->formatStateUsing(function ($record) {
                                if (! $record) {
                                    return '-';
                                }
                                $result = $record->calculatePrice();
                                return '₺' . number_format($result->final, 2);
                            }),
                        TextColumn::make('is_active')
                            ->label('Aktif')
                            ->formatStateUsing(fn ($state) => $state ? 'Evet' : 'Hayır'),
                        TextColumn::make('sort_order')
                            ->label('Sıralama'),
                        TextColumn::make('created_at')
                            ->label('Oluşturulma')
                            ->dateTime(),
                    ])
                    ->formatStates([
                        'default_image_url' => fn ($state) => $state ?? '',
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    FilamentExportBulkAction::make('export')
                        ->label('Dışa Aktar')
                        ->fileName('urunler')
                        ->disableTableColumns()
                        ->withColumns([
                            TextColumn::make('name')
                                ->label('Ürün Adı'),
                            TextColumn::make('sku')
                                ->label('SKU'),
                            TextColumn::make('base_price')
                                ->label('Temel Fiyat')
                                ->money('TRY'),
                            TextColumn::make('custom_price')
                                ->label('Özel Fiyat')
                                ->money('TRY')
                                ->placeholder('-'),
                            TextColumn::make('final_price')
                                ->label('Final Fiyat')
                                ->formatStateUsing(function ($record) {
                                    if (! $record) {
                                        return '-';
                                    }
                                    $result = $record->calculatePrice();
                                    return '₺' . number_format($result->final, 2);
                                }),
                            TextColumn::make('is_active')
                                ->label('Aktif')
                                ->formatStateUsing(fn ($state) => $state ? 'Evet' : 'Hayır'),
                            TextColumn::make('sort_order')
                                ->label('Sıralama'),
                            TextColumn::make('created_at')
                                ->label('Oluşturulma')
                                ->dateTime(),
                        ])
                        ->formatStates([
                            'default_image_url' => fn ($state) => $state ?? '',
                        ]),
                    BulkAction::make('activate')
                        ->label('Toplu Aktif Et')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = $records->count();
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->title("{$count} ürün aktif edildi")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Toplu Deaktif Et')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = $records->count();
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->title("{$count} ürün deaktif edildi")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('attach_categories')
                        ->label('Kategoriye Ekle')
                        ->icon('heroicon-o-tag')
                        ->color('info')
                        ->form([
                            Select::make('categories')
                                ->label('Kategoriler')
                                ->multiple()
                                ->options(Category::query()->where('is_active', true)->pluck('display_name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Seçili ürünlere eklenecek kategorileri seçin'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = $records->count();
                            $categoryIds = $data['categories'] ?? [];

                            if (empty($categoryIds)) {
                                Notification::make()
                                    ->title('Lütfen en az bir kategori seçin')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $records->each(function ($product) use ($categoryIds) {
                                $product->categories()->syncWithoutDetaching($categoryIds);
                            });

                            Notification::make()
                                ->title("{$count} ürüne kategori eklendi")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Define the columns for the table when displayed in list layout
     */
    public static function getListTableColumns(): array
    {
        return [
            ImageColumn::make('default_image_url')
                ->label('Görsel')
                ->getStateUsing(fn ($record) => $record->default_image_url)
                ->size(60)
                ->defaultImageUrl(url('/images/placeholder.png'))
                ->toggleable(isToggledHiddenByDefault: false),
            TextColumn::make('name')
                ->label('Ürün Adı')
                ->searchable()
                ->sortable(),
            TextColumn::make('sku')
                ->label('SKU')
                ->searchable()
                ->sortable()
                ->copyable(),
            TextColumn::make('base_price')
                ->label('Temel Fiyat')
                ->money('TRY')
                ->sortable(),
            TextColumn::make('custom_price')
                ->label('Özel Fiyat')
                ->money('TRY')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->placeholder('-')
                ->tooltip('Manuel girilen özel fiyat. Boşsa temel fiyat kullanılır.'),
            TextColumn::make('final_price')
                ->label('Final Fiyat')
                ->formatStateUsing(function ($record) {
                    if (! $record) {
                        return '-';
                    }
                    $result = $record->calculatePrice();
                    return '₺' . number_format($result->final, 2);
                })
                ->sortable()
                ->tooltip('Fiyat kuralları uygulanarak hesaplanan final fiyat'),
            ToggleColumn::make('is_active')
                ->label('Aktif')
                ->sortable()
                ->onColor('success')
                ->offColor('danger'),
            TextColumn::make('sort_order')
                ->label('Sıralama')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('created_at')
                ->label('Oluşturulma')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Define the columns for the table when displayed in grid layout
     */
    public static function getGridTableColumns(): array
    {
        return [
            Stack::make([
                ImageColumn::make('default_image_url')
                    ->label('Görsel')
                    ->getStateUsing(fn ($record) => $record->default_image_url)
                    ->size(200)
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->extraAttributes([
                        'class' => 'mx-auto',
                    ]),

                TextColumn::make('name')
                    ->description(__('Ürün Adı'), position: 'above')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                Split::make([
                    TextColumn::make('sku')
                        ->description(__('SKU'), position: 'above')
                        ->searchable()
                        ->sortable()
                        ->copyable()
                        ->size('sm'),
                    TextColumn::make('final_price')
                        ->label('Final Fiyat')
                        ->description(__('Fiyat'), position: 'above')
                        ->formatStateUsing(function ($record) {
                            if (! $record) {
                                return '-';
                            }
                            $result = $record->calculatePrice();
                            return '₺' . number_format($result->final, 2);
                        })
                        ->sortable()
                        ->weight('bold')
                        ->color('success')
                        ->size('sm'),
                ]),

                Split::make([
                    ToggleColumn::make('is_active')
                        ->label('Aktif')
                        ->sortable()
                        ->onColor('success')
                        ->offColor('danger')
                        ->alignEnd(),
                ]),
            ])->space(3)->extraAttributes([
                'class' => 'pb-4',
            ]),
        ];
    }
}
