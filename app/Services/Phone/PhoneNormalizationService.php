<?php

namespace App\Services\Phone;

class PhoneNormalizationService
{
    /**
     * Normalize Turkish phone number to standard format (0XXXXXXXXXX).
     * 
     * Handles various formats:
     * - 05551234567 -> 05551234567
     * - 5551234567 -> 05551234567
     * - +905551234567 -> 05551234567
     * - 905551234567 -> 05551234567
     * - (555) 123 45 67 -> 05551234567
     * 
     * @param  string|null  $phone  Phone number in various formats
     * @return string|null  Normalized phone number (0XXXXXXXXXX) or null if invalid
     */
    public function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);

        // Remove country code if present (90)
        if (str_starts_with($digits, '90')) {
            $digits = substr($digits, 2);
        }

        // Remove leading 0 if present (we'll add it back)
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Validate: should be 10 digits and start with 5
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '0' . $digits;
        }

        return null;
    }

    /**
     * Normalize phone number for database storage and search.
     * This ensures consistent format for customer matching.
     * 
     * @param  string|null  $phone  Phone number in various formats
     * @return string|null  Normalized phone number or null if invalid
     */
    public function normalizeForStorage(?string $phone): ?string
    {
        return $this->normalize($phone);
    }

    /**
     * Check if two phone numbers match (after normalization).
     * 
     * @param  string|null  $phone1  First phone number
     * @param  string|null  $phone2  Second phone number
     * @return bool  True if normalized numbers match
     */
    public function matches(?string $phone1, ?string $phone2): bool
    {
        $normalized1 = $this->normalize($phone1);
        $normalized2 = $this->normalize($phone2);

        if ($normalized1 === null || $normalized2 === null) {
            return false;
        }

        return $normalized1 === $normalized2;
    }
}
