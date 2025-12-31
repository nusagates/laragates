<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\ContactTagService;
use App\Services\System\ChatLogService;

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
        ]);

        /**
         * ===============================
         * UPDATE BASIC FIELDS
         * ===============================
         */
        $customer->update([
            'is_vip'         => $data['is_vip']         ?? $customer->is_vip,
            'is_blacklisted' => $data['is_blacklisted'] ?? $customer->is_blacklisted,
            'notes'          => $data['notes']          ?? $customer->notes,
        ]);

        /**
         * ===============================
         * TAG HANDLING
         * ===============================
         */
        if (isset($data['tags'])) {
            // reset tags dulu
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
         * SYSTEM LOG
         * ===============================
         */
        ChatLogService::log(
            event: 'contact_updated',
            meta: [
                'customer_id' => $customer->id,
                'changes'     => $data,
            ]
        );

        return response()->json([
            'success' => true,
            'contact' => $customer->fresh(),
        ]);
    }
}
