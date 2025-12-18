<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductImage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Schema $schema): Schema
    {
        $productId = $this->ownerRecord->id;

        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('Görsel')
                    ->disk("public")
                    ->visibility('public')
                    ->image()
                    ->directory(fn () => "products/{$productId}/custom")
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
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state, ProductImage $record = null) {
                        // Eğer primary seçildiyse, diğer görsellerin primary'ini kaldır
                        if ($state && $record) {
                            $record->product->images()
                                ->where('id', '!=', $record->id)
                                ->update(['is_primary' => false]);
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Görsel')
                    ->getStateUsing(fn (ProductImage $record): ?string => $record->url)
                    ->size(80),
                TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'custom' => 'success',
                        'external' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_primary')
                    ->label('Birincil')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Sıralama')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('quickUpload')
                    ->label('Hızlı Resim Yükle')
                    ->icon('heroicon-o-photo')
                    ->color('success')
                    ->form([
                        FileUpload::make('images')
                            ->label('Görseller')
                            ->disk('public')
                            ->visibility('public')
                            ->image()
                            ->directory(fn () => "products/{$this->ownerRecord->id}/custom")
                            ->multiple()
                            ->maxFiles(20)
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->required()
                            ->helperText('Birden fazla görsel seçebilirsiniz (maksimum 20 adet)'),
                    ])
                    ->action(function (array $data): void {
                        $images = $data['images'] ?? [];
                        $productId = $this->ownerRecord->id;
                        $maxSortOrder = $this->ownerRecord->images()->max('sort_order') ?? 0;
                        $isFirstImage = $this->ownerRecord->images()->count() === 0;

                        $uploadedCount = 0;
                        foreach ($images as $index => $imagePath) {
                            $maxSortOrder++;
                            
                            ProductImage::create([
                                'product_id' => $productId,
                                'type' => 'custom',
                                'path' => $imagePath,
                                'is_primary' => $isFirstImage && $index === 0,
                                'sort_order' => $maxSortOrder,
                            ]);

                            $uploadedCount++;
                        }

                        Notification::make()
                            ->title("{$uploadedCount} görsel başarıyla yüklendi")
                            ->success()
                            ->send();
                    }),
                CreateAction::make()
                    ->label('Görsel Ekle')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Custom görsel için gerekli alanları ayarla
                        $data['type'] = 'custom';
                        $data['sort_order'] = $this->ownerRecord->images()->max('sort_order') ?? 0;
                        $data['sort_order']++;

                        return $data;
                    })
                    ->after(function (Model $record) {
                        // İlk görsel ise primary yap
                        if ($this->ownerRecord->images()->count() === 1) {
                            $record->update(['is_primary' => true]);
                        }
                    }),
            ])
            ->actions([
                Action::make('setPrimary')
                    ->label('Birincil Yap')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (ProductImage $record): bool => ! $record->is_primary)
                    ->action(function (ProductImage $record) {
                        // Diğer tüm görsellerin primary'ini kaldır
                        $record->product->images()->update(['is_primary' => false]);
                        // Bu görseli primary yap
                        $record->update(['is_primary' => true]);
                    })
                    ->requiresConfirmation(),
                DeleteAction::make()
                    ->label('Sil')
                    ->visible(fn (ProductImage $record): bool => $record->type === 'custom'), // Sadece custom görseller silinebilir
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order') // Drag & drop ile sıralama
            ->paginated([10, 25, 50]);
    }
}
