<?php

namespace App\Filament\Resources\PaymentLinks\Tables;

use App\Models\PaymentLink;
use App\Services\Payment\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FurkanMeclis\PayTRLink\Facades\PayTRLink;
use FurkanMeclis\PayTRLink\Data\DeleteLinkData;

class PaymentLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paytr_link_id')
                    ->label('Link ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('order.order_no')
                    ->label('Sipariş')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', $record->order) : null)
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Link Adı')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('price')
                    ->label('Fiyat')
                    ->money('TRY')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn ($record) => $record->getStatusBadgeColor())
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Beklemede',
                        'paid' => 'Ödendi',
                        'expired' => 'Süresi Doldu',
                        'cancelled' => 'İptal Edildi',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('link_type')
                    ->label('Tip')
                    ->badge()
                    ->color(fn ($state) => $state === 'Collection' ? 'success' : 'info')
                    ->formatStateUsing(fn ($state) => $state === 'Collection' ? 'Toplu' : 'Ürün')
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Son Kullanma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),

                IconColumn::make('callback_received_at')
                    ->label('Callback')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => !is_null($record->callback_received_at)),

                IconColumn::make('sms_sent_at')
                    ->label('SMS')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => !is_null($record->sms_sent_at)),

                IconColumn::make('email_sent_at')
                    ->label('Email')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => !is_null($record->email_sent_at)),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Beklemede',
                        'paid' => 'Ödendi',
                        'expired' => 'Süresi Doldu',
                        'cancelled' => 'İptal Edildi',
                    ]),
                SelectFilter::make('link_type')
                    ->label('Tip')
                    ->options([
                        'Product' => 'Ürün',
                        'Collection' => 'Toplu',
                    ]),
            ])
            ->recordActions([
                Action::make('sendSms')
                    ->label('SMS Gönder')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->fillForm(fn (PaymentLink $record): array => [
                        'phone' => $record->customer_phone ?? '',
                    ])
                    ->form([
                        TextInput::make('phone')
                            ->label('Telefon Numarası')
                            ->required()
                            ->tel()
                            ->helperText('Türk telefon formatı: 5551234567 veya 05551234567')
                            ->maxLength(15),
                    ])
                    ->action(function (array $data, PaymentLink $record): void {
                        $paymentService = app(PaymentService::class);
                        $result = $paymentService->sendPaymentLinkSms($record->paytr_link_id, $data['phone']);

                        if ($result['success']) {
                            $record->update(['sms_sent_at' => now()]);
                            Notification::make()
                                ->title('SMS Gönderildi')
                                ->body('SMS başarıyla gönderildi.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('SMS Gönderilemedi')
                                ->body($result['message'] ?? 'SMS gönderilirken bir hata oluştu.')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('sendEmail')
                    ->label('Email Gönder')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->fillForm(fn (PaymentLink $record): array => [
                        'email' => $record->customer_email ?? '',
                    ])
                    ->form([
                        TextInput::make('email')
                            ->label('Email Adresi')
                            ->required()
                            ->email()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, PaymentLink $record): void {
                        $paymentService = app(PaymentService::class);
                        $result = $paymentService->sendPaymentLinkEmail($record->paytr_link_id, $data['email']);

                        if ($result['success']) {
                            $record->update(['email_sent_at' => now()]);
                            Notification::make()
                                ->title('Email Gönderildi')
                                ->body('Email başarıyla gönderildi.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Email Gönderilemedi')
                                ->body($result['message'] ?? 'Email gönderilirken bir hata oluştu.')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('deleteLink')
                    ->label('Linki Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Linki Sil')
                    ->modalDescription('Bu linki PayTR\'de silmek ve veritabanından kaldırmak istediğinizden emin misiniz? Bu işlem geri alınamaz.')
                    ->modalSubmitActionLabel('Evet, Sil')
                    ->visible(fn (PaymentLink $record) => $record->status === 'pending' && !$record->isExpired())
                    ->action(function (PaymentLink $record): void {
                        try {
                            // Delete from PayTR API
                            $deleteData = DeleteLinkData::from([
                                'link_id' => $record->paytr_link_id,
                            ]);
                            
                            $response = PayTRLink::delete($deleteData);

                            if ($response->isSuccess()) {
                                // Soft delete from database
                                $record->update(['status' => 'cancelled']);
                                $record->delete();

                                Notification::make()
                                    ->title('Link Silindi')
                                    ->body('Link PayTR\'de ve veritabanında başarıyla silindi.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Link Silinemedi')
                                    ->body($response->message ?? 'PayTR API\'den link silinirken bir hata oluştu.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Hata')
                                ->body('Link silinirken bir hata oluştu: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
