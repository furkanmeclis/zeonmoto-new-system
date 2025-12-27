<?php

namespace App\Filament\Resources\PaymentLinks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Link Bilgileri')
                    ->schema([
                        TextInput::make('paytr_link_id')
                            ->label('PayTR Link ID')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('link_url')
                            ->label('Link URL')
                            ->url()
                            ->disabled()
                            ->dehydrated()
                            ->copyable(),

                        TextInput::make('name')
                            ->label('Link Adı')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('price')
                            ->label('Fiyat')
                            ->prefix('₺')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('currency')
                            ->label('Para Birimi')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('link_type')
                            ->label('Link Tipi')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('max_installment')
                            ->label('Maksimum Taksit')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('expiry_date')
                            ->label('Son Kullanma Tarihi')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Müşteri Bilgileri')
                    ->schema([
                        TextInput::make('merchant_oid')
                            ->label('Sipariş Numarası')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('customer_email')
                            ->label('Müşteri Email')
                            ->email()
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('customer_phone')
                            ->label('Müşteri Telefon')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Durum')
                    ->schema([
                        TextInput::make('status')
                            ->label('Durum')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('callback_received_at')
                            ->label('Callback Alınma Zamanı')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('sms_sent_at')
                            ->label('SMS Gönderilme Zamanı')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('email_sent_at')
                            ->label('Email Gönderilme Zamanı')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }
}
