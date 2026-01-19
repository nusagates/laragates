<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;

class ContactScoringService
{
    /**
     * ===============================
     * CONTACT SCORING & SEGMENTATION
     * ===============================
     *
     * Tags managed by this service:
     * - loyal
     * - frequent
     * - cold
     *
     * Tags NOT touched:
     * - vip
     * - blacklist
     * - manual / custom tags
     */
    public static function evaluate(Customer $customer): void
    {
        $now = Carbon::now();

        $lastContact = $customer->last_contacted_at
            ? Carbon::parse($customer->last_contacted_at)
            : null;

        /**
         * ===============================
         * CURRENT TAGS (SAFE COPY)
         * ===============================
         */
        $tags = collect($customer->tags ?? []);

        /**
         * ===============================
         * REMOVE ONLY SCORING TAGS
         * ===============================
         */
        $tags = $tags->reject(fn ($tag) =>
            in_array($tag, [
                'loyal',
                'frequent',
                'cold',
            ])
        );

        /**
         * ===============================
         * SCORING RULES (ORDER MATTERS)
         * ===============================
         */

        // 🟢 LOYAL
        if (
            $customer->total_chats >= 10 &&
            $customer->total_messages >= 50 &&
            $lastContact &&
            $lastContact->diffInDays($now) <= 14
        ) {
            $tags->push('loyal');
        }

        // 🟡 FREQUENT
        elseif (
            $customer->total_chats >= 3 &&
            $lastContact &&
            $lastContact->diffInDays($now) <= 30
        ) {
            $tags->push('frequent');
        }

        // 🔵 COLD
        elseif (
            !$lastContact ||
            $lastContact->diffInDays($now) > 60
        ) {
            $tags->push('cold');
        }

        /**
         * ===============================
         * SAVE (NORMALIZED)
         * ===============================
         */
        $customer->update([
            'tags' => $tags
                ->map(fn ($t) => strtolower(trim($t)))
                ->unique()
                ->values()
                ->toArray(),
        ]);
    }
}
