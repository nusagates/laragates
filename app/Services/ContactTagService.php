<?php

namespace App\Services;

use App\Models\Customer;
use App\Services\SystemLogService;

class ContactTagService
{
    public static function add(Customer $customer, string $tag): void
    {
        $tag  = self::normalize($tag);
        $tags = is_array($customer->tags) ? $customer->tags : [];

        if (!in_array($tag, $tags)) {
            $newTags = [...$tags, $tag];

            $customer->update(['tags' => $newTags]);

            SystemLogService::record(
                'contact_tag_added',
                'customer',
                $customer->id,
                ['tags' => $tags],
                ['tags' => $newTags],
                [
                    'phone' => $customer->phone,
                    'tag'   => $tag,
                ]
            );
        }
    }

    public static function remove(Customer $customer, string $tag): void
    {
        $tag  = self::normalize($tag);
        $tags = is_array($customer->tags) ? $customer->tags : [];

        $filtered = array_values(
            array_filter($tags, fn ($t) => $t !== $tag)
        );

        if ($filtered !== $tags) {
            $customer->update(['tags' => $filtered]);

            SystemLogService::record(
                'contact_tag_removed',
                'customer',
                $customer->id,
                ['tags' => $tags],
                ['tags' => $filtered],
                [
                    'phone' => $customer->phone,
                    'tag'   => $tag,
                ]
            );
        }
    }

    public static function has(Customer $customer, string $tag): bool
    {
        return in_array(
            self::normalize($tag),
            is_array($customer->tags) ? $customer->tags : []
        );
    }

    protected static function normalize(string $tag): string
    {
        return strtolower(trim($tag));
    }
}
