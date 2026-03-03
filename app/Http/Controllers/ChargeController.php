<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInvoiceRequest;
use App\Models\Invoice;

class ChargeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CreateInvoiceRequest $request)
    {
        $data = $request->validated();

        $invoice = Invoice::create($data);

        return response()->json([
            'status' => true,
            'ulid' => $invoice->ulid,
            'invoice_id' => $invoice->invoice_id,
            'payment_url' => $invoice->payment_url,
            'message' => 'Invoice created successfully.',
        ], 201);
    }
}
