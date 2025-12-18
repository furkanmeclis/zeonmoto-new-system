<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Orders are typically created through OrderCreationService
                // This form is for reference only
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('order_no')
                    ->label('Sipariş No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('total')
                    ->label('Toplam Tutar')
                    ->money('TRY')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Toplam Harcama')
                    ),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        \App\OrderStatus::Draft => 'gray',
                        \App\OrderStatus::New => 'warning',
                        \App\OrderStatus::Preparing => 'info',
                        \App\OrderStatus::Completed => 'success',
                        \App\OrderStatus::Cancelled => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        \App\OrderStatus::Draft => 'Taslak',
                        \App\OrderStatus::New => 'Yeni',
                        \App\OrderStatus::Preparing => 'Hazırlanıyor',
                        \App\OrderStatus::Completed => 'Tamamlandı',
                        \App\OrderStatus::Cancelled => 'İptal Edildi',
                        default => $state?->value ?? '-',
                    }),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
}
