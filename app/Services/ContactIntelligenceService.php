<?php

namespace App\Services;

use App\Models\Customer;

class ContactIntelligenceService
{
    /**
     * Auto tagging based on activity
     */
    public static function evaluate(Customer $contact): void
    {
        $tags = $contact->tags ?? [];

        /**
         * ===============================
         * FREQUENT CONTACT
         * ===============================
         * >= 20 messages
         */
        if ($contact->total_messages >= 20) {
            if (!in_array('frequent', $tags)) {
                $tags[] = 'frequent';
            }
        }

        /**
         * ===============================
         * INACTIVE CONTACT
         * ===============================
         * > 30 hari tidak dihubungi
         */
        if ($contact->last_contacted_at) {
            $days = now()->diffInDays($contact->last_contacted_at);

            if ($days > 30 && !in_array('inactive', $tags)) {
                $tags[] = 'inactive';
            }

            if ($days <= 30) {
                // hapus inactive jika aktif lagi
                $tags = array_filter($tags, fn ($t) => $t !== 'inactive');
            }
        }

        $contact->tags = array_values(array_unique($tags));
        $contact->save();
    }
}
