<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with('customer')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('order_no')
                    ->label('Sipariş No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer.full_name')
                    ->label('Müşteri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Toplam')
                    ->money('TRY')
                    ->sortable(),
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
            ->heading('Son Siparişler')
            ->defaultSort('created_at', 'desc');
    }
}
