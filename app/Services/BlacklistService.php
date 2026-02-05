<?php

namespace App\Services;

use App\Models\Customer;
use App\Services\System\ChatLogService;

class BlacklistService
{
    /**
     * Check if customer is blacklisted
     */
    public static function isBlacklisted(Customer $customer): bool
    {
        return (bool) $customer->is_blacklisted;
    }

    /**
     * Enforce blacklist rule
     */
    public static function enforce(Customer $customer, string $action): void
    {
        if (!self::isBlacklisted($customer)) {
            return;
        }

        ChatLogService::log(
            event: 'blacklist_blocked_action',
            meta: [
                'customer_id' => $customer->id,
                'phone'       => $customer->phone,
                'action'      => $action,
            ]
        );

        abort(403, 'Customer is blacklisted');
    }
}
