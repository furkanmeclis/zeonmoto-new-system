<?php

namespace App\Filament\Resources\PaymentLinks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentLinkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Link Bilgileri')
                    ->schema([
                        TextEntry::make('paytr_link_id')
                            ->label('PayTR Link ID')
                            ->copyable(),

                        TextEntry::make('link_url')
                            ->label('Link URL')
                            ->url(fn ($record) => $record->link_url)
                            ->openUrlInNewTab()
                            ->copyable(),

                        TextEntry::make('name')
                            ->label('Link Adı'),

                        TextEntry::make('price')
                            ->label('Fiyat')
                            ->money('TRY'),

                        TextEntry::make('currency')
                            ->label('Para Birimi'),

                        TextEntry::make('link_type')
                            ->label('Link Tipi')
                            ->badge()
                            ->color(fn ($state) => $state === 'Collection' ? 'success' : 'info')
                            ->formatStateUsing(fn ($state) => $state === 'Collection' ? 'Toplu' : 'Ürün'),

                        TextEntry::make('max_installment')
                            ->label('Maksimum Taksit'),

                        TextEntry::make('expiry_date')
                            ->label('Son Kullanma Tarihi')
                            ->dateTime('d.m.Y H:i')
                            ->color(fn ($record) => $record->isExpired() ? 'danger' : null),
                    ])
                    ->columns(2),

                Section::make('Müşteri Bilgileri')
                    ->schema([
                        TextEntry::make('merchant_oid')
                            ->label('Sipariş Numarası'),

                        TextEntry::make('order.order_no')
                            ->label('Sipariş')
                            ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', $record->order) : null)
                            ->color('primary'),

                        TextEntry::make('customer_email')
                            ->label('Müşteri Email')
                            ->copyable(),

                        TextEntry::make('customer_phone')
                            ->label('Müşteri Telefon')
                            ->copyable(),
                    ])
                    ->columns(2),

                Section::make('Durum')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Durum')
                            ->badge()
                            ->color(fn ($record) => $record->getStatusBadgeColor())
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'pending' => 'Beklemede',
                                'paid' => 'Ödendi',
                                'expired' => 'Süresi Doldu',
                                'cancelled' => 'İptal Edildi',
                                default => $state,
                            }),

                        TextEntry::make('callback_received_at')
                            ->label('Callback Alınma Zamanı')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Henüz alınmadı'),

                        TextEntry::make('sms_sent_at')
                            ->label('SMS Gönderilme Zamanı')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Henüz gönderilmedi'),

                        TextEntry::make('email_sent_at')
                            ->label('Email Gönderilme Zamanı')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Henüz gönderilmedi'),
                    ])
                    ->columns(2),

                Section::make('Callback Verileri')
                    ->schema([
                        TextEntry::make('callback_data')
                            ->label('Callback Verileri')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'Henüz callback alınmadı')
                            ->columnSpanFull()
                            ->copyable()
                            ->placeholder('Henüz callback alınmadı'),
                    ])
                    ->visible(fn ($record) => !empty($record->callback_data)),
            ]);
    }
}
