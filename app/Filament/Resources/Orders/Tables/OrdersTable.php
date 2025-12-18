<?php

namespace App\Filament\Resources\Orders\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\OrderStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_no')
                    ->label('Sipariş No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer.full_name')
                    ->label('Müşteri')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Toplam Tutar')
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        OrderStatus::Draft->value => 'Taslak',
                        OrderStatus::New->value => 'Yeni',
                        OrderStatus::Preparing->value => 'Hazırlanıyor',
                        OrderStatus::Completed->value => 'Tamamlandı',
                        OrderStatus::Cancelled->value => 'İptal Edildi',
                    ])
                    ->multiple(),
                SelectFilter::make('customer')
                    ->label('Müşteri')
                    ->relationship('customer', 'first_name')
                    ->searchable(['first_name', 'last_name'])
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}"),
                Filter::make('total_range')
                    ->label('Tutar Aralığı')
                    ->form([
                        TextInput::make('min_total')
                            ->label('Min Tutar')
                            ->numeric()
                            ->prefix('₺'),
                        TextInput::make('max_total')
                            ->label('Max Tutar')
                            ->numeric()
                            ->prefix('₺'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_total'],
                                fn (Builder $query, $total): Builder => $query->where('total', '>=', $total),
                            )
                            ->when(
                                $data['max_total'],
                                fn (Builder $query, $total): Builder => $query->where('total', '<=', $total),
                            );
                    }),
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
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label('Dışa Aktar')
                    ->fileName('siparisler')
                    ->defaultFormat('xlsx'),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Yazdır')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn ($record) => route('orders.print', $record))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    FilamentExportBulkAction::make('export')
                        ->label('Dışa Aktar')
                        ->fileName('siparisler'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
