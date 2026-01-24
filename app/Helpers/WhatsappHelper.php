<?php

if (! function_exists('normalizePhone')) {
    /**
     * Normalize phone numbers to international format (62xxx)
     * Accepts: 62xxx, +62xxx, 08xx, 8xxx formats
     *
     * @return string|null Normalized phone number or null if invalid
     */
    function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        // Remove whitespace and non-numeric characters except leading +
        $phone = preg_replace('/[^\d+]/', '', trim($phone));

        // Remove leading + if present
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        // Remove leading zeros (for 08xx format)
        while (str_starts_with($phone, '0') && strlen($phone) > 1) {
            $phone = substr($phone, 1);
        }

        // Handle numbers starting with 8 (already has country code 6)
        if (str_starts_with($phone, '8') && ! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        // Handle numbers starting with 62 (already normalized)
        if (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        // Validate: should be at least 62 + 9 digits (total 11 digits)
        if (strlen($phone) < 11 || strlen($phone) > 15) {
            return null;
        }

        // Validate: should only contain digits
        if (! ctype_digit($phone)) {
            return null;
        }

        return $phone;
    }
}
