<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SystemLog;

class ContactTimelineController extends Controller
{
    public function index(Customer $customer)
    {
        $logs = SystemLog::where(function ($q) use ($customer) {
                $q->where('entity_type', 'customer')
                  ->where('entity_id', $customer->id);
            })
            ->orWhere(function ($q) use ($customer) {
                $q->where('meta->customer_id', $customer->id);
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'event' => $log->event,
                    'actor' => $log->user_role ?? 'system',
                    'time'  => $log->created_at->toISOString(),
                    'meta'  => $log->meta,
                ];
            });

        return response()->json($logs);
    }
}
