<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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

        return $schema
            ->components([
                Section::make('Genel Bilgiler')
                    ->schema([
                        ImageEntry::make('default_image_url')
                            ->label('Görsel')
                            ->getStateUsing(fn () => $record->default_image_url)
                            ->defaultImageUrl(url('/images/placeholder.png'))
                            ->height(200)
                            ->width(200)
                            ->columnSpanFull(),
                        TextEntry::make('name')
                            ->label('Ürün Adı')
                            ->icon(Heroicon::OutlinedCube)
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable(),
                        IconEntry::make('is_active')
                            ->label('Durum')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),
                        TextEntry::make('sort_order')
                            ->label('Sıralama')
                            ->icon(Heroicon::OutlinedBars3),
                        TextEntry::make('created_at')
                            ->label('Oluşturulma Tarihi')
                            ->dateTime('d/m/Y H:i')
                            ->icon(Heroicon::OutlinedCalendar),
                        TextEntry::make('updated_at')
                            ->label('Güncelleme Tarihi')
                            ->dateTime('d/m/Y H:i')
                            ->icon(Heroicon::OutlinedClock),
                    ])
                    ->columns(2),
                Section::make('Fiyatlar')
                    ->schema([
                        TextEntry::make('base_price')
                            ->label('Temel Fiyat')
                            ->money('TRY')
                            ->icon(Heroicon::OutlinedCurrencyDollar),
                        TextEntry::make('custom_price')
                            ->label('Özel Fiyat')
                            ->money('TRY')
                            ->icon(Heroicon::OutlinedCurrencyDollar)
                            ->placeholder('-')
                            ->default('-'),
                        TextEntry::make('final_price')
                            ->label('Final Fiyat')
                            ->formatStateUsing(function () use ($record) {
                                $result = $record->calculatePrice();
                                return '₺' . number_format($result->final, 2);
                            })
                            ->icon(Heroicon::OutlinedCurrencyDollar)
                            ->color('success'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Section::make('Kategoriler')
                    ->schema([
                        RepeatableEntry::make('categories')
                            ->schema([
                                TextEntry::make('display_name')
                                    ->label('Kategori Adı')
                                    ->icon(Heroicon::OutlinedTag),
                                TextEntry::make('slug')
                                    ->label('Slug')
                                    ->icon(Heroicon::OutlinedHashtag)
                                    ->copyable(),
                                IconEntry::make('is_active')
                                    ->label('Aktif')
                                    ->boolean()
                                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                                    ->falseIcon(Heroicon::OutlinedXCircle)
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible()
                    ->visible(fn () => $record->categories()->count() > 0),
                Section::make('Görseller')
                    ->schema([
                        TextEntry::make('images_count')
                            ->label('Görsel Sayısı')
                            ->formatStateUsing(fn () => $record->images()->count())
                            ->icon(Heroicon::OutlinedPhoto),
                        RepeatableEntry::make('images')
                            ->schema([
                                ImageEntry::make('url')
                                    ->label('Görsel')
                                    ->height(100)
                                    ->width(100)
                                    ->defaultImageUrl(url('/images/placeholder.png')),
                                TextEntry::make('url')
                                    ->label('URL')
                                    ->copyable()
                                    ->limit(50),
                                IconEntry::make('is_primary')
                                    ->label('Birincil')
                                    ->boolean()
                                    ->trueIcon(Heroicon::OutlinedStar)
                                    ->falseIcon(Heroicon::OutlinedStar)
                                    ->trueColor('warning')
                                    ->falseColor('gray'),
                                TextEntry::make('sort_order')
                                    ->label('Sıralama')
                                    ->icon(Heroicon::OutlinedBars3),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible()
                    ->visible(fn () => $record->images()->count() > 0),
                Section::make('Harici Sağlayıcılar')
                    ->schema([
                        RepeatableEntry::make('externals')
                            ->schema([
                                TextEntry::make('provider_key')
                                    ->label('Sağlayıcı')
                                    ->badge()
                                    ->icon(Heroicon::OutlinedGlobeAlt),
                                TextEntry::make('external_uniqid')
                                    ->label('Harici ID')
                                    ->copyable()
                                    ->icon(Heroicon::OutlinedHashtag),
                                TextEntry::make('created_at')
                                    ->label('Eşleştirme Tarihi')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon(Heroicon::OutlinedCalendar),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible()
                    ->visible(fn () => $record->externals()->count() > 0),
            ]);
    }
}
