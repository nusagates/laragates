<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Crm\CustomerSummaryService;

class CustomerSummaryController extends Controller
{
    /**
     * GET /api/customers/{id}/summary
     */
    public function show(int $id, CustomerSummaryService $service)
    {
        $customer = Customer::findOrFail($id);

        return response()->json(
            $service->build($customer)
        );
    }
}
