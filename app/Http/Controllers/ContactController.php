<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ContactTagService;
use App\Services\SystemLogService;

class ContactController extends Controller
{
    /**
     * ===============================
     * LIST CONTACTS
     * ===============================
     */
    public function index(Request $request)
    {
        $q = $request->input('q');

        $contacts = Customer::query()
            ->when($q, fn ($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
            )
            ->orderByDesc('updated_at')
            ->paginate(30);

        return response()->json($contacts);
    }

    /**
     * ===============================
     * CONTACT DETAIL
     * ===============================
     */
    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    /**
     * ===============================
     * UPDATE CONTACT
     * ===============================
     */
    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'is_vip'         => 'nullable|boolean',
            'is_blacklisted' => 'nullable|boolean',
            'notes'          => 'nullable|string|max:2000',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string|max:50',

            // ===============================
            // CRM ENRICHMENT (NEW)
            // ===============================
            'source'         => 'nullable|string|max:50',
            'priority'       => 'nullable|in:low,normal,high',
        ]);

        /**
         * ===============================
         * SNAPSHOT OLD VALUES (AUDIT)
         * ===============================
         */
        $oldValues = [
            'is_vip'         => $customer->is_vip,
            'is_blacklisted' => $customer->is_blacklisted,
            'notes'          => $customer->notes,
            'tags'           => $customer->tags,

            // CRM enrichment
            'source'         => $customer->source,
            'priority'       => $customer->priority,
        ];

        /**
         * ===============================
         * UPDATE BASIC FIELDS
         * ===============================
         */
        $customer->update([
            'is_vip'         => $data['is_vip']         ?? $customer->is_vip,
            'is_blacklisted' => $data['is_blacklisted'] ?? $customer->is_blacklisted,
            'notes'          => $data['notes']          ?? $customer->notes,

            // CRM enrichment
            'source'         => $data['source']         ?? $customer->source,
            'priority'       => $data['priority']       ?? $customer->priority,
        ]);

        /**
         * ===============================
         * TAG HANDLING
         * ===============================
         */
        if (array_key_exists('tags', $data)) {
            // reset dulu
            $customer->update(['tags' => []]);

            foreach ($data['tags'] as $tag) {
                ContactTagService::add($customer, $tag);
            }
        }

        /**
         * ===============================
         * AUTO TAG VIP
         * ===============================
         */
        if ($customer->is_vip) {
            ContactTagService::add($customer, 'vip');
        } else {
            ContactTagService::remove($customer, 'vip');
        }

        /**
         * ===============================
         * SYSTEM LOG — CONTACT UPDATED
         * ===============================
         */
        SystemLogService::record(
            event: 'contact_updated',
            entityType: 'customer',
            entityId: $customer->id,
            oldValues: $oldValues,
            newValues: [
                'is_vip'         => $customer->is_vip,
                'is_blacklisted' => $customer->is_blacklisted,
                'notes'          => $customer->notes,
                'tags'           => $customer->tags,

                // CRM enrichment
                'source'         => $customer->source,
                'priority'       => $customer->priority,
            ],
            meta: [
                'source' => 'CONTACT',
            ]
        );

        /**
         * ===============================
         * SYSTEM LOG — BLACKLIST CHANGE
         * ===============================
         */
        if (
            array_key_exists('is_blacklisted', $data) &&
            $oldValues['is_blacklisted'] !== $customer->is_blacklisted
        ) {
            SystemLogService::record(
                event: $customer->is_blacklisted
                    ? 'contact_blacklisted'
                    : 'contact_unblacklisted',
                entityType: 'customer',
                entityId: $customer->id,
                oldValues: ['is_blacklisted' => $oldValues['is_blacklisted']],
                newValues: ['is_blacklisted' => $customer->is_blacklisted],
                meta: [
                    'source' => 'SECURITY',
                ]
            );
        }

        /**
         * ===============================
         * SYSTEM LOG — PRIORITY CHANGE (NEW)
         * ===============================
         */
        if (
            array_key_exists('priority', $data) &&
            $oldValues['priority'] !== $customer->priority
        ) {
            SystemLogService::record(
                event: 'contact_priority_changed',
                entityType: 'customer',
                entityId: $customer->id,
                oldValues: ['priority' => $oldValues['priority']],
                newValues: ['priority' => $customer->priority],
                meta: [
                    'source' => 'CRM',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'contact' => $customer->fresh(),
        ]);
    }
}
