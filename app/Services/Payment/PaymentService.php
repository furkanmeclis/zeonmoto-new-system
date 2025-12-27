<?php

namespace App\Services\Payment;

use FurkanMeclis\PayTRLink\Facades\PayTRLink;
use FurkanMeclis\PayTRLink\Data\CreateLinkData;
use FurkanMeclis\PayTRLink\Data\CallbackData;
use FurkanMeclis\PayTRLink\Data\SendSmsData;
use FurkanMeclis\PayTRLink\Enums\CurrencyEnum;
use FurkanMeclis\PayTRLink\Enums\LinkTypeEnum;
use FurkanMeclis\PayTRLink\Exceptions\PayTRRequestException;
use FurkanMeclis\PayTRLink\Exceptions\PayTRValidationException;

class PaymentService
{
    /**
     * Create PayTR payment link for an order.
     *
     * @param  string  $orderNo  Order number
     * @param  float  $amount  Amount in TL
     * @param  string  $customerName  Customer full name
     * @param  string|null  $customerEmail  Customer email (optional)
     * @param  int  $maxInstallment  Maximum installment count
     * @return array{success: bool, link?: string, link_id?: string, message?: string, errors?: array}
     */
    public function createPayTRLink(
        string $orderNo,
        float $amount,
        string $customerName,
        ?string $customerEmail = null,
        int $maxInstallment = 12
    ): array {
        try {
            $linkData = CreateLinkData::from([
                'name' => "Sipariş #{$orderNo}",
                'price' => $amount, // Automatically converted to kuruş by the package
                'currency' => CurrencyEnum::TL,
                'link_type' => $customerEmail ? LinkTypeEnum::Collection : LinkTypeEnum::Product,
                'max_installment' => $maxInstallment,
                'min_count' => 1,
                'lang' => 'tr',
                'expiry_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'description' => "Zeon Moto - Sipariş #{$orderNo}",
                ...($customerEmail ? ['email' => $customerEmail] : []),
            ]);

            $response = PayTRLink::create($linkData);

            if ($response->isSuccess()) {
                return [
                    'success' => true,
                    'link' => $response->link,
                    'link_id' => $response->id,
                ];
            }

            return [
                'success' => false,
                'message' => $response->message ?? 'Ödeme linki oluşturulamadı',
                'errors' => $response->errors ?? [],
            ];

        } catch (PayTRValidationException $e) {
            return [
                'success' => false,
                'message' => 'Ödeme linki oluşturulurken validasyon hatası oluştu',
                'errors' => $e->errors ?? [],
            ];

        } catch (PayTRRequestException $e) {
            return [
                'success' => false,
                'message' => 'Ödeme linki oluşturulurken API hatası oluştu',
                'errors' => [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ödeme linki oluşturulurken bir hata oluştu',
                'errors' => [],
            ];
        }
    }

    /**
     * Get bank account information for transfer payments.
     *
     * @return array{name: string, bank: string, iban: string, branch: string}
     */
    public function getBankAccountInfo(): array
    {
        return config('payment.bank_account', [
            'name' => '',
            'bank' => '',
            'iban' => '',
            'branch' => '',
        ]);
    }

    /**
     * Validate PayTR callback.
     *
     * @param  array  $data  Callback data from PayTR
     * @return bool
     */
    public function validatePayTRCallback(array $data): bool
    {
        try {
            return PayTRLink::validateCallback($data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Parse PayTR callback data.
     *
     * @param  array  $data  Callback data from PayTR
     * @return CallbackData|null
     */
    public function parsePayTRCallback(array $data): ?CallbackData
    {
        try {
            return CallbackData::from($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Send payment link via SMS.
     *
     * @param  string  $linkId  PayTR link ID
     * @param  string  $phone  Phone number (Turkish format: 5551234567 or 05551234567)
     * @return array{success: bool, message?: string}
     */
    public function sendPaymentLinkSms(string $linkId, string $phone): array
    {
        try {
            // Normalize phone number (remove +90, spaces, dashes, etc.)
            $normalizedPhone = $this->normalizePhoneNumber($phone);

            if (!$normalizedPhone) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz telefon numarası formatı',
                ];
            }

            $smsData = SendSmsData::from([
                'link_id' => $linkId,
                'phone' => $normalizedPhone,
            ]);

            $response = PayTRLink::sendSms($smsData);

            if ($response->isSuccess()) {
                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'message' => $response->message ?? 'SMS gönderilemedi',
                'errors' => $response->errors ?? [],
            ];

        } catch (PayTRRequestException $e) {
            return [
                'success' => false,
                'message' => 'SMS gönderilirken API hatası oluştu',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SMS gönderilirken bir hata oluştu',
            ];
        }
    }

    /**
     * Normalize Turkish phone number to PayTR format (10 digits without leading 0).
     *
     * @param  string  $phone  Phone number in various formats
     * @return string|null  Normalized phone number (e.g., 5551234567) or null if invalid
     */
    protected function normalizePhoneNumber(string $phone): ?string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);

        // Remove country code if present
        if (str_starts_with($digits, '90')) {
            $digits = substr($digits, 2);
        }

        // Remove leading 0 if present
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Validate: should be 10 digits and start with 5
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return $digits;
        }

        return null;
    }
}

