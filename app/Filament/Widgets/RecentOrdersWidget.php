<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Customers\CustomerResource;
class RecentOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Son Siparişler';

    protected static ?int $sort = 3;


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
                    ->url(fn ($record) => OrderResource::getUrl('view', ['record' => $record])),
                
                TextColumn::make('customer.full_name')
                    ->label('Müşteri')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->url(fn ($record) => CustomerResource::getUrl('view', ['record' => $record])),
                
                TextColumn::make('total')
                    ->label('Tutar')
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
                    })
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
            ])
            ->defaultSort('created_at', 'desc');
    }
}

