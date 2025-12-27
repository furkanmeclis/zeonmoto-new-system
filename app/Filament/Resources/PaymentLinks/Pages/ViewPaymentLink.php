<?php

namespace App\Filament\Resources\PaymentLinks\Pages;

use App\Filament\Resources\PaymentLinks\PaymentLinkResource;
use App\Models\PaymentLink;
use App\Services\Payment\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use FurkanMeclis\PayTRLink\Facades\PayTRLink;
use FurkanMeclis\PayTRLink\Data\DeleteLinkData;

class ViewPaymentLink extends ViewRecord
{
    protected static string $resource = PaymentLinkResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        /** @var PaymentLink $record */
        $record = $this->record;

        // Copy Link Action - Link URL is already copyable in infolist
        // This action is kept for quick access but the link can be copied from the infolist

        // Send SMS Action
        if ($record->canSendSms()) {
            $actions[] = Action::make('sendSms')
                ->label('SMS Gönder')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('SMS Gönder')
                ->modalDescription("SMS göndermek istediğinizden emin misiniz? Telefon: {$record->customer_phone}")
                ->modalSubmitActionLabel('Evet, Gönder')
                ->action(function () use ($record) {
                    $paymentService = app(PaymentService::class);
                    $result = $paymentService->sendPaymentLinkSms($record->paytr_link_id, $record->customer_phone);

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
                });
        }

        // Send Email Action
        if ($record->canSendEmail()) {
            $actions[] = Action::make('sendEmail')
                ->label('Email Gönder')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Email Gönder')
                ->modalDescription("Email göndermek istediğinizden emin misiniz? Email: {$record->customer_email}")
                ->modalSubmitActionLabel('Evet, Gönder')
                ->action(function () use ($record) {
                    $paymentService = app(PaymentService::class);
                    $result = $paymentService->sendPaymentLinkEmail($record->paytr_link_id, $record->customer_email);

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
                });
        }

        // Delete Link Action
        if ($record->status === 'pending' && !$record->isExpired()) {
            $actions[] = DeleteAction::make()
                ->label('Linki Sil')
                ->requiresConfirmation()
                ->modalHeading('Linki Sil')
                ->modalDescription('Bu linki PayTR\'de silmek ve veritabanından kaldırmak istediğinizden emin misiniz?')
                ->modalSubmitActionLabel('Evet, Sil')
                ->action(function () use ($record) {
                    try {
                        // Delete from PayTR
                        $deleteData = DeleteLinkData::from([
                            'link_id' => $record->paytr_link_id,
                        ]);
                        PayTRLink::delete($deleteData);

                        // Soft delete from database
                        $record->delete();

                        Notification::make()
                            ->title('Link Silindi')
                            ->body('Link PayTR\'de ve veritabanında silindi.')
                            ->success()
                            ->send();

                        $this->redirect(PaymentLinkResource::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Link silinirken bir hata oluştu: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                });
        }

        return $actions;
    }
}
