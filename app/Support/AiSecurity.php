<?php

namespace App\Support;

class AiSecurity
{
    /**
     * Hash prompt (SHA256)
     * Dipakai untuk audit trail, TANPA simpan isi prompt
     */
    public static function hashPrompt(string $prompt): string
    {
        return hash('sha256', $prompt);
    }

    /**
     * Sanitasi prompt dari data sensitif (PII)
     * Aman untuk WABA & compliance
     */
    public static function sanitizePrompt(string $prompt): string
    {
        $patterns = [
            // Email
            '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i',

            // Nomor HP (10–13 digit)
            '/\b\d{10,13}\b/',

            // Kartu / nomor panjang (16 digit)
            '/\b\d{16}\b/',
        ];

        return preg_replace($patterns, '[REDACTED]', $prompt);
    }
}
